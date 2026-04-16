<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class FileManagerController extends Controller
{
    /**
     * Constructor - Apply authentication middleware
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('session.expired');
    }

    /**
     * Get the base path for file storage
     */
    private function getBasePath(): string
    {
        return storage_path('app/filemanager');
    }

    /**
     * Resolve & validate a relative sub-path against BASE_PATH
     */
    private function resolve(?string $rel = ''): string
    {
        // Handle null values - convert to empty string
        $rel = $rel ?? '';

        $basePath = $this->getBasePath();

        // Ensure base directory exists
        if (!is_dir($basePath)) {
            mkdir($basePath, 0755, true);
        }

        $rel = ltrim($rel, '/');
        $fullPath = $basePath . ($rel ? DIRECTORY_SEPARATOR . $rel : '');
        $real = realpath($fullPath);

        // Prevent path-traversal outside BASE_PATH
        if ($real === false || !str_starts_with($real, realpath($basePath))) {
            Log::warning('FileManager access denied', [
                'user_id' => auth()->id(),
                'requested_path' => $rel,
                'resolved_path' => $real,
                'base_path' => $basePath
            ]);
            abort(403, 'Access denied.');
        }

        return $real;
    }

    /**
     * Build breadcrumb segments from a relative path string
     */
    private function breadcrumbs(string $rel): array
    {
        $crumbs = [['label' => 'Home', 'path' => '']];
        if ($rel === '') {
            return $crumbs;
        }

        $parts = explode('/', trim($rel, '/'));
        $built = '';
        foreach ($parts as $part) {
            $built .= ($built ? '/' : '') . $part;
            $crumbs[] = ['label' => $part, 'path' => $built];
        }
        return $crumbs;
    }

    /**
     * Format file size in human readable format
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }
        if ($bytes < 1073741824) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        return round($bytes / 1073741824, 2) . ' GB';
    }

    /**
     * Validate filename to prevent invalid characters
     */
    private function isValidFilename(string $name): bool
    {
        // Check for invalid characters: / \ < > : " | ? * and null bytes
        return preg_match('/^[^\/\\\\<>:"|?*\x00-\x1f]+$/u', $name) === 1;
    }

    /**
     * Display file manager index
     */
    public function index(Request $request)
    {
        $rel = $request->query('path', '');
        $dir = $this->resolve($rel);
        $base = $this->getBasePath();

        // Ensure the base folder exists
        if (!is_dir($base)) {
            mkdir($base, 0755, true);
        }

        $raw = scandir($dir);
        $entries = [];

        foreach ($raw as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }

            $full = $dir . DIRECTORY_SEPARATOR . $name;
            $isDir = is_dir($full);
            $subRel = ($rel ? $rel . '/' : '') . $name;
            $ext = !$isDir ? strtolower(pathinfo($name, PATHINFO_EXTENSION)) : '';
            $size = !$isDir ? filesize($full) : null;
            $modified = filemtime($full);

            $entries[] = [
                'name' => $name,
                'path' => $subRel,
                'is_dir' => $isDir,
                'ext' => $ext,
                'size' => $size,
                'size_formatted' => $size ? $this->formatBytes($size) : '—',
                'modified' => $modified,
                'modified_formatted' => date('Y-m-d H:i', $modified),
            ];
        }

        // Folders first, then files — both alphabetical
        usort($entries, function ($a, $b) {
            if ($a['is_dir'] !== $b['is_dir']) {
                return $a['is_dir'] ? -1 : 1;
            }
            return strcasecmp($a['name'], $b['name']);
        });

        return view('filemanager.index', [
            'entries' => $entries,
            'currentPath' => $rel,
            'breadcrumbs' => $this->breadcrumbs($rel),
            'baseName' => basename($this->getBasePath()),
        ]);
    }

    /**
     * Upload files
     */
    public function upload(Request $request)
    {
        $request->validate([
            'files.*' => 'required|file|max:51200', // 50 MB per file
            'path' => 'nullable|string',
        ]);

        $rel = $request->input('path', '');
        $dir = $this->resolve($rel);
        $uploadedCount = 0;

        foreach ($request->file('files') as $file) {
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            $safeName = Str::slug($originalName) . ($extension ? '.' . $extension : '');

            // Avoid duplicate filenames
            $counter = 1;
            $finalName = $safeName;
            while (file_exists($dir . DIRECTORY_SEPARATOR . $finalName)) {
                $finalName = Str::slug($originalName) . '-' . $counter . ($extension ? '.' . $extension : '');
                $counter++;
            }

            $file->move($dir, $finalName);
            $uploadedCount++;
        }

        return back()->with('success', $uploadedCount . ' file(s) uploaded successfully.');
    }

    /**
     * Download file
     */
    public function download(Request $request)
    {
        $rel = $request->query('path', '');
        $full = $this->resolve($rel);

        if (!is_file($full)) {
            abort(404, 'File not found.');
        }

        return response()->download($full);
    }

    /**
     * Rename file or directory
     */
    public function rename(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
            'newname' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if (!$this->isValidFilename($value)) {
                        $fail('The ' . $attribute . ' contains invalid characters. Allowed: letters, numbers, spaces, dots, hyphens, and underscores.');
                    }
                },
            ],
        ]);

        $rel = $request->input('path');
        $full = $this->resolve($rel);
        $parent = dirname($full);
        $newFull = $parent . DIRECTORY_SEPARATOR . $request->input('newname');

        if (file_exists($newFull)) {
            return back()->withErrors(['newname' => 'A file or folder with that name already exists.']);
        }

        if (!rename($full, $newFull)) {
            return back()->withErrors(['error' => 'Failed to rename. Please check permissions.']);
        }

        return back()->with('success', 'Renamed successfully.');
    }

    /**
     * Delete file or directory
     */
    public function delete(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $full = $this->resolve($request->input('path'));

        try {
            if (is_dir($full)) {
                $this->deleteDirectory($full);
                $message = 'Folder deleted successfully.';
            } else {
                unlink($full);
                $message = 'File deleted successfully.';
            }
        } catch (\Exception $e) {
            Log::error('FileManager delete error', [
                'user_id' => auth()->id(),
                'path' => $full,
                'error' => $e->getMessage()
            ]);
            return back()->withErrors(['error' => 'Failed to delete: ' . $e->getMessage()]);
        }

        return back()->with('success', $message);
    }

    /**
     * Recursively delete a directory and all its contents
     */
    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = array_diff(scandir($dir), ['.', '..']);
        foreach ($items as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    /**
     * Create a new directory
     */
    public function mkdir(Request $request)
    {
        $request->validate([
            'path' => 'nullable|string',
            'dirname' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if (!$this->isValidFilename($value)) {
                        $fail('The ' . $attribute . ' contains invalid characters. Allowed: letters, numbers, spaces, dots, hyphens, and underscores.');
                    }
                },
            ],
        ]);

        // Handle null path - convert to empty string
        $rel = $request->input('path', '');
        $parent = $this->resolve($rel);  // This was line 313 - now $rel is always a string
        $newDir = $parent . DIRECTORY_SEPARATOR . $request->input('dirname');

        if (is_dir($newDir)) {
            return back()->withErrors(['dirname' => 'Folder already exists.']);
        }

        if (!mkdir($newDir, 0755)) {
            return back()->withErrors(['error' => 'Failed to create folder. Please check permissions.']);
        }

        return back()->with('success', 'Folder created successfully.');
    }
}
