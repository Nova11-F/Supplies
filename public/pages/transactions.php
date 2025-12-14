<?php
$sub = $_GET['sub'] ?? 'purchase';
?>

<!-- TAB MENU -->
<div class="border-b-2 border-[#092363]">

    <div class="flex gap-5 px-8 mt-8">
        <!-- Products -->
        <a href="index.php?page=transactions&sub=purchase"
            class="flex items-center gap-2 pb-2 font-bold text-lg tracking-wide transform duration-300 hover:scale-102 transition-all
           <?= ($sub == 'purchase' ? 'text-[#e6b949] border-b-4 border-[#e6b949] font-extrabold' : 'text-[#092363] hover:text-[#e6b949]'); ?>">
            <i class='bx bx-cart text-xl'></i> Purchase Orders
        </a>

        <!-- Products -->
        <a href="index.php?page=transactions&sub=invoice"
            class="flex items-center gap-2 pb-2 font-bold text-lg tracking-wide transform duration-300 hover:scale-102 transition-all
           <?= ($sub == 'invoice' ? 'text-[#e6b949] border-b-4 border-[#e6b949] font-extrabold' : 'text-[#092363] hover:text-[#e6b949]'); ?>">
            <i class='bx bx-receipt text-xl'></i> Invoice
        </a>

        <!-- Transactions History -->
        <a href="index.php?page=transactions&sub=trans_history"
            class="flex items-center gap-2 pb-2 font-bold text-lg tracking-wide transform duration-300 hover:scale-102 transition-all
           <?= ($sub == 'trans_history' ? 'text-[#e6b949] border-b-4 border-[#e6b949] font-extrabold' : 'text-[#092363] hover:text-[#e6b949]'); ?>">
            <i class='bx bx-time-five text-xl'></i> History
        </a>

    </div>
</div>

<div class="px-8 py-2">

    <!-- SUBPAGE DINAMIS -->
    <div>
        <?php
        $basePath = "public/pages/transactions/";

        if ($sub == 'purchase') {
            include $basePath . "purchase.php";
        } elseif ($sub == 'invoice') {
            include $basePath . "invoice.php";
        } elseif ($sub == 'payment') {
            include $basePath . "payment.php";
        } elseif ($sub == 'payment_callback') {
            include $basePath . "payment_callback.php";
        } elseif ($sub == 'trans_history') {
            include $basePath . "transactions_history.php";
        } else {
            echo "<p>Halaman tidak ditemukan.</p>";
        }
        ?>
    </div>

</div>