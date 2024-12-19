<?php
session_start();
require_once('../command/conn.php');

if (!isset($_SESSION['member_id'])) {
    echo "<script>alert('กรุณาเข้าสู่ระบบ');</script>";
    echo "<script>window.location.href = '../index.php';</script>";
    exit;
}

// ดึง member_id จาก session
$member_id = $_SESSION['member_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_id = $_POST['subject_id'];
    $title = $_POST['title'];

    // ตรวจสอบ URL ของ YouTube
    $video_embed = $_POST['video_embed'];
    if (!empty($video_embed)) {
        if (isYouTubeUrl($video_embed)) {
            $video_url = extractVideoUrl($video_embed);
            if ($video_url) {
                if (!preg_match('/^https:\/\/www\.youtube\.com\/embed\//', $video_url)) {
                    $video_embed = "https://www.youtube.com/embed/" . getYouTubeVideoId($video_url);
                } else {
                    $video_embed = $video_url;
                }
            } else {
                echo "<script>alert('ไม่สามารถตัด URL ออกจากโค้ดฝังได้');</script>";
                echo "<script>window.location.href = 'add_lesson.php?subject_id=$subject_id';</script>";
                exit;
            }
        } else {
            echo "<script>alert('กรุณาใส่ URL ของ YouTube ที่ถูกต้อง');</script>";
            echo "<script>window.location.href = 'add_lesson.php?subject_id=$subject_id';</script>";
            exit;
        }
    }

    // ตรวจสอบการอัปโหลดไฟล์แบบฝึกหัด
    if (!isset($_FILES['exercise_file']) || $_FILES['exercise_file']['error'] !== UPLOAD_ERR_OK) {
        echo "<script>alert('กรุณาอัปโหลดไฟล์แบบฝึกหัดก่อน');</script>";
        echo "<script>window.location.href = 'add_lesson.php?subject_id=$subject_id';</script>";
        exit;
    }

    // อัปโหลดไฟล์แบบฝึกหัด
    $exerciseTmpPath = $_FILES['exercise_file']['tmp_name'];
    $exerciseName = $_FILES['exercise_file']['name'];
    $exerciseSize = $_FILES['exercise_file']['size'];
    $exerciseExtension = strtolower(pathinfo($exerciseName, PATHINFO_EXTENSION));
    $exerciseNewName = md5(time() . $exerciseName) . '.' . $exerciseExtension;
    $exerciseUploadDir = 'uploads/';
    $exerciseDestPath = $exerciseUploadDir . $exerciseNewName;

    // ตรวจสอบประเภทไฟล์และขนาดไฟล์
    $allowedExtensions = ['pdf', 'docx', 'zip'];
    if (!in_array($exerciseExtension, $allowedExtensions)) {
        echo "<script>alert('ประเภทไฟล์ไม่ถูกต้อง! รองรับเฉพาะไฟล์ PDF, DOCX, ZIP');</script>";
        echo "<script>window.location.href = 'add_lesson.php?subject_id=$subject_id';</script>";
        exit;
    }
    if ($exerciseSize > 10 * 1024 * 1024) { // 10MB
        echo "<script>alert('ไฟล์ใหญ่เกินไป! กรุณาอัปโหลดไฟล์ขนาดไม่เกิน 10MB');</script>";
        echo "<script>window.location.href = 'add_lesson.php?subject_id=$subject_id';</script>";
        exit;
    }

    // ย้ายไฟล์ไปยังโฟลเดอร์อัปโหลด
    if (!move_uploaded_file($exerciseTmpPath, $exerciseDestPath)) {
        echo "<script>alert('เกิดข้อผิดพลาดในการอัปโหลดไฟล์แบบฝึกหัด');</script>";
        echo "<script>window.location.href = 'add_lesson.php?subject_id=$subject_id';</script>";
        exit;
    }

    // เพิ่มบทเรียนในฐานข้อมูล
    $query = $conn->prepare('INSERT INTO tb_lesson (subject_id, title, member_id, video_url) VALUES (:subject_id, :title, :member_id, :video_url)');
    $query->bindParam(':subject_id', $subject_id);
    $query->bindParam(':title', $title);
    $query->bindParam(':member_id', $member_id);
    $query->bindParam(':video_url', $video_embed);
    $query->execute();

    // ดึง lesson_id ที่สร้างขึ้น
    $lesson_id = $conn->lastInsertId();

   // บันทึกข้อมูลไฟล์แบบฝึกหัดในตาราง tb_uploaded_files พร้อม subject_id
$exerciseQuery = $conn->prepare('
INSERT INTO tb_uploaded_files (member_id, file_name, file_path, file_size, file_type, lesson_id, subject_id) 
VALUES (:member_id, :file_name, :file_path, :file_size, :file_type, :lesson_id, :subject_id)
');
$exerciseQuery->bindParam(':member_id', $member_id);
$exerciseQuery->bindParam(':file_name', $exerciseName);
$exerciseQuery->bindParam(':file_path', $exerciseDestPath);
$exerciseQuery->bindParam(':file_size', $exerciseSize);
$exerciseQuery->bindParam(':file_type', $exerciseExtension);
$exerciseQuery->bindParam(':lesson_id', $lesson_id);
$exerciseQuery->bindParam(':subject_id', $subject_id); // เพิ่มการบันทึก subject_id

if ($exerciseQuery->execute()) {
echo "<script>alert('เพิ่มบทเรียนสำเร็จ');</script>";
echo "<script>window.location.href = 'view_details.php?subject_id=$subject_id';</script>";
} else {
echo "<script>alert('เกิดข้อผิดพลาดในการบันทึกข้อมูลไฟล์แบบฝึกหัดในฐานข้อมูล');</script>";
echo "<script>window.location.href = 'add_lesson.php?subject_id=$subject_id';</script>";
}
}

// ฟังก์ชันเพิ่มเติม
function getYouTubeVideoId($url) {
    parse_str(parse_url($url, PHP_URL_QUERY), $params);
    return $params['v'] ?? null;
}

function isYouTubeUrl($url) {
    return strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false;
}

function extractVideoUrl($embedCode) {
    preg_match('/src="([^"]+)"/', $embedCode, $matches);
    return $matches[1] ?? null;
}

?>
