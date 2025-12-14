<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: auth/login.php");
    exit;
}

// Dapatkan role user
$role = $_SESSION['role'] ?? 'staff';
$isAdmin = ($role === 'admin');
$isStaff = ($role === 'staff');

// Buat base URL otomatis
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$path = dirname($_SERVER['SCRIPT_NAME']);
$baseUrl = $protocol . "://" . $host . $path . "/";

$activePage = $_GET['page'] ?? 'dashboard';

// Daftar judul halaman berdasarkan nama file
$pageTitleMap = [
    'dashboard' => 'Dashboard',
    'inventory' => 'Inventory',
    'supplier' => 'Supplier',
    'customer' => 'Customer',
    'catalog' => 'Catalog',
    'transactions' => 'Transactions',
    'reports' => 'Reports',
    'usermanagement' => 'User & Access Management',
    'settings' => 'Settings',
    'help' => 'Help & Support',
    'history' => 'History'
];

$pageTitle = $pageTitleMap[$activePage] ?? 'Untitled Page';


// File path untuk halaman konten
$pageFile = "public/pages/{$activePage}.php";
if (!file_exists($pageFile)) {
    $pageFile = "public/pages/dashboard.php";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>

    <!-- Box Icon -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />

    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="<?= $baseUrl ?>public/assets/css/output.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>public/assets/css/style.css">

</head>

<body class="bg-gray-200 flex">

    <!-- Side Bar -->
    <?php include 'components/sidebar.php'; ?>

    <!-- Content Halaman -->
    <div class="w-full ml-80 flex-1">

        <!-- Header -->
        <div>
            <?php include 'components/header.php'; ?>
        </div>

        <!-- Content -->
        <div>
            <?php include $pageFile; ?>
        </div>
    </div>

    <script src="<?= $baseUrl ?>public/assets/js/main.js"></script>
    <script src="<?= $baseUrl ?>public/assets/js/theme.js"></script>
</body>

</html>