<x-filament-panels::page>
    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Gift Card</th>
                    <th class="text-center px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Total</th>
                    <th class="text-center px-4 py-3 font-semibold text-green-600">Available</th>
                    <th class="text-center px-4 py-3 font-semibold text-yellow-600">Reserved</th>
                    <th class="text-center px-4 py-3 font-semibold text-blue-600">Sold</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($cards as $card)
                @php
                    $rowClass = match(true) {
                        $card['available'] === 0 => 'bg-red-50 dark:bg-red-900/20',
                        $card['available'] <= 5  => 'bg-amber-50 dark:bg-amber-900/20',
                        default                  => '',
                    };
                @endphp
                <tr class="{{ $rowClass }}">
                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $card['name'] }}</td>
                    <td class="px-4 py-3 text-center text-gray-500">{{ $card['total'] }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex items-center justify-center rounded-full px-2.5 py-0.5 text-xs font-semibold
                            {{ $card['available'] > 5 ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : ($card['available'] > 0 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300') }}">
                            {{ $card['available'] }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center text-yellow-600 font-medium">{{ $card['reserved'] }}</td>
                    <td class="px-4 py-3 text-center text-blue-600 font-medium">{{ $card['sold'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
