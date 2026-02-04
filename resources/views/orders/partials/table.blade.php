                <!-- Table -->
                <div class="mb-4 overflow-x-auto rounded-xl">
                    <table class="min-w-full divide-y divide-gray-200 rounded-xl text-sm">
                        <thead class="bg-gray-50 text-left">
                            <tr>
                                <th class="px-4 py-3 font-medium text-gray-700">Order #</th>
                                <th class="px-4 py-3 font-medium text-gray-700">Customer</th>
                                {{-- 👔 Only managers see this column --}}
                                @if (auth()->user()->role === 'manager' || auth()->user()->role === 'super admin')
                                    <th class="px-4 py-3 font-medium text-gray-700">Requesting Store</th>
                                @endif
                                <th class="px-4 py-3 font-medium text-gray-700">Order Date</th>
                                <th class="px-4 py-3 font-medium text-gray-700">Channel</th>
                                {{-- <th class="px-4 py-3 font-medium text-gray-700">Delivery Date</th> --}}
                                <th class="px-4 py-3 font-medium text-gray-700">Status</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($orders as $order)
                                <tr class="animate-fade-in transition-all duration-200 hover:bg-indigo-100/60">
                                    <td class="whitespace-nowrap px-4 py-3">{{ $order->sof_id }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">{{ $order->customer_name }}</td>
                                    {{-- 👔 Only managers see store --}}
                                    @if (auth()->user()->role === 'manager' || auth()->user()->role === 'super admin')
                                        @php
                                            // All store names (exclude lz/vs keys)
                                            $allStoreLocations = [
                                                '4002' => 'F2 - Metro Wholesalemart Colon',
                                                '2010' => 'S10 - Metro Maasin',
                                                '2017' => 'S17 - Metro Tacloban',
                                                '2019' => 'S19 - Metro Bay-Bay',
                                                '3018' => 'F18 - Metro Alang-Alang',
                                                '3019' => 'F19 - Metro Hilongos',
                                                '2008' => 'S8 - Metro Toledo',
                                                '6012' => 'H8 - Super Metro Antipolo',
                                                '6009' => 'H9 - Super Metro Carcar',
                                                '6010' => 'H10 - Super Metro Bogo',
                                            ];

                                            $storeName = $allStoreLocations[$order->requesting_store] ?? 'Unknown Store';
                                        @endphp

                                        <td class="whitespace-nowrap px-4 py-3">
                                            {{ $storeName }}
                                        </td>
                                    @endif
                                    <td class="whitespace-nowrap px-4 py-3">
                                        {{ \Carbon\Carbon::parse($order->time_order)->format('Y-m-d H:i') }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        @php
                                            $channel = strtolower(trim($order->channel_order ?? ''));
                                            $channelDisplay = ucwords($channel ?: 'Unknown');

                                            $channelClass = match ($channel) {
                                                'e-commerce', 'ecommerce', 'online' => 'bg-yellow-100 text-green-800',
                                                'wholesale', 'wholesaler' => 'bg-blue-100 text-blue-800',
                                                default => 'bg-gray-100 text-gray-800',
                                            };
                                        @endphp

                                        <span class="{{ $channelClass }} inline-block rounded-lg px-2 py-1 text-xs font-medium">
                                            {{ $channelDisplay }}
                                        </span>
                                    </td>

                                    {{-- <td class="whitespace-nowrap px-4 py-3">
																				{{ \Carbon\Carbon::parse($order->delivery_date)->format('Y-m-d') }}</td> --}}
                                    <td class="whitespace-nowrap px-4 py-3">
                                        @php
                                            $status = ucwords(strtolower($order->order_status ?? 'New Order'));
                                            $statusClass = match ($status) {
                                                'Completed' => 'bg-green-200 text-green-800',
                                                'Archived' => 'bg-gray-200 text-gray-800',
                                                'Cancelled' => 'bg-red-200 text-red-800',
                                                'Pending' => 'bg-yellow-200 text-yellow-800',
                                                'Rejected' => 'bg-orange-200 text-orange-800',
                                                'For Approval' => 'bg-purple-100 text-purple-800',
                                                'Approved' => 'bg-green-100 text-green-800',
                                                default => 'bg-blue-100 text-blue-800',
                                            };
                                        @endphp

                                        <span class="{{ $statusClass }} inline-block rounded-lg px-2 py-1 text-xs font-medium">
                                            {{ $status }}
                                        </span>

                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <a
                                            href="{{ route('orders.show', $order->id) }}"
                                            class="inline-block font-medium text-indigo-600 hover:text-indigo-800">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="7"
                                        class="px-4 py-4 text-center text-gray-500">No orders found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="flex justify-end p-4">
                    {{ $orders->links('pagination::tailwind') }}
                </div>
