<?php
// --- ตั้งค่าการเชื่อมต่อ ---
$db_host = "localhost"; // หรือ 127.0.0.1
$db_user = "root";      // ชื่อผู้ใช้ที่เราเข้าระบบ phpMyAdmin
$db_pass = "Supergodball2540-"; // *** แก้ไขตรงนี้! ใส่รหัสผ่าน MariaDB ที่คุณตั้งไว้ ***
$db_name = "price_calculator"; // ชื่อฐานข้อมูลที่เราสร้าง

// --- เริ่มการเชื่อมต่อ ---
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// --- ตรวจสอบการเชื่อมต่อ ---
if ($conn->connect_error) {
    die("เชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

// --- ตั้งค่าให้รองรับภาษาไทย ---
$conn->set_charset("utf8mb4");

// ถ้าเชื่อมต่อสำเร็จ $conn จะถูกนำไปใช้ในไฟล์อื่น
?>