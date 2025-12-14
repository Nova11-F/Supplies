<div class="w-80 bg-[#092363] fixed top-0 left-0 z-50 flex flex-col shadow-2xl overflow-hidden" style="height: 100vh;">

    <div class="flex justify-center items-center w-40 h-auto my-4 mx-auto">
        <img src="<?= $baseUrl ?? '' ?>public/assets/images/supply_management-logo-removebg-preview.png" 
             alt="Logo" 
             class="w-full object-contain"/>
    </div>

    <div>
        <a href="index.php?page=dashboard"
           class="flex items-center gap-5 text-lg px-4 py-2 transition-all duration-200
           <?= ($activePage == 'dashboard') 
             ? 'bg-[#e6b949] text-[#092363] font-bold' 
             : 'text-[#e6b949] font-medium hover:bg-[#e6b949] hover:text-[#092363]' ?>">
            <i class="<?= ($activePage == 'dashboard') ? 'bx bxs-bar-chart-square' : 'bx bx-bar-chart-square' ?>"></i>
            Dashboard
        </a>
    </div>

    <div>
        <a href="index.php?page=inventory"
           class="flex items-center gap-5 text-lg px-4 py-2 transition-all duration-200
           <?= ($activePage == 'inventory') 
             ? 'bg-[#e6b949] text-[#092363] font-bold' 
             : 'text-[#e6b949] font-medium hover:bg-[#e6b949] hover:text-[#092363]' ?>">
            <i class="<?= ($activePage == 'inventory') ? 'bx bxs-package' : 'bx bx-package' ?>"></i> 
            Inventory
        </a>
    </div>

    <div>
        <a href="index.php?page=supplier"
           class="flex items-center gap-5 text-lg px-4 py-2 transition-all duration-200
           <?= ($activePage == 'supplier') 
             ? 'bg-[#e6b949] text-[#092363] font-bold' 
             : 'text-[#e6b949] font-medium hover:bg-[#e6b949] hover:text-[#092363]' ?>">
            <i class="<?= ($activePage == 'supplier') ? 'bx bxs-buildings' : 'bx bx-buildings' ?>"></i> 
            Supplier
        </a>
    </div>

    <div>
        <a href="index.php?page=customer"
           class="flex items-center gap-5 text-lg px-4 py-2 transition-all duration-200
           <?= ($activePage == 'customer') 
             ? 'bg-[#e6b949] text-[#092363] font-bold' 
             : 'text-[#e6b949] font-medium hover:bg-[#e6b949] hover:text-[#092363]' ?>">
            <i class="<?= ($activePage == 'customer') ? 'bx bxs-user' : 'bx bx-user' ?>"></i> 
            Customer
        </a>
    </div>

    <div>
        <a href="index.php?page=transactions"
            class="flex items-center gap-5 text-lg px-4 py-2 transition-all duration-200
            <?= ($activePage == 'transactions') 
                ? 'bg-[#e6b949] text-[#092363] font-bold' 
                : 'text-[#e6b949] font-medium hover:bg-[#e6b949] hover:text-[#092363]' ?>">
                <i class="<?= ($activePage == 'transactions') ? 'bx bxs-credit-card' : 'bx bx-credit-card' ?>"></i> 
                Transactions
        </a>
    </div>

        <div>
        <a href="index.php?page=catalog"
        class="flex items-center gap-5 text-lg px-4 py-2 transition-all duration-200
        <?= ($activePage == 'catalog') 
                ? 'bg-[#e6b949] text-[#092363] font-bold' 
                : 'text-[#e6b949] font-medium hover:bg-[#e6b949] hover:text-[#092363]' ?>">
            <i class="<?= ($activePage == 'catalog') ? 'bx bxs-folder-open' : 'bx bx-folder-open' ?>"></i> 
            Catalog
        </a>
    </div>
    
    <?php if ($isAdmin): ?>

    <div>
        <a href="index.php?page=usermanagement"
        class="flex items-center gap-5 text-lg px-4 py-2 transition-all duration-200
            <?= ($activePage == 'usermanagement') 
                ? 'bg-[#e6b949] text-[#092363] font-bold' 
                : 'text-[#e6b949] font-medium hover:bg-[#e6b949] hover:text-[#092363]' ?>">
                <i class="<?= ($activePage == 'usermanagement') ? 'bx bxs-lock' : 'bx bx-lock' ?>"></i> 
                User & Access Management
            </a>
    </div>
        
    <div>
        <a href="index.php?page=reports"
        class="flex items-center gap-5 text-lg px-4 py-2 transition-all duration-200
        <?= ($activePage == 'reports') 
            ? 'bg-[#e6b949] text-[#092363] font-bold' 
            : 'text-[#e6b949] font-medium hover:bg-[#e6b949] hover:text-[#092363]' ?>">
            <i class="<?= ($activePage == 'reports') ? 'bx bxs-report' : 'bx bx-file' ?>"></i> 
            Reports
        </a>
    </div>

    <?php endif; ?>
    
    <div>
        <a href="index.php?page=help"
           class="flex items-center gap-5 text-lg px-4 py-2 transition-all duration-200
           <?= ($activePage == 'help') 
             ? 'bg-[#e6b949] text-[#092363] font-bold' 
             : 'text-[#e6b949] font-medium hover:bg-[#e6b949] hover:text-[#092363]' ?>">
            <i class="<?= ($activePage == 'help') ? 'bx bxs-help-circle' : 'bx bx-help-circle' ?>"></i>
            Help & Support
        </a>
    </div>
    
    <div>
        <a href="index.php?page=settings"
           class="flex items-center gap-5 text-lg px-4 py-2 transition-all duration-200
           <?= ($activePage == 'settings') 
             ? 'bg-[#e6b949] text-[#092363] font-bold' 
             : 'text-[#e6b949] font-medium hover:bg-[#e6b949] hover:text-[#092363]' ?>">
            <i class="<?= ($activePage == 'settings') ? 'bx bxs-cog' : 'bx bx-cog' ?>"></i>
            Settings
        </a>
    </div>

    <div class="mt-10">
        <a href="auth/logout.php"
        class="flex items-center gap-5 text-red-600 text-lg font-medium hover:bg-red-600 hover:text-white px-4 py-2 transition-all duration-200">
            <i class="bx bx-exit"></i>
            Logout
        </a>
    </div>
</div>