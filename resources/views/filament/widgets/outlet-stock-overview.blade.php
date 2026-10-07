<div class="rounded-xl border border-gray-300 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
    <div class="mb-3 flex items-center justify-between gap-2">
        <div class="text-sm font-semibold text-gray-950 dark:text-white">
            Stock {{ $outlet->name }}
        </div>
        <span class="text-xs text-gray-500 dark:text-gray-400">jumlah box tersedia</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <th class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Bakpia</th>
                    <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Stok</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr class="border-b border-gray-100 last:border-b-0 dark:border-gray-800">
                        <td class="px-3 py-2 font-medium text-gray-950 dark:text-white">{{ $row['name'] }}</td>
                        <td class="px-3 py-2 text-right font-semibold text-gray-950 dark:text-white">{{ number_format($row['on_hand']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400">
                            Belum ada stock untuk outlet ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>