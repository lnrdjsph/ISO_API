<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FileManagerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('session.expired');
    }

    private function getBasePath(): string
    {
        return storage_path('app/filemanager');
    }

    /**
     * Resolve & validate a relative path against the base path.
     *
     * The key fix: realpath() returns FALSE for paths that don't exist yet,
     * so we CANNOT use it to validate — only to normalise after we confirm
     * the constructed path string is safe. We do the traversal check on the
     * raw string first, then realpath only on paths that already exist.
     */
    private function resolve(?string $rel = ''): string
    {
        $rel      = ltrim($rel ?? '', '/');
        $basePath = $this->getBasePath();

        // Ensure base directory exists BEFORE any realpath calls
        if (!is_dir($basePath)) {
            mkdir($basePath, 0755, true);
        }

        // Build the candidate path without relying on realpath yet
        $candidate = $rel
            ? $basePath . DIRECTORY_SEPARATOR . $rel
            : $basePath;

        // Manually normalise (resolve . and ..) without requiring existence
        $candidate = $this->normalisePath($candidate);

        // Safety check on the normalised string — must still start with base
        $normalisedBase = $this->normalisePath($basePath);

        if (!str_starts_with($candidate, $normalisedBase)) {
            Log::warning('FileManager path traversal attempt', [
                'user_id'   => auth()->id(),
                'requested' => $rel,
                'candidate' => $candidate,
                'base'      => $normalisedBase,
            ]);
            abort(403, 'Access denied.');
        }

        // Now use realpath if the path exists (for symlink resolution),
        // but fall back to the normalised candidate if it doesn't exist yet.
        if (file_exists($candidate)) {
            $real = realpath($candidate);

            // Re-check after symlink resolution
            if ($real === false || !str_starts_with($real, realpath($basePath))) {
                abort(403, 'Access denied.');
            }

            return $real;
        }

        // Path doesn't exist yet (e.g. mkdir target) — return normalised candidate.
        // The mkdir/upload callers are responsible for actually creating it.
        return $candidate;
    }

    /**
     * Normalise a path string by resolving . and .. segments,
     * without requiring the path to exist on disk.
     */
    private function normalisePath(string $path): string
    {
        $path  = str_replace('\\', '/', $path);
        $parts = explode('/', $path);
        $stack = [];

        foreach ($parts as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                array_pop($stack);
            } else {
                $stack[] = $part;
            }
        }

        // Preserve leading slash on Unix
        $normalised = (str_starts_with($path, '/') ? '/' : '') . implode('/', $stack);

        // Re-apply OS separator
        return str_replace('/', DIRECTORY_SEPARATOR, $normalised);
    }

    private function breadcrumbs(string $rel): array
    {
        $crumbs = [['label' => 'Home', 'path' => '']];
        if ($rel === '') return $crumbs;

        $parts = explode('/', trim($rel, '/'));
        $built = '';
        foreach ($parts as $part) {
            $built    .= ($built ? '/' : '') . $part;
            $crumbs[] = ['label' => $part, 'path' => $built];
        }
        return $crumbs;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024)       return $bytes . ' B';
        if ($bytes < 1048576)    return round($bytes / 1024, 1) . ' KB';
        if ($bytes < 1073741824) return round($bytes / 1048576, 1) . ' MB';
        return round($bytes / 1073741824, 2) . ' GB';
    }

    private function isValidFilename(string $name): bool
    {
        return preg_match('/^[^\/\\\\<>:"|?*\x00-\x1f]+$/u', $name) === 1;
    }

    // ── Index ──────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $rel = $request->query('path', '') ?? '';
        $dir = $this->resolve($rel);

        if (!is_dir($dir)) {
            abort(404, 'Directory not found.');
        }

        $entries = [];
        foreach (scandir($dir) as $name) {
            if ($name === '.' || $name === '..') continue;

            $full     = $dir . DIRECTORY_SEPARATOR . $name;
            $isDir    = is_dir($full);
            $subRel   = ($rel ? $rel . '/' : '') . $name;
            $ext      = !$isDir ? strtolower(pathinfo($name, PATHINFO_EXTENSION)) : '';
            $size     = !$isDir ? filesize($full) : null;
            $modified = filemtime($full);

            $entries[] = [
                'name'             => $name,
                'path'             => $subRel,
                'is_dir'           => $isDir,
                'ext'              => $ext,
                'size'             => $size,
                'size_formatted'   => $size !== null ? $this->formatBytes($size) : '—',
                'modified'         => $modified,
                'modified_formatted' => date('Y-m-d H:i', $modified),
            ];
        }

        usort($entries, function ($a, $b) {
            if ($a['is_dir'] !== $b['is_dir']) return $a['is_dir'] ? -1 : 1;
            return strcasecmp($a['name'], $b['name']);
        });

        return view('filemanager.index', [
            'entries'     => $entries,
            'currentPath' => $rel,
            'breadcrumbs' => $this->breadcrumbs($rel),
            'baseName'    => basename($this->getBasePath()),
        ]);
    }

    // ── Upload ─────────────────────────────────────────────────────

    public function upload(Request $request)
    {
        $request->validate([
            'files.*' => 'required|file|max:51200',
            'path'    => 'nullable|string',
        ]);

        $rel   = $request->input('path', '') ?? '';
        $dir   = $this->resolve($rel);
        $count = 0;

        foreach ($request->file('files') as $file) {
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension    = $file->getClientOriginalExtension();
            $safeName     = Str::slug($originalName) . ($extension ? '.' . $extension : '');

            $counter   = 1;
            $finalName = $safeName;
            while (file_exists($dir . DIRECTORY_SEPARATOR . $finalName)) {
                $finalName = Str::slug($originalName) . '-' . $counter . ($extension ? '.' . $extension : '');
                $counter++;
            }

            $file->move($dir, $finalName);
            $count++;
        }

        return back()->with('success', $count . ' file(s) uploaded successfully.');
    }

    // ── Download ───────────────────────────────────────────────────

    public function download(Request $request)
    {
        $rel  = $request->query('path', '') ?? '';
        $full = $this->resolve($rel);

        if (!is_file($full)) abort(404, 'File not found.');

        return response()->download($full);
    }

    // ── Rename ─────────────────────────────────────────────────────

    public function rename(Request $request)
    {
        $request->validate([
            'path'    => 'required|string',
            'newname' => ['required', 'string', 'max:255', function ($attr, $value, $fail) {
                if (!$this->isValidFilename($value)) {
                    $fail('The name contains invalid characters.');
                }
            }],
        ]);

        $full    = $this->resolve($request->input('path'));
        $newFull = dirname($full) . DIRECTORY_SEPARATOR . $request->input('newname');

        if (file_exists($newFull)) {
            return back()->withErrors(['newname' => 'A file or folder with that name already exists.']);
        }

        if (!rename($full, $newFull)) {
            return back()->withErrors(['newname' => 'Failed to rename. Check permissions.']);
        }

        return back()->with('success', 'Renamed successfully.');
    }

    // ── Delete ─────────────────────────────────────────────────────

    public function delete(Request $request)
    {
        $request->validate(['path' => 'required|string']);

        $full = $this->resolve($request->input('path'));

        try {
            if (is_dir($full)) {
                $this->deleteDirectory($full);
                $msg = 'Folder deleted successfully.';
            } else {
                unlink($full);
                $msg = 'File deleted successfully.';
            }
        } catch (\Exception $e) {
            Log::error('FileManager delete error', ['user_id' => auth()->id(), 'error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Failed to delete: ' . $e->getMessage()]);
        }

        return back()->with('success', $msg);
    }

    private function deleteDirectory(string $dir): void
    {
        foreach (array_diff(scandir($dir), ['.', '..']) as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    // ── Make directory ─────────────────────────────────────────────

    public function mkdir(Request $request)
    {
        $request->validate([
            'path'    => 'nullable|string',
            'dirname' => ['required', 'string', 'max:255', function ($attr, $value, $fail) {
                if (!$this->isValidFilename($value)) {
                    $fail('The folder name contains invalid characters.');
                }
            }],
        ]);

        $rel    = $request->input('path', '') ?? '';
        $parent = $this->resolve($rel);
        $newDir = $parent . DIRECTORY_SEPARATOR . $request->input('dirname');

        if (is_dir($newDir)) {
            return back()->withErrors(['dirname' => 'Folder already exists.']);
        }

        if (!mkdir($newDir, 0755)) {
            return back()->withErrors(['dirname' => 'Failed to create folder. Check permissions.']);
        }

        return back()->with('success', 'Folder created successfully.');
    }
}
