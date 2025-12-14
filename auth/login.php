<?php
session_start();
include '../config/database.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

  $username = trim($_POST['username']);
  $password = trim($_POST['password']);

  // Query user
  $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $result = $stmt->get_result();

  $user = $result->fetch_assoc();

  if ($user) {

    // cek password hash
    if (password_verify($password, $user['password'])) {

      // cek status aktif
      if ($user['status'] !== 'active') {
        $error = "Akun anda tidak aktif!";
      } else {

        // buat session
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['fullname']  = $user['full_name'];
        $_SESSION['role']      = $user['role'];

        header("Location: ../index.php?page=dashboard");
        exit;
      }
    } else {
      $error = "Password salah!";
    }
  } else {
    $error = "Username tidak ditemukan!";
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Supply Management</title>
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="../public/assets/css/output.css" />
</head>

<body class="flex h-screen">

  <div class="flex-1 flex items-center justify-center p-10 bg-[#e6b949]">
    <div class="bg-white w-full max-w-md p-8 rounded-xl shadow-xl">
      <h1 class="text-2xl text-[#e6b949] font-bold mb-6 text-center">LOGIN</h1>
      
          <?php if($error): ?>
            <div class="bg-red-100 text-red-600 p-3 rounded mb-4 text-sm text-center">
                <?= $error ?>
            </div>
          <?php endif; ?>

      <form class="space-y-6" method="POST">
        <div class="relative">
          <input type="text" id="username" name="username" required
            class="peer w-full border-b-2 border-gray-300 bg-transparent py-2 focus:outline-none focus:border-[#092363] transition-colors" />
          <label for="username" class="absolute left-0 top-2 text-gray-500 transition-all font-medium peer-focus:text-sm peer-focus:-top-3 peer-focus:text-[#092363] peer-valid:text-sm peer-valid:-top-3">
            Username
          </label>
        </div>

        <div class="relative">
          <input type="password" id="password" name="password" required
            class="peer w-full border-b-2 border-gray-300 bg-transparent py-2 focus:outline-none focus:border-[#092363]" />
          <label for="password" class="absolute left-0 top-2 text-gray-500 transition-all font-medium peer-focus:text-sm peer-focus:-top-3 peer-focus:text-[#092363] peer-valid:text-sm peer-valid:-top-3">
            Password
          </label>
        </div>

        <button type="submit" class="w-full bg-[#092363] text-[#e6b949] py-2 text-md font-bold rounded-lg shadow-lg hover:bg-[#e6b949] hover:text-[#092363] transition-transform hover:scale-102 duration-200">
          LOGIN
        </button>
      </form>

      <div class="mt-4 text-xs text-gray-400 text-center">
        Admin: admin / admin123 <br>
        Staff: staff / staff123
      </div>
    </div>
  </div>

  <div class="flex-1 bg-[#092363] flex flex-col justify-center items-center text-white p-10">
    <div class="flex justify-center items-center w-80 h-40 mb-1">
      <img src="../public/assets/images/supply_management-logo-removebg-preview.png" alt="Logo" class="w-full object-contain" />
    </div>
    <h2 class="text-4xl font-bold mb-4 text-shadow-lg">Welcome back!</h2>
    <p class="text-lg text-center max-w-md">Manage your supplies efficiently and effectively with our system.</p>
  </div>

</body>

</html>