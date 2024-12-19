<?php
session_start();
require_once('../command/conn.php'); // เชื่อมต่อฐานข้อมูล

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
if (!isset($_SESSION['member_id'])) {
    echo "<script>alert('กรุณาล็อกอินก่อน'); window.location.href='login.php';</script>";
    exit;
}

// รับค่า lesson_id จาก URL หรือฟอร์ม
$lesson_id = isset($_GET['lesson_id']) ? $_GET['lesson_id'] : 0;

// ดึงไฟล์ที่อัปโหลดจากฐานข้อมูล
$query = $conn->prepare("SELECT * FROM tb_uploaded_files WHERE member_id = :member_id AND lesson_id = :lesson_id");
$query->bindParam(':member_id', $_SESSION['member_id']);
$query->bindParam(':lesson_id', $lesson_id);
$query->execute();
$uploadedFiles = $query->fetchAll(PDO::FETCH_ASSOC);

// ดึง subject_id จาก tb_lesson ตาม lesson_id
$querySubject = $conn->prepare("SELECT subject_id FROM tb_lesson WHERE lesson_id = :lesson_id");
$querySubject->bindParam(':lesson_id', $lesson_id, PDO::PARAM_INT);
$querySubject->execute();
$subjectInfo = $querySubject->fetch(PDO::FETCH_ASSOC);

// ตรวจสอบว่าพบ subject_id หรือไม่
$subject_id = isset($subjectInfo['subject_id']) ? $subjectInfo['subject_id'] : 0;

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>อัปโหลดไฟล์</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .container {
            max-width: 100%;
        }

        .table {
            margin-top: 20px;
        }

        .table td, .table th {
            vertical-align: middle;
        }

        .btn {
            font-size: 14px;
        }

        /* ให้ปุ่มอยู่ในแนวนอน */
        .form-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-start;
        }

        /* ปรับขนาดฟอนต์และการแสดงผลบนมือถือ */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .btn {
                font-size: 12px; 
                padding: 5px 10px;
            }

            .table {
                margin-top: 10px;
            }

            .table-responsive {
                -webkit-overflow-scrolling: touch;
            }

            /* ปรับขนาดของตารางในมือถือ */
            .table td, .table th {
                font-size: 12px;
                padding: 8px;
            }

            .form-buttons {
                flex-direction: column;
                align-items: flex-start;
            }

            .modal-dialog {
                max-width: 100%;
                width: auto;
                margin: 0;
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
    border-color: #007bff; /* สีน้ำเงิน */
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
    <div class="container mt-5">
        <h2>อัปโหลดไฟล์แบบฝึกหัดสำหรับบทเรียน</h2>
        <form action="upload.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="lesson_id" value="<?php echo $lesson_id; ?>">
            <div class="mb-3">
                <label for="fileUpload" class="form-label">เลือกไฟล์:</label>
                <input class="form-control" type="file" id="fileUpload" name="fileUpload" accept=".pdf, .docx, .zip" required>
                <input type="hidden" name="lesson_id" value="<?php echo $lesson_id; ?>">
                <input type="hidden" name="subject_id" value="<?php echo $subject_id; ?>"> <!-- เพิ่ม hidden field สำหรับ subject_id -->
            </div>
            <!-- Spinner แสดงเมื่อกำลังอัปโหลด -->
<div id="loadingSpinner">
    <div class="spinner-container">
        <div class="spinner-border text-light" role="status"></div>
        <p id="loadingMessage">กำลังเพิ่มแบบฝึกหัด...</p>
    </div>
</div>

            <div class="form-buttons">
                <button type="submit" class="btn btn-primary">เพิ่มแบบฝึกหัด</button>
                <a href="view_details.php?lesson_id=<?php echo $lesson_id; ?>&subject_id=<?php echo $subject_id; ?>" class="btn btn-secondary">กลับ</a>
            </div>
        </form>
    </div>

    <div class="container mt-5">
        <h2>รายการไฟล์แบบฝึกหัดที่อัปโหลด</h2>
        
        <?php if (count($uploadedFiles) > 0): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ชื่อไฟล์</th>
                            <th>ขนาดไฟล์</th>
                            <th>ประเภทไฟล์</th>
                            <th>ตัวอย่าง</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($uploadedFiles as $file): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($file['file_name']); ?></td>
                                <td><?php echo number_format($file['file_size'] / 1024, 2) . ' KB'; ?></td>
                                <td><?php echo htmlspecialchars($file['file_type']); ?></td>
                                <td>
                                    <?php if (in_array(strtolower(pathinfo($file['file_name'], PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#imageModal-<?php echo $file['id']; ?>">
                                            <img src="<?php echo $file['file_path']; ?>" alt="<?php echo htmlspecialchars($file['file_name']); ?>" style="max-width: 100px; max-height: 100px;">
                                        </a>
                                        <div class="modal fade" id="imageModal-<?php echo $file['id']; ?>" tabindex="-1" aria-labelledby="imageModalLabel-<?php echo $file['id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog modal-xl modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="imageModalLabel-<?php echo $file['id']; ?>"><?php echo htmlspecialchars($file['file_name']); ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body text-center">
                                                        <img src="<?php echo $file['file_path']; ?>" alt="<?php echo htmlspecialchars($file['file_name']); ?>" style="max-width: 90%; height: auto;">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <a href="<?php echo $file['file_path']; ?>" target="_blank">ดาวน์โหลด</a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="delete_file.php?file_id=<?php echo $file['id']; ?>&lesson_id=<?php echo $lesson_id; ?>" class="btn btn-danger btn-sm" onclick="return confirm('คุณต้องการลบไฟล์นี้หรือไม่?')" title="ลบไฟล์">
                                        <i class="bi bi-trash"></i> ลบ
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>ยังไม่มีไฟล์แบบฝึกหัดที่อัปโหลด</p>
        <?php endif; ?>
    </div>
    <script>
    // ฟังก์ชันที่ใช้ในการแสดง spinner ขณะกำลังอัปโหลด
    const form = document.querySelector("form");
    const loadingSpinner = document.getElementById("loadingSpinner");
    const fileUploadInput = document.getElementById("fileUpload");

    form.addEventListener("submit", function(event) {
        // เช็คว่าไฟล์ถูกเลือกหรือไม่
        if (fileUploadInput.files.length > 0) {
            // แสดง spinner เมื่อเริ่มอัปโหลด
            loadingSpinner.style.display = "flex";
            document.getElementById("loadingMessage").innerText = "กำลังเพิ่มแบบฝึกหัด..."; // เปลี่ยนข้อความใน spinner
        }
    });

    // ฟังก์ชันที่ทำให้แสดง spinner เมื่อกดปุ่ม "กลับ"
    const backButton = document.querySelector("a.btn.btn-secondary");
    backButton.addEventListener("click", function(event) {
        event.preventDefault(); // ยกเลิกการไปยังหน้าก่อนหน้านี้
        // แสดง spinner กำลังโหลด
        loadingSpinner.style.display = "flex";
        document.getElementById("loadingMessage").innerText = "กำลังโหลด..."; // เปลี่ยนข้อความใน spinner

        // รอ 1 วินาทีเพื่อให้การแสดงผล spinner ดูสมจริง
        setTimeout(function() {
            window.location.href = backButton.href; // เมื่อการโหลดเสร็จจะไปยังหน้าก่อนหน้านี้
        }, 1000);  // คุณสามารถปรับเวลาตามต้องการ
    });
</script>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
