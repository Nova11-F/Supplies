<div class="mb-4 mt-3">
    <h1 class="text-2xl px-4 font-bold text-[#092363]">System Activity Logs</h1>
    <p class="px-4 text-gray-500 text-sm">Rekaman jejak aktivitas semua pengguna dalam sistem.</p>
</div>

<div class="bg-white p-4 rounded-xl shadow-lg mb-6">
    <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Dari Tanggal</label>
            <input type="date" name="start_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none">
        </div>
        
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">User / Staff</label>
            <select name="user_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none cursor-pointer">
                <option value="">Semua User</option>
                <option value="1">Admin Utama</option>
                <option value="2">Staff Gudang A</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Jenis Aktivitas</label>
            <select name="action" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none cursor-pointer">
                <option value="">Semua Aktivitas</option>
                <option value="create">Create (Tambah Data)</option>
                <option value="update">Update (Edit Data)</option>
                <option value="delete">Delete (Hapus Data)</option>
                <option value="login">Login / Logout</option>
            </select>
        </div>

        <button type="submit" class="bg-[#092363] font-bold text-white px-6 py-2 rounded-lg flex justify-center items-center gap-2 hover:bg-[#e6b949] hover:text-[#092363] transform duration-300 hover:scale-102 transition-all">
            <i class='bx bx-filter-alt'></i> Filter Log
        </button>
    </form>
</div>

<div class="overflow-x-auto bg-white shadow-md rounded-md mb-6">
    <table class="min-w-full">
        <thead class="bg-[#092363] text-white">
            <tr>
                <th class="px-6 py-3 text-center text-xs font-bold uppercase tracking-wider w-48">Waktu</th>
                <th class="px-6 py-3 text-center text-xs font-bold uppercase tracking-wider">User</th>
                <th class="px-6 py-3 text-center text-xs font-bold uppercase tracking-wider">Menu</th>
                <th class="px-6 py-3 text-center text-xs font-bold uppercase tracking-wider">Aksi</th>
                <th class="px-6 py-3 text-center text-xs font-bold uppercase tracking-wider">Deskripsi Detail</th>
                <th class="px-6 py-3 text-center text-xs font-bold uppercase tracking-wider">IP Address</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-3 text-sm text-gray-500">
                    23 Nov 2025<br><span class="text-xs text-gray-400">14:30:45</span>
                </td>
                <td class="px-6 py-3">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold">A</div>
                        <span class="text-sm font-bold text-gray-700">Admin</span>
                    </div>
                </td>
                <td class="px-6 py-3 text-sm text-gray-600">Inventory</td>
                <td class="px-6 py-3 text-center">
                    <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold border border-green-200">CREATE</span>
                </td>
                <td class="px-6 py-3 text-sm text-gray-600">
                    Menambahkan item baru: <span class="font-bold">"Printer Epson L3210"</span>
                </td>
                <td class="px-6 py-3 text-right text-xs text-gray-400 font-mono">192.168.1.10</td>
            </tr>

            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-3 text-sm text-gray-500">
                    23 Nov 2025<br><span class="text-xs text-gray-400">13:15:20</span>
                </td>
                <td class="px-6 py-3">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center text-xs font-bold">B</div>
                        <span class="text-sm font-bold text-gray-700">Budi (Staff)</span>
                    </div>
                </td>
                <td class="px-6 py-3 text-sm text-gray-600">Supplier</td>
                <td class="px-6 py-3 text-center">
                    <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs font-bold border border-blue-200">UPDATE</span>
                </td>
                <td class="px-6 py-3 text-sm text-gray-600">
                    Mengubah No. Telp Supplier <span class="font-bold">PT. Teknologi Maju</span>
                </td>
                <td class="px-6 py-3 text-right text-xs text-gray-400 font-mono">192.168.1.12</td>
            </tr>

            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-3 text-sm text-gray-500">
                    22 Nov 2025<br><span class="text-xs text-gray-400">09:00:00</span>
                </td>
                <td class="px-6 py-3">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-full bg-red-100 text-red-600 flex items-center justify-center text-xs font-bold">A</div>
                        <span class="text-sm font-bold text-gray-700">Admin</span>
                    </div>
                </td>
                <td class="px-6 py-3 text-sm text-gray-600">User Management</td>
                <td class="px-6 py-3 text-center">
                    <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs font-bold border border-red-200">DELETE</span>
                </td>
                <td class="px-6 py-3 text-sm text-gray-600">
                    Menghapus user <span class="font-bold text-red-500">staff_magang_01</span>
                </td>
                <td class="px-6 py-3 text-right text-xs text-gray-400 font-mono">192.168.1.10</td>
            </tr>

            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-3 text-sm text-gray-500">
                    22 Nov 2025<br><span class="text-xs text-gray-400">08:00:05</span>
                </td>
                <td class="px-6 py-3">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center text-xs font-bold">B</div>
                        <span class="text-sm font-bold text-gray-700">Budi (Staff)</span>
                    </div>
                </td>
                <td class="px-6 py-3 text-sm text-gray-600">Auth</td>
                <td class="px-6 py-3 text-center">
                    <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs font-bold border border-gray-200">LOGIN</span>
                </td>
                <td class="px-6 py-3 text-sm text-gray-600">
                    Login berhasil ke sistem.
                </td>
                <td class="px-6 py-3 text-right text-xs text-gray-400 font-mono">192.168.1.12</td>
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