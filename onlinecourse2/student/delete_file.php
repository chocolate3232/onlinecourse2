<?php
session_start();
require_once('../command/conn.php');

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้ว
if (!isset($_SESSION['member_id'])) {
    echo "<script>alert('กรุณาล็อกอินก่อน'); window.location.href='login.php';</script>";
    exit;
}

// ตรวจสอบว่า file_id และ lesson_id ถูกส่งมาจากฟอร์มหรือไม่
if (isset($_POST['file_id']) && isset($_POST['lesson_id'])) {
    // รับค่า file_id และ lesson_id จาก POST
    $fileId = $_POST['file_id'];
    $lessonId = $_POST['lesson_id'];

    // ตรวจสอบว่า file_id เป็นตัวเลข
    if (!filter_var($fileId, FILTER_VALIDATE_INT)) {
        echo "<script>alert('ไฟล์ไม่ถูกต้อง'); window.location.href='student_files.php?lesson_id=" . $lessonId . "';</script>";
        exit;
    }

    // ดึงข้อมูลไฟล์จากฐานข้อมูล
    $selectQuery = $conn->prepare("SELECT file_path FROM tb_uploaded_sd WHERE id = :file_id AND member_id = :member_id");
    $selectQuery->bindParam(':file_id', $fileId, PDO::PARAM_INT);
    $selectQuery->bindParam(':member_id', $_SESSION['member_id'], PDO::PARAM_INT);
    $selectQuery->execute();
    $file = $selectQuery->fetch(PDO::FETCH_ASSOC);

    if ($file) {
        // ลบไฟล์จากโฟลเดอร์
        $filePath = $file['file_path'];
        if (file_exists($filePath)) {
            if (unlink($filePath)) {
                // ลบข้อมูลจากฐานข้อมูล
                $deleteQuery = $conn->prepare("DELETE FROM tb_uploaded_sd WHERE id = :file_id AND member_id = :member_id");
                $deleteQuery->bindParam(':file_id', $fileId, PDO::PARAM_INT);
                $deleteQuery->bindParam(':member_id', $_SESSION['member_id'], PDO::PARAM_INT);

                if ($deleteQuery->execute()) {
                    echo "<script>alert('ไฟล์ถูกลบสำเร็จ'); window.location.href='student_files.php?lesson_id=" . $lessonId . "';</script>";
                } else {
                    echo "<script>alert('เกิดข้อผิดพลาดในการลบข้อมูลในฐานข้อมูล'); window.location.href='student_files.php?lesson_id=" . $lessonId . "';</script>";
                }
            } else {
                echo "<script>alert('ไม่สามารถลบไฟล์ในโฟลเดอร์ได้'); window.location.href='student_files.php?lesson_id=" . $lessonId . "';</script>";
            }
        } else {
            echo "<script>alert('ไม่พบไฟล์ในโฟลเดอร์'); window.location.href='student_files.php?lesson_id=" . $lessonId . "';</script>";
        }
    } else {
        echo "<script>alert('ไม่พบข้อมูลไฟล์ในฐานข้อมูล'); window.location.href='student_files.php?lesson_id=" . $lessonId . "';</script>";
    }
} else {
    echo "<script>alert('กรุณาระบุ file_id และ lesson_id'); window.location.href='student_files.php';</script>";
}
?>
