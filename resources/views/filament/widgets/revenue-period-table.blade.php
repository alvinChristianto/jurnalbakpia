<div class="rounded-xl border border-gray-300 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
    <div class="mb-3 text-sm font-semibold text-gray-950 dark:text-white">
        Rekap Pendapatan
    </div>

    <div class="overflow-x-auto">
        <table class="w-full table-fixed text-left text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <th class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Periode</th>
                    <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Total</th>
                    @foreach ($outlets as $outlet)
                        <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">
                            {{ $outlet->name }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr class="border-b border-gray-100 last:border-b-0 dark:border-gray-800">
                        <td class="px-3 py-2 font-medium text-gray-950 dark:text-white">{{ $row['label'] }}</td>
                        <td class="px-3 py-2 text-right font-medium text-gray-950 dark:text-white">
                            {{ 'Rp ' . number_format($row['total'], 0, ',', '.') }}
                        </td>
                        @foreach ($outlets as $outlet)
                            <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-300">
                                {{ 'Rp ' . number_format($row['perOutlet'][$outlet->id_outlet], 0, ',', '.') }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>