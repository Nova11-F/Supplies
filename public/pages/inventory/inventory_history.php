<div class="flex justify-between items-center mb-4 mt-3">
    <div>
    <h1 class="text-2xl px-4 font-bold text-[#092363]">
        Riwayat Pergerakan Barang
    </h1>
    <p class="px-4 text-gray-500 text-sm">
        Log pergerakan barang masuk, keluar, dan penyesuaian stok.
    </p>
</div>

    <div class="flex justify-end gap-2 items-center">
        <?php if ($isAdmin): ?>
        <button onclick="openModal('manualStockModal')" class="bg-white border border-[#092363] text-[#092363] px-4 py-2 rounded-lg text-sm font-bold hover:bg-[#092363] hover:text-white transition-all shadow-sm">
            <i class='bx bx-slider-alt'></i> Penyesuaian Manual
        </button>
        <button class="px-3 py-2 rounded-lg text-sm font-bold text-white bg-[#092363] hover:bg-[#e6b949] hover:text-[#092363] shadow-lg transform hover:-translate-y-0.5 transition-all">    
            <i class='bx bxs-file-pdf'></i> Export Laporan
        </button>
        <?php endif; ?>
    </div>

</div>

<div class="bg-white p-4 rounded-md shadow-md mb-6">
    <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-2 items-end">
        
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Dari Tanggal</label>
            <input type="date" name="start_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Sampai Tanggal</label>
            <input type="date" name="end_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none">
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Tipe Transaksi</label>
            <select name="type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none cursor-pointer">
                <option value="">Semua Tipe</option>
                <option value="in">Stok Masuk (In)</option>
                <option value="out">Stok Keluar (Out)</option>
                <option value="adjustment">Adjustment (Penyesuaian)</option>
            </select>
        </div>

        <div class="flex gap-2">
            <div class="flex-1">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1"><?= $isAdmin ? 'Cari Barang / User' : 'Cari Barang' ?></label>
                <input type="text" name="search" placeholder="Nama barang..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none">
            </div>
            <button type="submit" class="bg-[#092363] text-white px-4 py-2 rounded-lg hover:bg-[#e6b949] hover:text-[#092363] transition-colors h-[38px] mt-auto">
                <i class='bx bx-search'></i>
            </button>
        </div>
    </form>
</div>


<!-- Tabel History -->
<div class="overflow-x-auto bg-white shadow-lg rounded-xl border border-gray-100 mb-6">
    <table class="min-w-full">
        <thead class="bg-[#092363] text-white">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider">Tanggal & Waktu</th>
                <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider">Barang</th>
                <th class="px-6 py-3 text-center text-xs font-bold uppercase tracking-wider">Tipe</th>
                <th class="px-6 py-3 text-center text-xs font-bold uppercase tracking-wider">Jumlah</th>
                <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider">User</th>
                <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider">Location</th>
                <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider">Keterangan / Ref</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-3 text-sm text-gray-600">23 Nov 2025, 10:30</td>
                <td class="px-6 py-3">
                    <p class="font-bold text-gray-800">Laptop Asus ROG</p>
                    <p class="text-xs text-gray-500">SKU: PRD-001</p>
                </td>
                <td class="px-6 py-3 text-center">
                    <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold">IN</span>
                </td>
                <td class="px-6 py-3 text-center font-bold text-green-600">+10</td>
                <td class="px-6 py-3 text-sm text-gray-600">
                <?= $isAdmin ? 'Nama Staff A' : 'Anda' ?>
                </td>
                <td class="px-6 py-3 text-sm text-gray-500">Rak 001</td>
                <td class="px-6 py-3 text-sm text-gray-500">Invoice Masuk #INV-IN-001</td>
            </tr>

            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-3 text-sm text-gray-600">23 Nov 2025, 14:15</td>
                <td class="px-6 py-3">
                    <p class="font-bold text-gray-800">Meja Kantor</p>
                    <p class="text-xs text-gray-500">SKU: FUR-101</p>
                </td>
                <td class="px-6 py-3 text-center">
                    <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs font-bold">OUT</span>
                </td>
                <td class="px-6 py-3 text-center font-bold text-red-600">-2</td>
                <td class="px-6 py-3 text-sm text-gray-600">
                <?= $isAdmin ? 'Nama Staff A' : 'Anda' ?>
                </td>
                <td class="px-6 py-3 text-sm text-gray-500">Rak 001</td>
                <td class="px-6 py-3 text-sm text-gray-500">Invoice Keluar #INV-OUT-005</td>
            </tr>

            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-3 text-sm text-gray-600">22 Nov 2025, 09:00</td>
                <td class="px-6 py-3">
                    <p class="font-bold text-gray-800">Kursi Gaming</p>
                    <p class="text-xs text-gray-500">SKU: FUR-202</p>
                </td>
                <td class="px-6 py-3 text-center">
                    <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-xs font-bold">ADJUST</span>
                </td>
                <td class="px-6 py-3 text-center font-bold text-red-600">-1</td>
                <td class="px-6 py-3 text-sm text-gray-600">
                <?= $isAdmin ? 'Nama Staff A' : 'Anda' ?>
                </td>
                <td class="px-6 py-3 text-sm text-gray-500">Rak 001</td>
                <td class="px-6 py-3 text-sm text-gray-500 italic">Barang rusak saat pengiriman</td>
            </tr>

        </tbody>
    </table>
</div>

<!-- Next menu -->
<div class="flex justify-between items-center px-6 mb-10">
    <span class="text-sm text-gray-500">Showing 1 to 10 of 150 entries</span>
    <div class="flex gap-1">
        <button class="px-3 py-1 rounded border bg-white border-gray-300 text-gray-700 hover:bg-[#e6b949] hover:text-[#092363] text-sm font-bold">Prev</button>
        <button class="px-3 py-1 rounded border border-[#092363] bg-[#092363] text-white text-sm">1</button>
        <button class="px-3 py-1 rounded border border-gray-300 text-gray-500 hover:bg-[#092363] hover:text-white text-sm">2</button>
        <button class="px-3 py-1 rounded border bg-white border-gray-300 text-gray-700 hover:bg-[#e6b949] hover:text-[#092363] text-sm font-bold">Next</button>
    </div>
</div>

<div id="manualStockModal" class="custom-modal">
    <div class="modal-backdrop" onclick="closeModal('manualStockModal')"></div>
    <div class="modal-flex-container">
        <div class="modal-content-box">
            
            <div class="bg-[#092363] px-6 py-4 rounded-t-[16px] flex justify-between items-center">
                <h3 class="text-white text-lg font-bold tracking-wide">Penyesuaian Stok Manual</h3>
                <button onclick="closeModal('manualStockModal')" class="text-white hover:text-[#e6b949] transition-colors text-2xl font-bold cursor-pointer">&times;</button>
            </div>

            <form action="" method="POST" class="p-6 space-y-4">
                
                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Pilih Barang</label>
                    <select name="product_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none cursor-pointer">
                        <option value="">-- Cari Barang --</option>
                        <option value="1">Laptop Asus ROG (Stok: 15)</option>
                        <option value="2">Meja Kantor (Stok: 8)</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Jenis Aksi</label>
                        <select name="type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none cursor-pointer">
                            <option value="in">Tambah Stok (+)</option>
                            <option value="out">Kurangi Stok (-)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Jumlah</label>
                        <input type="number" name="qty" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none" placeholder="0">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Alasan Penyesuaian</label>
                    <textarea name="reason" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none resize-none" placeholder="Contoh: Barang rusak, Bonus supplier, Selisih opname..."></textarea>
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-gray-100 mt-2">
                    <button type="button" onclick="closeModal('manualStockModal')" class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100 transition-all">Batal</button>
                    <button type="submit" style="width: 140px;" class="px-5 py-2 rounded-lg text-sm font-bold text-white bg-[#092363] hover:bg-[#e6b949] hover:text-[#092363] shadow-lg transform hover:-translate-y-0.5 transition-all">Simpan Stok</button>
                </div>
            </form>
        </div>
    </div>
</div>