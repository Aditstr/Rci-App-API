<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="fi-wi-stats-overview-stat rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="text-lg font-medium text-gray-500 dark:text-gray-400">Total Pendapatan Platform (RCI)</h2>
            <div class="mt-2 text-3xl font-bold tracking-tight text-gray-950 dark:text-white">
                Rp {{ number_format((float)$balance, 0, ',', '.') }}
            </div>
        </div>
    </div>

    <div class="mt-8">
        <h2 class="text-xl font-bold mb-4 dark:text-white">Riwayat Transaksi Pendapatan</h2>
        
        <div class="overflow-x-auto bg-white rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-800 dark:text-gray-400 border-b dark:border-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3">Waktu</th>
                        <th scope="col" class="px-6 py-3">Deskripsi</th>
                        <th scope="col" class="px-6 py-3">Nominal</th>
                        <th scope="col" class="px-6 py-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $t)
                        <tr class="bg-white border-b dark:bg-gray-900 dark:border-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap">{{ $t->created_at->format('d M Y H:i') }}</td>
                            <td class="px-6 py-4">{{ $t->description }}</td>
                            <td class="px-6 py-4 text-green-600 font-bold">+ Rp {{ number_format((float)$t->amount, 0, ',', '.') }}</td>
                            <td class="px-6 py-4">
                                <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-green-900 dark:text-green-300">{{ $t->status }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center">Belum ada transaksi</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
