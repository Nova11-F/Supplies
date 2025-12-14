<?php
$host = "localhost";
$user = "root";
$pass = "Nova2811!";
$db   = "supply_management";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

$conn->set_charset("utf8");
