<?php
session_start();
require_once('../command/conn.php');

if (isset($_POST['progress'], $_POST['progress_percen'], $_SESSION['member_id'], $_POST['subject_id'], $_POST['lesson_id'])) {
    $progress = $_POST['progress'];
    $progress_percen = $_POST['progress_percen'];
    $member_id = $_SESSION['member_id'];
    $subject_id = $_POST['subject_id'];
    $lesson_id = $_POST['lesson_id'];

    $query = $conn->prepare("
        INSERT INTO tb_video_progress (member_id, subject_id, lesson_id, progress, progress_percen)
        VALUES (:member_id, :subject_id, :lesson_id, :progress, :progress_percen)
        ON DUPLICATE KEY UPDATE 
            progress = :progress,
            progress_percen = :progress_percen
    ");
    
    $query->execute([
        ':member_id' => $member_id,
        ':subject_id' => $subject_id,
        ':lesson_id' => $lesson_id,
        ':progress' => $progress,
        ':progress_percen' => $progress_percen
    ]);
}
