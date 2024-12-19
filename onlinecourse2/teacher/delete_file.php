<?php
session_start();
require_once('../command/conn.php');

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
if (!isset($_SESSION['member_id'])) {
    echo "<script>alert('กรุณาล็อกอินก่อน'); window.location.href='login.php';</script>";
    exit;
}

// ตรวจสอบว่ามีการส่ง file_id และ lesson_id มาหรือไม่
if (isset($_GET['file_id']) && isset($_GET['lesson_id'])) {
    $fileId = $_GET['file_id'];
    $lessonId = $_GET['lesson_id'];

    // ดึงข้อมูลไฟล์จากฐานข้อมูล
    $query = $conn->prepare("SELECT * FROM tb_uploaded_files WHERE id = :file_id AND member_id = :member_id");
    $query->bindParam(':file_id', $fileId, PDO::PARAM_INT);
    $query->bindParam(':member_id', $_SESSION['member_id'], PDO::PARAM_INT);
    $query->execute();
    $file = $query->fetch(PDO::FETCH_ASSOC);

    if ($file) {
        // ลบไฟล์จากเซิร์ฟเวอร์
        if (file_exists($file['file_path'])) {
            unlink($file['file_path']);
        }

        // ลบข้อมูลจากฐานข้อมูล
        $deleteQuery = $conn->prepare("DELETE FROM tb_uploaded_files WHERE id = :file_id");
        $deleteQuery->bindParam(':file_id', $fileId, PDO::PARAM_INT);
        if ($deleteQuery->execute()) {
            // รีไดเร็กต์ไปยังหน้าไฟล์พร้อมกับ lesson_id และ timestamp เพื่อป้องกันแคช
            header("Location: file.php?lesson_id=" . $lessonId . "&" . time());
            exit;
        } else {
            echo "<script>alert('เกิดข้อผิดพลาดในการลบไฟล์'); window.location.href='file.php';</script>";
        }
    } else {
        echo "<script>alert('ไม่พบไฟล์ที่ต้องการลบ'); window.location.href='file.php';</script>";
    }
} else {
    echo "<script>alert('ไม่มีข้อมูลไฟล์ที่ต้องการลบ'); window.location.href='file.php';</script>";
}
?>
