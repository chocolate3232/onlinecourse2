<?php
session_start();
require_once('../command/conn.php');

// ตรวจสอบว่าได้รับ lesson_id มาหรือไม่
if (!isset($_GET['lesson_id'])) {
    echo "<script>alert('ไม่พบรหัสบทเรียน');</script>";
    echo "<script>window.location.href='table.php';</script>";
    exit;
}

$lesson_id = $_GET['lesson_id'];

try {
    // เริ่มต้น transaction
    $conn->beginTransaction();

    // ดึงข้อมูล subject_id จาก tb_lesson ก่อนที่จะลบบทเรียน
    $get_subject_query = $conn->prepare("SELECT subject_id FROM tb_lesson WHERE lesson_id = :lesson_id");
    $get_subject_query->bindParam(':lesson_id', $lesson_id);
    $get_subject_query->execute();
    $subject = $get_subject_query->fetch(PDO::FETCH_ASSOC);

    // ตรวจสอบว่าพบ subject_id หรือไม่
    if (!$subject) {
        echo "<script>alert('ไม่พบข้อมูลวิชาที่เกี่ยวข้อง');</script>";
        echo "<script>window.location.href='table.php';</script>";
        exit;
    }

    $subject_id = $subject['subject_id']; // เก็บ subject_id ไว้ใช้งานต่อไป

    // ลบข้อมูลในตาราง tb_video_progress ที่เชื่อมโยงกับ lesson_id
    $delete_video_progress_query = $conn->prepare("DELETE FROM tb_video_progress WHERE lesson_id = :lesson_id");
    $delete_video_progress_query->bindParam(':lesson_id', $lesson_id);
    $delete_video_progress_query->execute();

    // ลบไฟล์ใน tb_uploaded_sd และลบไฟล์จากโฟลเดอร์
    $get_uploaded_sd_files_query = $conn->prepare("SELECT * FROM tb_uploaded_sd WHERE lesson_id = :lesson_id");
    $get_uploaded_sd_files_query->bindParam(':lesson_id', $lesson_id);
    $get_uploaded_sd_files_query->execute();
    $uploaded_sd_files = $get_uploaded_sd_files_query->fetchAll(PDO::FETCH_ASSOC);

    // ลบไฟล์จากโฟลเดอร์ (เช็คไฟล์ที่อยู่ในฐานข้อมูล)
    foreach ($uploaded_sd_files as $file) {
        $file_path = '../uploads/' . $file['file_path'];  // สมมติว่าไฟล์ถูกเก็บในโฟลเดอร์ uploads/
        if (file_exists($file_path)) {
            unlink($file_path);  // ลบไฟล์จากระบบ
        }
    }

    // ลบข้อมูลในตาราง tb_uploaded_sd ที่เชื่อมโยงกับ lesson_id
    $delete_uploaded_sd_query = $conn->prepare("DELETE FROM tb_uploaded_sd WHERE lesson_id = :lesson_id");
    $delete_uploaded_sd_query->bindParam(':lesson_id', $lesson_id);
    $delete_uploaded_sd_query->execute();

    // ลบไฟล์ใน tb_uploaded_files และลบไฟล์จากโฟลเดอร์
    $get_uploaded_files_query = $conn->prepare("SELECT * FROM tb_uploaded_files WHERE lesson_id = :lesson_id");
    $get_uploaded_files_query->bindParam(':lesson_id', $lesson_id);
    $get_uploaded_files_query->execute();
    $uploaded_files = $get_uploaded_files_query->fetchAll(PDO::FETCH_ASSOC);

    // ลบไฟล์จากโฟลเดอร์ (เช็คไฟล์ที่อยู่ในฐานข้อมูล)
    foreach ($uploaded_files as $file) {
        $file_path = '../uploads/' . $file['file_path'];  // สมมติว่าไฟล์ถูกเก็บในโฟลเดอร์ uploads/
        if (file_exists($file_path)) {
            unlink($file_path);  // ลบไฟล์จากระบบ
        }
    }

    // ลบข้อมูลในตาราง tb_uploaded_files ที่เชื่อมโยงกับ lesson_id
    $delete_uploaded_files_query = $conn->prepare("DELETE FROM tb_uploaded_files WHERE lesson_id = :lesson_id");
    $delete_uploaded_files_query->bindParam(':lesson_id', $lesson_id);
    $delete_uploaded_files_query->execute();

    // ลบบทเรียนจากฐานข้อมูลในตาราง tb_lesson
    $delete_lesson_query = $conn->prepare("DELETE FROM tb_lesson WHERE lesson_id = :lesson_id");
    $delete_lesson_query->bindParam(':lesson_id', $lesson_id);
    $delete_lesson_query->execute();

    // ยืนยันการลบ
    $conn->commit();

    // แสดงข้อความและกลับไปยังหน้า subject_page
    echo "<script>alert('ลบบทเรียนเรียบร้อยแล้ว');</script>";
    echo "<script>window.location.href='view_details.php?subject_id=" . htmlspecialchars($subject_id) . "';</script>";
} catch (Exception $e) {
    // ยกเลิก transaction ในกรณีที่เกิดข้อผิดพลาด
    $conn->rollBack();

    // แสดงข้อความแจ้งข้อผิดพลาด
    echo "<script>alert('เกิดข้อผิดพลาด: " . $e->getMessage() . "');</script>";
    echo "<script>window.location.href='table.php';</script>";
    exit;
}
?>
