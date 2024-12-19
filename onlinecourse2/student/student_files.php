<?php
session_start();
require_once('../command/conn.php');

// ตรวจสอบว่าผู้ใช้เป็นนักเรียน
if (!isset($_SESSION['member_id'])) {
    echo "<script>alert('กรุณาล็อกอินก่อน'); window.location.href='login.php';</script>";
    exit;
}

$lesson_id = $_GET['lesson_id']; // รับค่า lesson_id จาก URL หรือสามารถปรับใช้วิธีที่เหมาะสม
$member_id = $_SESSION['member_id']; // ใช้ session ของผู้ใช้ปัจจุบัน

// ดึงข้อมูลการดูวิดีโอของนักเรียน
$videoProgressQuery = $conn->prepare("SELECT progress_percen FROM tb_video_progress WHERE member_id = :member_id AND lesson_id = :lesson_id");
$videoProgressQuery->bindParam(':member_id', $member_id);
$videoProgressQuery->bindParam(':lesson_id', $lesson_id);
$videoProgressQuery->execute();
$videoProgress = $videoProgressQuery->fetch(PDO::FETCH_ASSOC);

// หากยังไม่ได้ดูวิดีโอหรือดูไม่ถึง 90% จะไม่สามารถเข้าถึงหน้านี้ได้
if ($videoProgress && $videoProgress['progress_percen'] < 90) {
    echo "<script>alert('คุณต้องดูวิดีโอให้ครบ 90% ก่อนจึงจะสามารถทำแบบฝึกหัดได้'); window.location.href='user_dashboard.php';</script>";
    exit;
}

// ดึงไฟล์ทั้งหมดที่อัปโหลดโดยครูในบทเรียนนี้
$query = $conn->prepare("SELECT * FROM tb_uploaded_files WHERE lesson_id = :lesson_id");
$query->bindParam(':lesson_id', $lesson_id);
$query->execute();
$files = $query->fetchAll(PDO::FETCH_ASSOC);

// ดึงไฟล์ที่นักเรียนส่งสำหรับบทเรียนนี้
$studentQuery = $conn->prepare("SELECT * FROM tb_uploaded_sd WHERE lesson_id = :lesson_id AND member_id = :member_id");
$studentQuery->bindParam(':lesson_id', $lesson_id);
$studentQuery->bindParam(':member_id', $member_id);
$studentQuery->execute();
$studentFiles = $studentQuery->fetchAll(PDO::FETCH_ASSOC);

// ตรวจสอบว่ามีไฟล์หรือไม่
if (count($files) === 0) {
    echo "<script>alert('ไม่มีแบบฝึกหัดสำหรับบทเรียนนี้'); window.location.href='user_dashboard.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แบบฝึกหัด</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #fce4ec;
            color: #5c3b6d;
        }
        h1, h2 {
            color: #d81b60;
        }
        .file-list, .student-file-list {
            margin-top: 20px;
        }
        .file-item, .student-file-item {
            background-color: #ffffff;
            padding: 15px;
            margin: 10px 0;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .file-item .file-info, .student-file-item .file-info {
            flex: 1;
            padding-right: 10px;
        }
        .file-actions, .student-file-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            align-items: center;
        }
        .file-actions a, .student-file-actions a {
            color: #d81b60;
            text-decoration: none;
        }
        .file-actions a:hover, .student-file-actions a:hover {
            text-decoration: none;
            color: white;
        }
        .upload-form, .back-button {
            margin-top: 20px;
        }
        .btn-delete {
            background-color: #e53935;
            color: white;
            border: none;
        }
        .btn-delete:hover {
            background-color: #c62828;
        }
        /* ปรับแต่งให้เหมาะกับมือถือ */
        @media (max-width: 767px) {
            h1 {
                font-size: 1.5rem;
            }
            .file-item, .student-file-item {
                padding: 10px;
            }
            .upload-form {
                padding: 10px;
            }
            .file-actions a, .student-file-actions a {
                font-size: 0.9rem;
            }
            .btn-delete {
                font-size: 0.9rem;
            }
            .back-button {
                margin-bottom: 20px;
            }
        }
        #loadingSpinner {
    display: none;
    justify-content: center;
    align-items: center;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 9999;
    text-align: center;
    background-color: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    width: 350px;
    animation: fadeIn 0.5s ease-out;
}



#loadingMessage {
    font-size: 18px;
    font-weight: bold;
    margin: 0;
    padding-top: 10px;
    text-align: center;
}

.spinner-container {
    display: flex;
    flex-direction: column;
    align-items: center;
}

#loadingSpinner .spinner-border {
    width: 50px;
    height: 50px;
    margin-bottom: 10px;
    border-color: #d81b60; /* สีน้ำเงิน */
    border-top-color: transparent; /* เปลี่ยนแค่ขอบบนเป็นสีน้ำเงิน */
}


#loadingSpinner p {
    font-size: 18px;
    font-weight: bold;
    margin: 0;
    padding-top: 10px;
}

@keyframes fadeIn {
    0% {
        opacity: 0;
    }
    100% {
        opacity: 1;
    }
}
    </style>
</head>
<body>
    <!-- Spinner สำหรับแสดงสถานะกำลังโหลด -->
<div id="loadingSpinner">
    <div class="spinner-container">
        <div class="spinner-border" role="status"></div>
        <p id="loadingMessage">กำลังโหลด...</p>
    </div>
</div>

    <div class="container">
        <h1 class="my-4">แบบฝึกหัด</h1>

        <!-- ปุ่มกลับ -->
        <a href="user_dashboard.php" class="btn btn-secondary">กลับไปยังหน้าหลัก</a>

        <!-- แสดงไฟล์ที่อัปโหลด -->
        <div class="file-list">
            <h2>ไฟล์จากครู</h2>
            <?php foreach ($files as $file): ?>
                <div class="file-item">
                    <div class="file-info">
                        <strong><?= $file['file_name'] ?></strong>
                        <div class="text-muted">
                            ขนาด: <?= number_format($file['file_size'] / 1024, 2) ?> KB | ประเภท: <?= $file['file_type'] ?>
                        </div>
                    </div>
                    <div class="file-actions">
                        <a href="http://localhost/onlinecourse2/teacher/<?= $file['file_path'] ?>" target="_blank" class="btn btn-outline-primary btn-sm">ดูไฟล์</a>
                        <a href="http://localhost/onlinecourse2/teacher/<?= $file['file_path'] ?>" download class="btn btn-outline-success btn-sm">ดาวน์โหลด</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <hr>

        <!-- ฟอร์มสำหรับการอัปโหลดไฟล์ -->
        <h2>แนบไฟล์สำหรับส่งแบบฝึกหัด</h2>
        <div class="upload-form">
            <form action="submit_file.php" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="fileUpload" class="form-label">เลือกไฟล์ที่ต้องการส่ง</label>
                    <input type="file" name="fileUpload" id="fileUpload" class="form-control"  accept=".pdf, .docx, .zip, .jpg, .png, .jpeg"  required>
                </div>
                <input type="hidden" name="lesson_id" value="<?= $lesson_id ?>">
                <button type="submit" class="btn btn-primary w-100">ส่งแบบฝึกหัด</button>
            </form>
        </div>
<!-- แสดงไฟล์ที่นักเรียนส่ง -->
<div class="student-file-list">
    <h2>ไฟล์ที่คุณส่ง</h2>
    <?php if (count($studentFiles) === 0): ?>
        <p class="text-muted">ยังไม่ส่งแบบฝึกหัด</p>
    <?php else: ?>
        <?php foreach ($studentFiles as $studentFile): ?>
            <div class="student-file-item">
                <div class="file-info">
                    <strong><?= htmlspecialchars($studentFile['file_name']) ?></strong>
                    <div class="text-muted">
                        ขนาด: <?= number_format($studentFile['file_size'] / 1024, 2) ?> KB | ประเภท: <?= htmlspecialchars($studentFile['file_type']) ?>
                    </div>
                </div>
                <div class="student-file-actions">
                    <a href="http://localhost/onlinecourse2/student/<?= htmlspecialchars($studentFile['file_path']) ?>" target="_blank" class="btn btn-outline-primary btn-sm">ดูไฟล์</a>
                    <form action="delete_file.php" method="POST" style="display:inline;" onsubmit="return confirmDelete();">
                        <input type="hidden" name="file_id" value="<?= htmlspecialchars($studentFile['id']) ?>">
                        <input type="hidden" name="lesson_id" value="<?= htmlspecialchars($studentFile['lesson_id']) ?>">
                        <button type="submit" class="btn btn-delete btn-sm">ลบ</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>


    </div>
    <script>
    function confirmDelete() {
        return confirm("คุณแน่ใจหรือไม่ว่าต้องการลบไฟล์นี้?");
    }
</script>
<script>
    const loadingSpinner = document.getElementById("loadingSpinner");
    const loadingMessage = document.getElementById("loadingMessage");

    // แสดงสถานะ "กำลังส่งแบบฝึกหัด" เมื่อกดปุ่มส่งฟอร์ม
    const uploadForm = document.querySelector("form[action='submit_file.php']");
    uploadForm.addEventListener("submit", function () {
        loadingMessage.innerText = "กำลังส่งแบบฝึกหัด...";
        loadingSpinner.style.display = "flex";
    });

    // แสดงสถานะ "กำลังโหลด..." เมื่อกดปุ่มกลับไปยังหน้าหลัก
    const backButton = document.querySelector("a[href='user_dashboard.php']");
    backButton.addEventListener("click", function (e) {
        loadingMessage.innerText = "กำลังโหลด...";
        loadingSpinner.style.display = "flex";
    });
</script>

</body>
</html>
