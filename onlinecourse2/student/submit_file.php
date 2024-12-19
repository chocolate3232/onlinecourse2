<?php
session_start();
require_once('../command/conn.php'); // เชื่อมต่อฐานข้อมูล

// ตรวจสอบว่าผู้ใช้เป็นนักเรียน
if (!isset($_SESSION['member_id'])) {
    echo "<script>alert('กรุณาล็อกอินก่อน'); window.location.href='login.php';</script>";
    exit;
}

$lesson_id = $_POST['lesson_id']; // รับค่า lesson_id จากฟอร์ม
$uploadDirectory = "uploads/"; // กำหนดพาธที่เก็บไฟล์

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fileUpload'])) {
    // รับข้อมูลไฟล์
    $fileName = $_FILES['fileUpload']['name'];
    $fileTmpName = $_FILES['fileUpload']['tmp_name'];
    $fileSize = $_FILES['fileUpload']['size'];
    $fileError = $_FILES['fileUpload']['error'];
    $fileType = $_FILES['fileUpload']['type'];

    // แยกนามสกุลไฟล์
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'zip', 'pdf', 'docx'];

    if (!in_array($fileExt, $allowedExtensions)) {
        echo "<script>alert('ประเภทไฟล์ไม่ถูกต้อง'); window.location.href='student_files.php?lesson_id=" . $lesson_id . "';</script>";
        exit;
    }

    if ($fileError !== 0) {
        echo "<script>alert('เกิดข้อผิดพลาดในการอัปโหลดไฟล์'); window.location.href='student_files.php?lesson_id=" . $lesson_id . "';</script>";
        exit;
    }

    // ตรวจสอบขนาดไฟล์ (เช่น จำกัดไว้ที่ 10MB)
    if ($fileSize > 10 * 1024 * 1024) {  // 10MB
        echo "<script>alert('ไฟล์ขนาดใหญ่เกินไป'); window.location.href='student_files.php?lesson_id=" . $lesson_id . "';</script>";
        exit;
    }


    // สร้างชื่อไฟล์ใหม่เพื่อหลีกเลี่ยงการซ้ำกัน
    $newFileName = uniqid('', true) . '.' . $fileExt;
    $uploadPath = $uploadDirectory . $newFileName;

    if (move_uploaded_file($fileTmpName, $uploadPath)) {
        // บันทึกข้อมูลไฟล์ลงในฐานข้อมูล
        try {
            $student_id = $_SESSION['member_id'];
            $query = $conn->prepare("INSERT INTO tb_uploaded_sd (member_id, file_name, file_path, file_size, file_type, lesson_id, is_student_submission) 
                                     VALUES (:member_id, :file_name, :file_path, :file_size, :file_type, :lesson_id, 1)");
            $query->bindParam(':member_id', $student_id);
            $query->bindParam(':file_name', $fileName);
            $query->bindParam(':file_path', $uploadPath);
            $query->bindParam(':file_size', $fileSize);
            $query->bindParam(':file_type', $fileType);
            $query->bindParam(':lesson_id', $lesson_id); // ส่งค่า lesson_id ที่ได้รับจากฟอร์ม

            $query->execute();

            // รีไดเร็กต์กลับไปที่หน้าการแสดงไฟล์พร้อมกับการแจ้งเตือน
            echo "<script>alert('ส่งไฟล์สำเร็จ'); window.location.href='student_files.php?lesson_id=" . $lesson_id . "';</script>";
            exit;
        } catch (Exception $e) {
            echo "<script>alert('เกิดข้อผิดพลาดในการบันทึกข้อมูลลงฐานข้อมูล'); window.location.href='student_files.php?lesson_id=" . $lesson_id . "';</script>";
        }
    } else {
        echo "<script>alert('เกิดข้อผิดพลาดในการอัปโหลดไฟล์'); window.location.href='student_files.php?lesson_id=" . $lesson_id . "';</script>";
    }
}
?>
