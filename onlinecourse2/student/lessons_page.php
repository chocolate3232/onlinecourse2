<?php
session_start();
require_once('../command/conn.php');

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
if (!isset($_SESSION['member_id'])) {
    echo "<script>alert('กรุณาเข้าสู่ระบบ');</script>";
    echo "<script>window.location.href = '../index.php';</script>";
    exit;
}

$studentQuery = $conn->prepare('SELECT member_title, member_firstname, member_lastname FROM on_member WHERE member_id = :member_id');
$studentQuery->bindParam(':member_id', $_SESSION['member_id']);
$studentQuery->execute();
$student = $studentQuery->fetch(PDO::FETCH_ASSOC);

// ตรวจสอบว่าได้มีการส่งค่า subject_id มาหรือไม่
if (!isset($_GET['subject_id'])) {
    echo "<script>alert('ไม่พบข้อมูลวิชา');</script>";
    echo "<script>window.location.href = 'subject_page.php';</script>";
}

$subject_id = $_GET['subject_id']; // รับค่าจากพารามิเตอร์ URL

// ดึงข้อมูลบทเรียนจากฐานข้อมูล
$query = $conn->prepare('
    SELECT * FROM tb_lesson WHERE subject_id = :subject_id
');
$query->bindParam(':subject_id', $subject_id);
$query->execute();
$lessons = $query->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลวิชาจากฐานข้อมูลพร้อมข้อมูลครูผู้สอน
$subjectQuery = $conn->prepare('
    SELECT s.subject_name, s.subject_code, m.member_title,m.member_firstname, m.member_lastname 
    FROM tb_subject2 s
    JOIN on_member m ON s.member_id = m.member_id
    WHERE s.subject_id = :subject_id
');
$subjectQuery->bindParam(':subject_id', $subject_id);
$subjectQuery->execute();
$subject = $subjectQuery->fetch(PDO::FETCH_ASSOC);

// ดึงข้อมูลความคืบหน้าวิดีโอ
$progressQuery = $conn->prepare("SELECT lesson_id, progress_percen FROM tb_video_progress WHERE member_id = :member_id");
$progressQuery->bindParam(':member_id', $_SESSION['member_id']);
$progressQuery->execute();
$progressData = $progressQuery->fetchAll(PDO::FETCH_KEY_PAIR);

// ดึงข้อมูลไฟล์ที่ผู้เรียนอัปโหลด
$uploadedFilesQuery = $conn->prepare("SELECT lesson_id FROM tb_uploaded_sd WHERE member_id = :member_id");
$uploadedFilesQuery->bindParam(':member_id', $_SESSION['member_id']);
$uploadedFilesQuery->execute();
$uploadedFilesData = $uploadedFilesQuery->fetchAll(PDO::FETCH_COLUMN, 0); // lesson_id ของบทเรียนที่มีการอัปโหลดไฟล์
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เลือกบทเรียน - <?php echo htmlspecialchars($subject['subject_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #fff5f7; /* Soft pink background */
            color: #333;
            padding-top: 80px; /* space for fixed navbar */
        }
        .navbar {
            background-color: #ec407a; /* Soft pink navbar */
        }
        .navbar-brand, .navbar-nav .nav-link {
            color: white !important;
        }
        .navbar-nav .nav-link:hover {
            color: #f8d7fa !important;
        }
        .subject-info {
            background: #fce4ec; /* Light pink background */
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .subject-info h2 {
            color: #ec407a; /* Darker pink for titles */
        }
        .lesson-card {
            border: none;
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            border-radius: 15px;
            background-color: #f8bbd0; /* Light pink background for cards */
        }
        .lesson-card:hover {
            transform: scale(1.05);
            box-shadow: 0px 8px 20px rgba(0, 0, 0, 0.2);
        }
        .locked {
            opacity: 0.5;
            cursor: not-allowed;
            background-color: #f48fb1; /* Darker pink for locked lessons */
        }
        .footer {
            background-color: #ec407a; /* Soft pink footer */
            color: white;
            padding: 10px 0;
            text-align: center;
            margin-top: 40px;
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
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#"><b>LMS</a></b>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#" id="homeButton"><i class="fas fa-home"></i> หน้าหลัก</a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link"><i class="fas fa-user"></i> <?php echo htmlspecialchars($student['member_title'] . ' ' . $student['member_firstname'] . ' ' . $student['member_lastname']); ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Loading Spinner -->
    <div id="loadingSpinner">
        <div class="spinner-container">
            <div class="spinner-border" role="status"></div>
            <p id="loadingMessage">กำลังโหลด...</p>
        </div>
    </div>

    <!-- Content -->
    <div class="container">
        <div class="subject-info mb-4">
            <h2>รายละเอียดวิชา</h2>
            <p><strong>ชื่อวิชา:</strong> <?php echo htmlspecialchars($subject['subject_name']); ?></p>
            <p><strong>รหัสรายวิชา:</strong> <?php echo htmlspecialchars($subject['subject_code']); ?></p>
            <p><strong>ครูผู้สอน:</strong> <?php echo htmlspecialchars($subject['member_title'] .$subject['member_firstname'] . ' ' . $subject['member_lastname']); ?></p>
            <p><strong>จำนวนบทเรียน:</strong> <?php echo count($lessons); ?> บทเรียน</p>
        </div>

        <div class="row">
            <?php
            $count = 0;
            foreach ($lessons as $lesson): 
                $count++;
                $isUnlocked = ($count === 1 || 
                (isset($progressData[$lessons[$count - 2]['lesson_id']]) && 
                $progressData[$lessons[$count - 2]['lesson_id']] >= 99) &&
                in_array($lessons[$count - 2]['lesson_id'], $uploadedFilesData)
            );
?>            
                <div class="col-md-4 mb-4">
                    <div class="card lesson-card <?php echo $isUnlocked ? '' : 'locked'; ?>" 
                        <?php if ($isUnlocked): ?>
                            onclick="window.location.href = 'video_page.php?lesson_id=<?php echo htmlspecialchars($lesson['lesson_id']); ?>&subject_id=<?php echo htmlspecialchars($lesson['subject_id']); ?>';"
                        <?php endif; ?> >
                        <div class="card-body text-center">
                            <h5 class="card-title"><?php echo htmlspecialchars($lesson['title']); ?></h5>
                            <p class="card-text"><i class="fas fa-play-circle"></i> บทที่ <?php echo $count; ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
    // Show the loading spinner
    function showLoadingSpinner() {
        document.getElementById('loadingSpinner').style.display = 'flex';
    }

    // Hide the loading spinner
    function hideLoadingSpinner() {
        document.getElementById('loadingSpinner').style.display = 'none';
    }

    // Handle click on Home button (หน้าหลัก)
    document.getElementById('homeButton').addEventListener('click', function(event) {
        event.preventDefault(); // Prevent immediate navigation
        showLoadingSpinner();  // Show loading spinner

        // Simulate a delay, then navigate to the home page
        setTimeout(() => {
            window.location.href = 'user_dashboard.php'; // Change to your home page URL
        }, 500); // Wait for 500ms to show the spinner
    });

    // Handle click on lesson cards
    document.querySelectorAll('.lesson-card').forEach(card => {
        card.addEventListener('click', function(event) {
            event.preventDefault(); // Prevent immediate navigation
            showLoadingSpinner();  // Show loading spinner

            const targetUrl = this.getAttribute('onclick').replace('window.location.href = ', '').replace("'", "").replace("';", "");

            // Simulate a delay, then navigate to the lesson page
            setTimeout(() => {
                window.location.href = targetUrl;
            }, 500); // Wait for 500ms to show the spinner
        });
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
