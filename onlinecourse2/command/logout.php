<?php
session_start();

// ลบ session ทั้งหมด
session_unset();

// ทำลาย session
session_destroy();

// ลบคุกกี้ที่เก็บข้อมูลล็อกอิน (หากมี)
setcookie('username', '', time() - 3600, '/'); // ตั้งเวลาให้คุกกี้หมดอายุทันที
setcookie('password', '', time() - 3600, '/'); // ตั้งเวลาให้คุกกี้หมดอายุทันที

// ส่งผู้ใช้กลับไปยังหน้า login
header('Location: ../index.php');
exit();
?>