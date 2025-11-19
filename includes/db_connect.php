<?php
// --- เรียกใช้ไฟล์ Config ---
require_once __DIR__ . '/../config.php';

// --- เริ่มการเชื่อมต่อ ---
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// --- ตรวจสอบการเชื่อมต่อ ---
if ($conn->connect_error) {
    die("เชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

// --- ตั้งค่าให้รองรับภาษาไทย ---
$conn->set_charset("utf8mb4");

// ถ้าเชื่อมต่อสำเร็จ $conn จะถูกนำไปใช้ในไฟล์อื่น
?>