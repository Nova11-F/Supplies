<?php
// Load Midtrans Library
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Konfigurasi Midtrans
\Midtrans\Config::$serverKey = 'Mid-server-6-G7RtdBSGM6iybd8HEelD3v';
\Midtrans\Config::$clientKey = 'Mid-client-ztDT35obbAWdKlys';
\Midtrans\Config::$isProduction = false; // false = sandbox/testing, true = production/live
\Midtrans\Config::$isSanitized = true;   // Sanitize input
\Midtrans\Config::$is3ds = true;         // Enable 3D Secure