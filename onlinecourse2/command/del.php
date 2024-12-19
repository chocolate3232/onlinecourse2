<?php
session_start();
require_once('../command/conn.php');

if (isset($_GET['del'])) {
    $subject_id = $_GET['del'];

    try {
        // ลบข้อมูลใน tb_video_progress ที่เชื่อมโยงกับ lesson_id
        $delete_video_progress_query = $conn->prepare("DELETE FROM tb_video_progress WHERE lesson_id IN (SELECT lesson_id FROM tb_lesson WHERE subject_id = :subject_id)");
        $delete_video_progress_query->bindParam(':subject_id', $subject_id);
        $delete_video_progress_query->execute();

        // ลบข้อมูลใน tb_uploaded_files ที่เชื่อมโยงกับ lesson_id
        $delete_uploaded_files_query = $conn->prepare("DELETE FROM tb_uploaded_files WHERE lesson_id IN (SELECT lesson_id FROM tb_lesson WHERE subject_id = :subject_id)");
        $delete_uploaded_files_query->bindParam(':subject_id', $subject_id);
        $delete_uploaded_files_query->execute();

        // ลบข้อมูลใน tb_lesson ที่เชื่อมโยงกับ subject_id
        $delete_lesson_query = $conn->prepare("DELETE FROM tb_lesson WHERE subject_id = :subject_id");
        $delete_lesson_query->bindParam(':subject_id', $subject_id);
        $delete_lesson_query->execute();

        // ลบข้อมูลใน tb_subject2
        $delete_subject_query = $conn->prepare("DELETE FROM tb_subject2 WHERE subject_id = :subject_id");
        $delete_subject_query->bindParam(':subject_id', $subject_id);
        $delete_subject_query->execute();

        echo "<script>alert('ลบข้อมูลสำเร็จ');</script>";
        echo "<script>window.location.href = '../teacher/table.php';</script>";
    } catch (PDOException $e) {
        echo "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}
?>
