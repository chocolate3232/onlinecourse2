<?php
session_start();
require_once('../command/conn.php');

function getYouTubeEmbedUrl($embedCode) {
    if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $embedCode, $matches)) {
        return 'https://www.youtube.com/embed/' . $matches[1];
    } elseif (preg_match('/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/', $embedCode, $matches)) {
        return 'https://www.youtube.com/embed/' . $matches[1];
    }

    $pattern = '/src="([^"]+)"/';
    if (preg_match($pattern, $embedCode, $matches)) {
        return $matches[1];
    }

    return $embedCode;
}

if (!isset($_GET['lesson_id']) || !isset($_GET['subject_id'])) {
    echo "<script>alert('ไม่พบข้อมูลบทเรียนหรือหัวข้อ');</script>";
    echo "<script>window.location.href='table.php';</script>";
    exit;
}

$lesson_id = $_GET['lesson_id'];
$subject_id = $_GET['subject_id'];

$query = $conn->prepare("SELECT * FROM tb_lesson WHERE lesson_id = :lesson_id");
$query->bindParam(':lesson_id', $lesson_id);
$query->execute();
$lesson = $query->fetch(PDO::FETCH_ASSOC);

if (!$lesson) {
    echo "<script>alert('ไม่พบข้อมูลบทเรียน');</script>";
    echo "<script>window.location.href='table.php';</script>";
    exit;
}

$embed_code = $lesson['video_url'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $video_type = $_POST['video_type'];
    $video_url = '';

    if ($video_type == 'youtube') {
        $video_embed_code = $_POST['video_embed_code'];
        if (!empty($video_embed_code)) {
            $video_url = getYouTubeEmbedUrl($video_embed_code);
        } else {
            echo "<script>alert('กรุณาใส่โค้ดฝัง YouTube');</script>";
            exit;
        }
    } elseif ($video_type == 'mp4') {
        if (isset($_FILES["video_file"]) && $_FILES["video_file"]["error"] == 0) {
            $target_dir = "../uploads/";
            $target_file = $target_dir . basename($_FILES["video_file"]["name"]);
            if (move_uploaded_file($_FILES["video_file"]["tmp_name"], $target_file)) {
                $video_url = $target_file;
            } else {
                echo "<script>alert('เกิดข้อผิดพลาดในการอัปโหลดไฟล์');</script>";
                exit;
            }
        } else {
            echo "<script>alert('กรุณาอัปโหลดไฟล์ MP4');</script>";
            exit;
        }
    }

    $update_query = $conn->prepare("UPDATE tb_lesson SET title = :title, video_url = :video_url WHERE lesson_id = :lesson_id");
    $update_query->bindParam(':title', $title);
    $update_query->bindParam(':video_url', $video_url);
    $update_query->bindParam(':lesson_id', $lesson_id);
    $update_query->execute();

    // แสดงการแจ้งเตือนการบันทึกสำเร็จ
    echo "<script>document.getElementById('loadingSpinner').style.display = 'none';</script>";
    echo "<script>alert('บันทึกข้อมูลเรียบร้อยแล้ว');</script>";
    echo "<script>window.location.href = 'view_details.php?subject_id=$subject_id';</script>";
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขบทเรียน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="edit_lesson1.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center mb-4">แก้ไขบทเรียน</h1>
        <form method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
            <div class="mb-3">
                <label for="title" class="form-label">ชื่อบทเรียน:</label>
                <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($lesson['title']); ?>" required>
            </div>

            <div class="mb-3">
                <input type="radio" id="youtube" name="video_type" value="youtube" onclick="toggleVideoType('youtube')" <?php echo strpos($lesson['video_url'], 'youtube.com') !== false ? 'checked' : ''; ?>>
                <label for="youtube" class="me-3">YouTube</label>
            </div>

            <div class="mb-3" id="youtube_embed_section">
                <label for="video_embed_code" class="form-label">โค้ดฝังวิดีโอ (จาก YouTube):</label>
                <textarea id="video_embed_code" name="video_embed_code" class="form-control" rows="5"><?php echo htmlspecialchars($embed_code); ?></textarea>
            </div>

            <div class="d-flex justify-content-center">
                <button type="submit" class="btn btn-primary">บันทึกการแก้ไข</button>
                <a href="javascript:void(0);" onclick="goBack();" class="btn btn-secondary ms-2">กลับ</a>
            </div>
        </form>

        <!-- สปินเนอร์ที่จะถูกแสดงในระหว่างการบันทึก -->
        <div id="loadingSpinner" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">กำลังบันทึกข้อมูล...</span>
            </div>
            <p id="loadingMessage">กำลังบันทึกข้อมูล...</p>
        </div>
    </div>

    <script>
        function toggleVideoType(type) {
            document.getElementById('youtube_embed_section').style.display = type === 'youtube' ? 'block' : 'none';
        }

        function validateForm() {
            const videoType = document.querySelector('input[name="video_type"]:checked').value;
            if (videoType === 'youtube') {
                const youtubeCode = document.getElementById('video_embed_code').value.trim();
                if (!youtubeCode) {
                    alert('กรุณาใส่โค้ดฝัง YouTube');
                    return false;
                }
            }
            // แสดงสปินเนอร์เมื่อเริ่มการบันทึก
            document.getElementById('loadingSpinner').style.display = 'block';
            document.getElementById('loadingMessage').innerText = 'กำลังบันทึกข้อมูล...'; // ข้อความกำลังบันทึก
            return true;
        }

        function goBack() {
            // แสดงสปินเนอร์เมื่อคลิกปุ่ม "กลับ"
            document.getElementById('loadingSpinner').style.display = 'block';
            document.getElementById('loadingMessage').innerText = 'กำลังโหลด...'; // ข้อความกำลังโหลด
            
           
            setTimeout(function() {
                window.location.href = "view_details.php?subject_id=<?php echo htmlspecialchars($subject_id); ?>";
            }, 500); 
        }

        window.onload = function() {
            <?php if (strpos($lesson['video_url'], 'youtube.com') !== false): ?>
                toggleVideoType('youtube');
            <?php endif; ?>
        };
    </script>
</body>
</html>
