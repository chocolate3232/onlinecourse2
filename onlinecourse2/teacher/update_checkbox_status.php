<?php
session_start();
require_once('../command/conn.php');

// ตรวจสอบว่าได้ส่งข้อมูลมาหรือไม่
if (isset($_POST['lesson_id']) && isset($_POST['is_checked'])) {
    $lesson_id = $_POST['lesson_id'];
    $is_checked = $_POST['is_checked'] == 'true' ? true : false;
    $member_id = $_SESSION['member_id']; // ผู้ใช้ที่ทำการเลือก

    // ตรวจสอบว่าได้บันทึกไว้แล้วหรือไม่ ถ้ามีให้อัปเดต ถ้าไม่มีให้เพิ่ม
    $query = $conn->prepare("SELECT * FROM tb_lesson WHERE lesson_id = :lesson_id AND member_id = :member_id");
    $query->bindParam(':lesson_id', $lesson_id);
    $query->bindParam(':member_id', $member_id);
    $query->execute();
    $existing = $query->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // ถ้ามีการบันทึกแล้ว ให้ทำการอัปเดต
        $updateQuery = $conn->prepare("UPDATE tb_lesson SET is_checked = :is_checked WHERE lesson_id = :lesson_id AND member_id = :member_id");
        $updateQuery->bindParam(':is_checked', $is_checked);
        $updateQuery->bindParam(':lesson_id', $lesson_id);
        $updateQuery->bindParam(':member_id', $member_id);
        $updateQuery->execute();
    } else {
        // ถ้ายังไม่มีการบันทึก ให้เพิ่มข้อมูลใหม่
        $insertQuery = $conn->prepare("INSERT INTO tb_lesson (lesson_id, member_id, is_checked) VALUES (:lesson_id, :member_id, :is_checked)");
        $insertQuery->bindParam(':lesson_id', $lesson_id);
        $insertQuery->bindParam(':member_id', $member_id);
        $insertQuery->bindParam(':is_checked', $is_checked);
        $insertQuery->execute();
    }
}
?>
