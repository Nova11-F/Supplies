<?php 
$sub = $_GET['sub'] ?? 'user'; 
?>

    <!-- TAB MENU -->
    <div class="border-b-2 border-[#092363] ">

    <div class="flex gap-5 px-8 mt-8">
    <!-- Create User -->
        <a href="index.php?page=usermanagement&sub=user" 
           class="flex items-center gap-2 pb-2 font-bold text-lg tracking-wide transform duration-300 hover:scale-102 transition-all
           <?= ($sub == 'user' ? 'text-[#e6b949] border-b-4 border-[#e6b949] font-extrabold' : 'text-[#092363] hover:text-[#e6b949]'); ?>">
           <i class="bx bx-user-plus text-xl"></i>Create User
        </a>

    <!-- Activity Log -->
        <a href="index.php?page=usermanagement&sub=activity_log" 
           class="flex items-center gap-2 pb-2 font-bold text-lg tracking-wide transform duration-300 hover:scale-102 transition-all
           <?= ($sub == 'activity_log' ? 'text-[#e6b949] border-b-4 border-[#e6b949] font-extrabold' : 'text-[#092363] hover:text-[#e6b949]'); ?>">
           <i class="bx bx-history text-xl"></i>Activity Log
        </a>

    <!-- Permissions -->
        <a href="index.php?page=usermanagement&sub=permissions" 
           class="flex items-center gap-2 pb-2 font-bold text-lg tracking-wide transform duration-300 hover:scale-102 transition-all
           <?= ($sub == 'permissions' ? 'text-[#e6b949] border-b-4 border-[#e6b949] font-extrabold' : 'text-[#092363] hover:text-[#e6b949]'); ?>">
           <i class='bx bx-lock-alt text-xl'></i>Permissions
        </a>

    </div>
    </div>

    <div class="px-8 py-2">

    <!-- SUBPAGE DINAMIS -->
    <div>
        <?php 
            $basePath = "public/pages/user/";

            if ($sub == 'user') {
                include $basePath . "user.php";
            } 
            elseif ($sub == 'activity_log') {
                include $basePath . "activity_log.php";
            } 
            elseif ($sub == 'permissions') {
                include $basePath . "permissions.php";
            } 
            else {
                echo "<p>Halaman tidak ditemukan.</p>";
            }
        ?>
    </div>
</div>
