<?php
session_start();
require_once('../command/conn.php'); // เชื่อมต่อฐานข้อมูล

// กำหนดพาธที่จัดเก็บไฟล์ที่อัปโหลด
$uploadDirectory = "uploads/";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fileUpload'])) {
    // รับค่าจากฟอร์ม
    $lesson_id = $_POST['lesson_id']; // รับค่า lesson_id
    $subject_id = $_POST['subject_id']; // รับค่า subject_id
    $fileName = $_FILES['fileUpload']['name'];
    $fileTmpName = $_FILES['fileUpload']['tmp_name'];
    $fileSize = $_FILES['fileUpload']['size'];
    $fileError = $_FILES['fileUpload']['error'];
    $fileType = $_FILES['fileUpload']['type'];

    // แยกนามสกุลไฟล์
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedExtensions = [ 'zip', 'pdf', 'docx'];

    if (!in_array($fileExt, $allowedExtensions)) {
        echo "<script>alert('ประเภทไฟล์ไม่ถูกต้อง'); window.location.href='file.php?lesson_id=" . $lesson_id . "';</script>";
        exit;
    }

    if ($fileError !== 0) {
        echo "<script>alert('เกิดข้อผิดพลาดในการอัปโหลดไฟล์'); window.location.href='file.php';</script>";
        exit;
    }

    // ตรวจสอบขนาดไฟล์ (เช่น จำกัดไว้ที่ 10MB)
    if ($fileSize > 10 * 1024 * 1024) {  // 10MB
        echo "<script>alert('ไฟล์มีขนาดใหญ่เกินไป'); window.location.href='file.php?lesson_id=" . $lesson_id . "';</script>";
        exit;
    }

    // สร้างชื่อไฟล์ใหม่เพื่อหลีกเลี่ยงการซ้ำกัน
    $newFileName = uniqid('', true) . '.' . $fileExt;
    $uploadPath = $uploadDirectory . $newFileName;

    if (move_uploaded_file($fileTmpName, $uploadPath)) {
        // บันทึกข้อมูลไฟล์ลงในฐานข้อมูล
        try {
            $member_id = $_SESSION['member_id'];
            $query = $conn->prepare("INSERT INTO tb_uploaded_files (member_id, file_name, file_path, file_size, file_type, lesson_id, subject_id) 
                                     VALUES (:member_id, :file_name, :file_path, :file_size, :file_type, :lesson_id, :subject_id)");
            $query->bindParam(':member_id', $member_id);
            $query->bindParam(':file_name', $fileName);
            $query->bindParam(':file_path', $uploadPath);
            $query->bindParam(':file_size', $fileSize);
            $query->bindParam(':file_type', $fileType);
            $query->bindParam(':lesson_id', $lesson_id); // ส่งค่า lesson_id ที่ได้รับจากฟอร์ม
            $query->bindParam(':subject_id', $subject_id); // ส่งค่า subject_id ที่ได้รับจากฟอร์ม

            $query->execute();

            echo "<script>alert('อัปโหลดไฟล์สำเร็จ!'); window.location.href='file.php?lesson_id=" . $lesson_id . "';</script>";
            exit;
        } catch (Exception $e) {
            echo "<script>alert('เกิดข้อผิดพลาดในการบันทึกข้อมูลลงฐานข้อมูล'); window.location.href='file.php';</script>";
        }
    } else {
        echo "<script>alert('เกิดข้อผิดพลาดในการอัปโหลดไฟล์'); window.location.href='file.php';</script>";
    }
}
?>
