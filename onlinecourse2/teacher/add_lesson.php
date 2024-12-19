<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มบทเรียน</title>
    <!-- ลิงก์ไปยังไฟล์ CSS ของ Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="add_lesson.css">
    
    <style>
        /* การตกแต่งการแจ้งเตือน */
        #loadingSpinner {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
            text-align: center;
            background-color: rgba(0, 0, 0, 0.7); /* พื้นหลังมืดๆ */
            color: white; /* ข้อความสีขาว */
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* เงาเล็กน้อย */
            width: 350px; /* ความกว้างของการแจ้งเตือน */
        }

        #loadingSpinner .spinner-border {
            width: 50px;
            height: 50px;
            margin-bottom: 10px;
        }

        #loadingSpinner p {
            font-size: 18px; /* ข้อความขนาดใหญ่ขึ้น */
            font-weight: bold;
            margin: 0;
            padding-top: 10px;
        }

        /* เอฟเฟกต์การเคลื่อนไหวเมื่อแสดงการแจ้งเตือน */
        @keyframes fadeIn {
            0% {
                opacity: 0;
            }
            100% {
                opacity: 1;
            }
        }

        #loadingSpinner {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>

<body>
<div class="container">
    <h1 class="text-center text-primary mb-4">เพิ่มบทเรียน</h1>
    <form action="add_lesson_process.php" method="POST" enctype="multipart/form-data" id="lessonForm">
        <input type="hidden" name="subject_id" value="<?php echo $_GET['subject_id']; ?>" required>

        <div class="mb-3">
            <label for="title" class="form-label">ชื่อบทเรียน:</label>
            <input type="text" name="title" id="title" class="form-control" required>
        </div>
        
        <div class="exercise-upload mt-4">
            <div class="mb-3">
                <label for="exercise_file" class="form-label">อัปโหลดไฟล์แบบฝึกหัด (PDF, DOCX, ZIP):</label>
                <input type="file" name="exercise_file" id="exercise_file" class="form-control" accept=".pdf, .docx, .zip" required>
            </div>
            <div class="alert alert-info" role="alert">
                รองรับไฟล์ PDF, DOCX, ZIP ขนาดไม่เกิน 10 MB
            </div>
        </div>
        
        <div>
            <div class="mb-3">
                <label for="video_embed" class="form-label">โค้ดฝังวิดีโอจาก YouTube:</label>
                <textarea name="video_embed" id="video_embed" class="form-control" rows="4" placeholder="วางโค้ดฝังที่นี่... " required></textarea>
            </div>
            <div class="alert alert-info" role="alert">
                วิธีการเอาโค้ดฝังจาก YouTube: ไปที่วิดีโอที่คุณต้องการฝัง คลิกที่ปุ่ม 'แชร์' แล้วเลือก 'ฝัง' คัดลอกโค้ดที่แสดงและวางที่นี่
            </div>
        </div>

        <div class="button-container mt-4">
            <input type="submit" name="submit" value="เพิ่มบทเรียน" class="btn btn-primary">
            <a href="table.php" class="btn btn-secondary" id="backButton">กลับ</a>
        </div>
    </form>
</div>

<!-- HTML -->
<!-- Loading spinner -->
<div id="loadingSpinner">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">กำลังโหลด...</span>
    </div>
    <p class="mt-2" id="loadingMessage">กำลังดำเนินการ กรุณารอสักครู่...</p>
</div>

<!-- JavaScript -->
<script>
    // แสดงการแจ้งเตือนเมื่อฟอร์มถูกส่ง
    document.getElementById("lessonForm").addEventListener("submit", function(event) {
        const loadingSpinner = document.getElementById("loadingSpinner");
        const loadingMessage = document.getElementById("loadingMessage");
        
        loadingMessage.textContent = "กำลังเพิ่มบทเรียน กรุณารอสักครู่...";
        loadingSpinner.style.display = "block";
    });

    // แสดงการแจ้งเตือนเมื่อคลิกปุ่มกลับ
    document.getElementById("backButton").addEventListener("click", function(event) {
        event.preventDefault(); // หยุดการเปลี่ยนหน้าไว้ก่อน
        
        const loadingSpinner = document.getElementById("loadingSpinner");
        const loadingMessage = document.getElementById("loadingMessage");
        
        loadingMessage.textContent = "กำลังโหลด...";
        loadingSpinner.style.display = "block";

        // เปลี่ยนหน้าไปยังลิงก์ที่ตั้งค่าไว้หลังจากแสดงการแจ้งเตือน
        setTimeout(function() {
            window.location.href = "table.php";
        }, 100); // หน่วงเวลา 100 มิลลิวินาที
    });
</script>

<!-- ลิงก์ไปยังไฟล์ JavaScript ของ Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>


</body>
</html>