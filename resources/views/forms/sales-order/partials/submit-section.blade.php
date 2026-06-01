<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl">
    <div class="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-4">
        <h2 class="text-lg font-semibold text-gray-800">Additional Information</h2>
        <p class="text-sm text-gray-500">Order-level comments and final submission</p>
    </div>
    <div class="p-6">
        <div class="flex flex-col gap-6 md:flex-row md:items-end md:gap-8">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700">Order Comment <span class="text-xs text-gray-400">(optional)</span></label>
                <textarea name="comment" rows="3"
                    class="mt-1 block w-full rounded-lg border-gray-300 px-4 py-3 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder="e.g., Rush delivery, coordinate with warehouse...">{{ old('comment') }}</textarea>
            </div>
            <button type="submit" id="submitBtn"
                class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 px-8 py-4 text-base font-semibold text-white shadow-md transition-all duration-200 hover:scale-105 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:hover:scale-100">
                Submit Order
            </button>
        </div>
    </div>
</div>
