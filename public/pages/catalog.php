<?php 
$sub = $_GET['sub'] ?? 'products'; 
?>

    <!-- TAB MENU -->
    <div class="border-b-2 border-[#092363] ">

    <div class="flex gap-5 px-8 mt-8">
        <!-- Products -->
        <a href="index.php?page=catalog&sub=products"
           class="flex items-center gap-2 pb-2 font-bold text-lg tracking-wide transform duration-300 hover:scale-102 transition-all
           <?= ($sub == 'products' ? 'text-[#e6b949] border-b-4 border-[#e6b949] font-extrabold' : 'text-[#092363] hover:text-[#e6b949]'); ?>">
            <i class='bx bx-cube text-xl'></i> Products
        </a>

        <!-- Categories -->
        <a href="index.php?page=catalog&sub=categories"
           class="flex items-center gap-2 pb-2 font-bold text-lg tracking-wide transform duration-300 hover:scale-102 transition-all
           <?= ($sub == 'categories' ? 'text-[#e6b949] border-b-4 border-[#e6b949] font-extrabold' : 'text-[#092363] hover:text-[#e6b949]'); ?>">
            <i class='bx bx-category text-xl'></i> Categories
        </a>

        <!-- Locations -->
        <a href="index.php?page=catalog&sub=locations"
           class="flex items-center gap-2 pb-2 font-bold text-lg tracking-wide transform duration-300 hover:scale-102 transition-all
           <?= ($sub == 'locations' ? 'text-[#e6b949] border-b-4 border-[#e6b949] font-extrabold' : 'text-[#092363] hover:text-[#e6b949]'); ?>">
            <i class='bx bx-map text-xl'></i> Locations
        </a>

        <!-- Brands -->
        <a href="index.php?page=catalog&sub=brands"
           class="flex items-center gap-2 pb-2 font-bold text-lg tracking-wide transform duration-300 hover:scale-102 transition-all
           <?= ($sub == 'brands' ? 'text-[#e6b949] border-b-4 border-[#e6b949] font-extrabold' : 'text-[#092363] hover:text-[#e6b949]'); ?>">
            <i class='bx bx-purchase-tag text-xl'></i> Brands
        </a>

        </div>
    </div>

    <div class="px-8 py-2">

    <!-- SUBPAGE DINAMIS -->
    <div>
        <?php 
            $basePath = "public/pages/catalog/";

            if ($sub == 'products') {
                include $basePath . "products.php";
            } 
            elseif ($sub == 'categories') {
                include $basePath . "categories.php";
            } 
            elseif ($sub == 'locations') {
                include $basePath . "locations.php";
            } 
            elseif ($sub == 'brands') {
                include $basePath . "brands.php";
            } 
            else {
                echo "<p>Halaman tidak ditemukan.</p>";
            }
        ?>
    </div>

    </div>

