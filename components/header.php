<div class="w-full px-8 pt-8 pb-2 flex justify-between items-start">
    
    <div>
        <!-- Page Title -->
        <h1 class="text-4xl font-bold text-black tracking-wide">
            <?= $pageTitle ?? 'Dashboard' ?>
        </h1>

        <!-- Status User -->
        <p class="text-gray-500 text-sm mt-3">
            <?= date('l, d F Y') ?> &bull; <span class="text-[#092363] font-semibold">Active</span>
        </p>
    </div>

    <div class="relative z-40"> <button onclick="toggleNotification()" 
                id="notifButton"
                class="flex items-center bg-white p-3 rounded-2xl text-[#092363] shadow-lg hover:bg-gray-100 transform duration-300 hover:scale-102 transition-all">
            
            <i class="bx bx-bell text-2xl"></i>
            
            <span class="absolute top-3 right-3 w-2.5 h-2.5 bg-red-500 border-2 border-white rounded-full pointer-events-none"></span>
        </button>

        <div id="notificationDropdown" 
          class="hidden absolute mt-3 w-80 bg-white rounded-xl shadow-2xl border border-gray-100 overflow-hidden origin-top-right transition-all duration-200 transform scale-95 opacity-0"
          style="right: 0; left: auto; z-index: 50;"> <div class="bg-[#092363] px-4 py-3 flex justify-between items-center" style="border-top-left-radius: 0.75rem; border-top-right-radius: 0.75rem;">
              <h3 class="text-white font-bold text-sm">Notifications</h3>
              <span class="bg-red-500 text-white text-[10px] px-2 py-0.5 rounded-xl">3 New</span>
          </div>

          <div class="max-h-64 overflow-y-auto">
              
              <a href="#" class="flex items-start gap-3 p-3 hover:bg-gray-50 border-b border-gray-100 transition-colors group">
                  <div class="bg-red-100 text-red-600 p-2 rounded-full shrink-0 group-hover:bg-red-200">
                      <i class='bx bx-error-circle text-xl'></i>
                  </div>
                  <div>
                      <p class="text-sm font-bold text-gray-800">Stok Menipis!</p>
                      <p class="text-xs text-gray-500 mt-0.5">Laptop Asus ROG sisa 2 unit.</p>
                      <span class="text-[10px] text-gray-400 mt-1 block">2 mins ago</span>
                  </div>
              </a>

              <a href="#" class="flex items-start gap-3 p-3 hover:bg-gray-50 border-b border-gray-100 transition-colors group">
                  <div class="bg-blue-100 text-blue-600 p-2 rounded-full shrink-0 group-hover:bg-blue-200">
                      <i class='bx bx-cart-alt text-xl'></i>
                  </div>
                  <div>
                      <p class="text-sm font-bold text-gray-800">Order Baru</p>
                      <p class="text-xs text-gray-500 mt-0.5">PO-2025-001 menunggu approval.</p>
                      <span class="text-[10px] text-gray-400 mt-1 block">1 hour ago</span>
                  </div>
              </a>

          </div>

          <div class="bg-gray-50 p-2 text-center border-t border-gray-100">
              <a href="#" class="text-xs text-[#092363] font-bold hover:underline">Mark all as read</a>
          </div>
      </div>
    </div>

</div>


