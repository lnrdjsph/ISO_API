{{-- Full-page loader shown during WMS allocation update --}}
<div id="pageLoader" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="mx-4 flex w-full max-w-md flex-col items-center rounded-2xl bg-white px-8 py-6 shadow-2xl">
        <div class="h-12 w-12 animate-spin rounded-full border-4 border-blue-500 border-t-transparent"></div>

        <p id="loaderTitle" class="mt-4 text-lg font-semibold text-gray-700">
            Updating the following fields:
        </p>

        <ul class="mt-3 space-y-1 text-gray-600">
            @foreach (['WMS Actual Allocation', 'WMS Virtual Allocation', 'Case Pack'] as $field)
                <li class="flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                    {{ $field }}
                </li>
            @endforeach
        </ul>

        <div class="mt-4 w-full">
            <p id="loaderMessage" class="text-center text-sm text-gray-600">
                Please wait, this may take a few minutes...
            </p>
            <div id="progressDetails" class="mt-3 hidden rounded-lg bg-blue-50 p-3">
                <p class="font-mono text-[10px] text-blue-800" id="progressText"></p>
            </div>
        </div>

        <div class="mt-4 w-full">
            <div class="h-1 w-full overflow-hidden rounded-full bg-gray-200">
                <div id="progressBar" class="h-full w-0 bg-blue-500 transition-all duration-500"></div>
            </div>
        </div>
    </div>
</div>
