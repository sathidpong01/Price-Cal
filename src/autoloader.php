<?php

spl_autoload_register(function ($class_name) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/src/';
    // ตรวจสอบว่า class อยู่ใน namespace App\
    $len = strlen($prefix);
    if (strncmp($prefix, $class_name, $len) !== 0) {
        return;
    }
    // ตัด prefix ออก แล้วแปลง \ เป็น /
    $relative_class = substr($class_name, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

?> 