<?php
session_start();
require_once('../command/conn.php');

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['member_id'])) {
    echo "<script>alert('กรุณาเข้าสู่ระบบ');</script>";
    echo "<script>window.location.href = '../index.php';</script>";
    exit;
}


// ดึงข้อมูลนักเรียน พร้อมข้อมูลระดับชั้นจาก tb_student_level
$studentQuery = $conn->prepare("
    SELECT 
        m.member_title, 
        m.member_firstname, 
        m.member_lastname, 
        sl.student_level, 
        sl.student_num, 
        sl.student_group 
    FROM on_member AS m
    JOIN tb_student_level2 AS sl 
        ON m.member_id = sl.member_id
    WHERE m.member_id = :member_id
");
$studentQuery->bindParam(':member_id', $_SESSION['member_id']);
$studentQuery->execute();
$student = $studentQuery->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    echo "<script>alert('ไม่พบนักเรียนในระบบ');</script>";
    echo "<script>window.location.href = '../index.php';</script>";
    exit;
}

// ตรวจสอบคำค้นหา
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

// ดึงข้อมูลว่า นักเรียนได้อัปโหลดไฟล์ในบทเรียนก่อนหน้านี้หรือไม่
function isFileUploaded($lesson_id, $member_id) {
    global $conn;
    $query = $conn->prepare("SELECT COUNT(*) FROM tb_uploaded_sd WHERE lesson_id = :lesson_id AND member_id = :member_id");
    $query->bindParam(':lesson_id', $lesson_id);
    $query->bindParam(':member_id', $member_id);
    $query->execute();
    return $query->fetchColumn() > 0;
}

// ดึงข้อมูลวิชา บทเรียน และความคืบหน้าของนักเรียน
$subjectQuery = $conn->prepare("
    SELECT 
        s.subject_id, 
        s.subject_name, 
        s.subject_year, 
        l.lesson_id, 
        l.title, 
        vp.progress_percen 
    FROM tb_subject2 AS s
    LEFT JOIN tb_lesson AS l 
        ON s.subject_id = l.subject_id
    LEFT JOIN tb_video_progress AS vp 
        ON l.lesson_id = vp.lesson_id AND vp.member_id = :member_id
    WHERE 
        s.subject_level = :student_level 
        AND s.subject_num = :student_num 
        AND s.subject_group = :student_group
        AND s.subject_year = (
            SELECT MAX(subject_year) 
            FROM tb_subject2 
            WHERE 
                subject_level = s.subject_level 
                AND subject_num = s.subject_num 
                AND subject_group = s.subject_group
        )
        AND s.subject_name LIKE :search
");
$subjectQuery->bindParam(':member_id', $_SESSION['member_id']);
$subjectQuery->bindParam(':student_level', $student['student_level']);
$subjectQuery->bindParam(':student_num', $student['student_num']);
$subjectQuery->bindParam(':student_group', $student['student_group']);
$subjectQuery->bindValue(':search', '%' . $searchTerm . '%');
$subjectQuery->execute();
$subjects = $subjectQuery->fetchAll(PDO::FETCH_ASSOC);

// จัดกลุ่มข้อมูลตามรายวิชา
$groupedSubjects = [];
foreach ($subjects as $subject) {
    $subject_id = $subject['subject_id'];
    if (!isset($groupedSubjects[$subject_id])) {
        $groupedSubjects[$subject_id] = [
            'subject_name' => $subject['subject_name'],
            'subject_year' => $subject['subject_year'],
            'lessons' => []
        ];
    }
    // ตรวจสอบว่ามีบทเรียนหรือไม่
    if (!empty($subject['lesson_id'])) {
        $groupedSubjects[$subject_id]['lessons'][] = [
            'lesson_id' => $subject['lesson_id'],
            'title' => $subject['title'],
            'progress_percen' => $subject['progress_percen'] ?? 0
        ];
    }
}

// ลบวิชาที่ไม่มีบทเรียน
$groupedSubjects = array_filter($groupedSubjects, function($subject) {
    return !empty($subject['lessons']);
});



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อมูลสรุปนักเรียน</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- เพิ่ม Font Awesome สำหรับไอคอน -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #fff5f7;
        }

        h1 {
            color: #d81b60;
        }

        .card {
            border: none;
            margin-bottom: 20px;
            padding: 20px;
            background-color: #ffffff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #880e4f;
        }

        .card-body {
            display: flex;
            flex-direction: column;
        }

        .card-details {
            margin-bottom: 15px;
        }

        .card-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .btn-primary {
            background-color: #ec407a;
            border-color: #ec407a;
        }

        .btn-primary:hover {
            background-color: #d81b60;
            border-color: #d81b60;
        }

        .progress {
            background-color: #f8bbd0;
            border: 1px solid #f48fb1;
            height: 12px;
            width: 100%;
        }

        .progress-bar {
            background-color: #ec407a;
            line-height: 12px;
            color: white;
            text-align: center;
        }

        footer {
            background-color: #ec407a;
        }

        .btn.disabled, .btn:disabled {
            background-color: #ec407a;
            border-color: #ec407a;
            opacity: 0.6;
            pointer-events: none;
        }

        .btn-warning.disabled, .btn-warning:disabled {
            background-color: #ffeb3b;
            border-color: #fbc02d;
            opacity: 0.6;
            pointer-events: none;
        }

        .btn-back {
            background-color: #607d8b;
            border-color: #607d8b;
            color: white;
        }

        .btn-back:hover {
            background-color: #455a64;
            border-color: #455a64;
        }

        .lesson-box {
            background-color: #f3e5f5;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .lesson-box h6 {
            font-size: 1.1rem;
            color: #880e4f;
        }

        .lesson-box .btn {
            margin-top: 10px;
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
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-12 text-center mb-4">
                <h1>ข้อมูลสรุปนักเรียน</h1>
                <p class="lead">ชื่อนักเรียน: <?php echo htmlspecialchars($student['member_title'] . ' ' . $student['member_firstname'] . ' ' . $student['member_lastname']); ?></p>
            </div>
        </div>

        <form action="" method="GET" class="mb-4">
    <div class="input-group">
        <!-- ไอคอนค้นหาในช่องค้นหา -->
        <span class="input-group-text bg-light border-0">
            <i class="fas fa-search text-muted"></i> <!-- Font Awesome Search Icon -->
        </span>
        <input type="text" id="search" name="search" class="form-control border-0" placeholder="ค้นหารายวิชา..." value="<?php echo htmlspecialchars($searchTerm); ?>" oninput="searchSubjects()">
    </div>
</form>

<!-- Loading Spinner (hidden by default) -->
<div id="loadingSpinner">
    <div class="spinner-container">
        <div class="spinner-border" role="status"></div>
        <p id="loadingMessage">กำลังโหลด...</p>
    </div>
</div>
        <!-- Back Button -->
        <div class="mb-4">
        <a href="javascript:void(0);" class="btn btn-back btn-sm" onclick="showLoadingSpinner('user_dashboard.php')">กลับ</a>
        </div>

        <!-- ข้อมูลวิชาที่ค้นหา -->
        <div id="subjects-container" class="row">
            <?php if (!empty($groupedSubjects)): ?>
                <?php foreach ($groupedSubjects as $subject_id => $subject): ?>
                    <div class="col-md-12 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <div class="card-details">
                                    <h5 class="card-title"><?php echo htmlspecialchars($subject['subject_name']); ?></h5>
                                    <p class="card-text">ปีการศึกษา: <?php echo htmlspecialchars($subject['subject_year']); ?></p>
                                </div>
                                <?php if (!empty($subject['lessons'])): ?>
                                    <?php 
                                    $previousProgress = 100; 
                                    $previousUploaded = true; 
                                    $lessonCounter = 1;
                                    foreach ($subject['lessons'] as $lesson): 
                                        $isUploaded = isFileUploaded($lesson['lesson_id'], $_SESSION['member_id']);
                                    ?>
                                        <div class="lesson-box">
                                            <h6 class="text-secondary">บทที่ <?php echo $lessonCounter; ?>: <?php echo htmlspecialchars($lesson['title']); ?></h6>
                                            <div class="d-flex flex-column align-items-start mb-2">
                                                <span class="text-secondary">ความคืบหน้าในการดู:</span>
                                                <div class="progress" style="width: 100%;">
                                                    <div class="progress-bar" role="progressbar" style="width: <?php echo htmlspecialchars($lesson['progress_percen']); ?>%;" 
                                                         aria-valuenow="<?php echo htmlspecialchars($lesson['progress_percen']); ?>" 
                                                         aria-valuemin="0" aria-valuemax="100">
                                                        <?php echo htmlspecialchars($lesson['progress_percen']); ?>%
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center mb-2">
                                                <p class="text-<?php echo $isUploaded ? 'success' : 'danger'; ?> mb-0">
                                                    <?php echo $isUploaded ? 'สถานะการส่งแบบฝึกหัด: ส่งแบบฝึกหัดแล้ว' : 'สถานะการส่งแบบฝึกหัด: ยังไม่ส่งแบบฝึกหัด'; ?>
                                                </p>
                                            </div>
                                            <div class="d-flex gap-2 justify-content-end">
                                                <?php 
                                                $isVideoDisabled = (!$previousUploaded || $previousProgress < 99) ? 'disabled' : '';
                                                ?>
                                               <!-- View Video Button -->
<a href="javascript:void(0);" 
   onclick="showLoadingSpinner('video_page.php?subject_id=<?php echo htmlspecialchars($subject_id); ?>&lesson_id=<?php echo htmlspecialchars($lesson['lesson_id']); ?>')" 
   class="btn btn-primary btn-sm <?php echo $isVideoDisabled; ?>">
   ดูวิดีโอ
</a>

<!-- View Exercise Button -->
<a href="javascript:void(0);" 
   onclick="showLoadingSpinner('<?php echo $lesson['progress_percen'] >= 90 ? 'student_files.php?subject_id=' . htmlspecialchars($subject_id) . '&lesson_id=' . htmlspecialchars($lesson['lesson_id']) : '#'; ?>')" 
   class="btn btn-warning btn-sm <?php echo $lesson['progress_percen'] < 90 ? 'disabled' : ''; ?>">
   ดูแบบฝึกหัด
</a>

                                                <a href="http://www.ctnphrae.com/th/student-check-test.html" class="btn btn-success btn-sm" target="_blank">
                                                    ดูคะแนน
                                                </a>
                                            </div>
                                        </div>
                                    <?php 
                                    $previousProgress = $lesson['progress_percen'];
                                    $previousUploaded = $isUploaded;
                                    $lessonCounter++;
                                    endforeach; 
                                    ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-md-12 text-center">
                    <p>ไม่พบข้อมูลวิชาที่ค้นหา</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- เพิ่ม JavaScript สำหรับการค้นหาอัตโนมัติ -->
    <script>
function searchSubjects() {
    const searchTerm = document.getElementById('search').value;
    const url = new URL(window.location.href);
    const params = new URLSearchParams(url.search);
    params.set('search', searchTerm);
    url.search = params.toString();
    window.history.pushState({}, '', url); // ปรับ URL โดยไม่ต้องโหลดหน้าใหม่

    // ส่งคำค้นหาผ่าน AJAX
    const xhr = new XMLHttpRequest();
    xhr.open('GET', url.toString(), true);
    xhr.onload = function () {
        if (xhr.status === 200) {
            // อัปเดตเนื้อหาผลการค้นหาใน #subjects-container โดยไม่โหลดส่วนอื่นๆ
            const container = document.getElementById('subjects-container');
            const newContent = xhr.responseText;
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = newContent;
            // คัดลอกเฉพาะเนื้อหาของ #subjects-container ที่มาใหม่
            const newSubjectsContainer = tempDiv.querySelector('#subjects-container');
            if (newSubjectsContainer) {
                container.innerHTML = newSubjectsContainer.innerHTML;
            }
        }
    };
    xhr.send();
}

function showLoadingSpinner(url) {
    // Display the loading spinner
    document.getElementById('loadingSpinner').style.display = 'flex';
    
    // Delay for 1 second before redirecting to the URL to allow the spinner to be visible
    setTimeout(function() {
        window.location.href = url; // Redirect to the URL after loading spinner shows
    }, 1000);
}

    </script>
    <!-- jQuery, Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
