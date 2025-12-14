<?php 
$sub = $_GET['sub'] ?? 'list'; 
?>

    <!-- TAB MENU -->
    <div class="border-b-2 border-gray-500 ">

    <div class="flex gap-5 px-8 mt-8">
        <!-- Stock List -->
        <a href="index.php?page=inventory&sub=list"
           class="flex items-center gap-2 pb-2 font-bold text-lg tracking-wide
           <?= ($sub == 'list' ? 'text-[#e6b949] border-b-4 border-[#e6b949] font-extrabold' : 'text-[#092363]'); ?>">
            <i class='bx bx-list-ul text-xl'></i> Stock List
        </a>

        <!-- Stock Out -->
        <a href="index.php?page=inventory&sub=out"
           class="flex items-center gap-2 pb-2 font-bold text-lg tracking-wide transform duration-300 hover:scale-102 transition-all
           <?= ($sub == 'out' ? 'text-[#e6b949] border-b-4 border-[#e6b949] font-extrabold' : 'text-[#092363] hover:text-[#e6b949]'); ?>">
            <i class='bx bx-log-out text-xl'></i> Stock Out
        </a>

        <!-- Stock Adjusment -->
        <a href="index.php?page=inventory&sub=adjusment"
           class="flex items-center gap-2 pb-2 font-bold text-lg tracking-wide transform duration-300 hover:scale-102 transition-all
           <?= ($sub == 'adjusment' ? 'text-[#e6b949] border-b-4 border-[#e6b949] font-extrabold' : 'text-[#092363] hover:text-[#e6b949]'); ?>">
            <i class='bx bx-edit text-xl'></i>Adjusment
        </a>

        <!-- Stock Transfer -->
        <a href="index.php?page=inventory&sub=transfer"
           class="flex items-center gap-2 pb-2 font-bold text-lg tracking-wide transform duration-300 hover:scale-102 transition-all
           <?= ($sub == 'transfer' ? 'text-[#e6b949] border-b-4 border-[#e6b949] font-extrabold' : 'text-[#092363] hover:text-[#e6b949]'); ?>">
            <i class='bx bx-transfer text-xl'></i>Transfer
        </a>

        <!-- Inventory History -->
        <a href="index.php?page=inventory&sub=inven_history"
           class="flex items-center gap-2 pb-2 font-bold text-lg tracking-wide transform duration-300 hover:scale-102 transition-all
           <?= ($sub == 'inven_history' ? 'text-[#e6b949] border-b-4 border-[#e6b949] font-extrabold' : 'text-[#092363] hover:text-[#e6b949]'); ?>">
            <i class='bx bx-history text-xl'></i>History
        </a>

    </div>
    </div>

    <div class="px-8 py-2">
    <!-- SUBPAGE DINAMIS -->
    <div>
        <?php 
            $basePath = "public/pages/inventory/";

            if ($sub == 'list') {
                include $basePath . "stock_list.php";
            } 
            elseif ($sub == 'out') {
                include $basePath . "stock_out.php";
            } 
            elseif ($sub == 'adjusment') {
                include $basePath . "stock_adjusment.php";
            }
            elseif ($sub == 'transfer') {
                include $basePath . "stock_transfer.php";
            } 
            elseif ($sub == 'inven_history') {
                include $basePath . "inventory_history.php";
            } 
            else {
                echo "<p>Halaman tidak ditemukan.</p>";
            }
        ?>
    </div>

    </div>


