<?php
session_start();
require_once('../command/conn.php');

// ตรวจสอบว่ามีคำขอ AJAX เข้ามาหรือไม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $subject_id = $_POST['subject_id'] ?? '';
    $search_term = $_POST['search'] ?? '';
    
    $search_query = '';
    $params = [':subject_id' => $subject_id];

    if (!empty($search_term)) {
        $search_query = " AND title LIKE :search_term";
        $params[':search_term'] = '%' . $search_term . '%';
    }

    $query = $conn->prepare("SELECT * FROM tb_lesson WHERE subject_id = :subject_id" . $search_query);
    $query->execute($params);
    $lessons = $query->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['lessons' => $lessons]);
    exit;
}

// ตรวจสอบว่าได้ส่ง subject_id มาหรือไม่
if (!isset($_GET['subject_id'])) {
    echo "<script>alert('ไม่พบรหัสวิชา');</script>";
    echo "<script>window.location.href='table.php';</script>";
    exit;
}

$subject_id = $_GET['subject_id'];

// ค้นหาบทเรียนตามชื่อบทเรียน (ถ้ามีการค้นหา)
$search_query = "";
if (isset($_POST['search'])) {
    $search_term = "%" . $_POST['search'] . "%";
    $search_query = " AND title LIKE :search_term";
}

// ดึงข้อมูลบทเรียนจาก tb_lesson
$query = $conn->prepare("SELECT * FROM tb_lesson WHERE subject_id = :subject_id" . $search_query);
$query->bindParam(':subject_id', $subject_id);
if ($search_query != "") {
    $query->bindParam(':search_term', $search_term);
}
$query->execute();
$lessons = $query->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดบทเรียน</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .container {
            max-width: 1200px;
        }

        h1 {
            font-family: 'Prompt', sans-serif;
            font-weight: 600;
            color: #4e73df;
        }

        .btn {
            font-size: 14px;
        }

        .table {
            margin-top: 20px;
        }

        .table th,
        .table td {
            vertical-align: middle;
        }


        .video-embed {
            width: 100%;
            height: 200px;
            border: none;
        }
        /* ปรับตารางให้มีความสามารถในการเลื่อนบนโทรศัพท์ */
.table-responsive {
    overflow-x: auto;
}

/* ปรับขนาดฟอนต์และการแสดงผลของตารางบนมือถือ */
@media (max-width: 768px) {
    .container {
        padding: 15px;
    }

    /* ปรับขนาดฟอนต์ของหัวตารางและตัวตาราง */
    .table th, .table td {
        font-size: 12px; /* ลดขนาดฟอนต์ในตาราง */
        padding: 8px; /* ลด padding ให้ตารางไม่กว้างเกินไป */
    }

    .table {
        margin-top: 10px;
    }

    .table-responsive {
        -webkit-overflow-scrolling: touch;
    }

    /* ทำให้ตารางสามารถเลื่อนได้ */
    .table-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .btn {
        font-size: 12px; /* ลดขนาดปุ่ม */
        padding: 5px 10px; /* ลดขนาด padding ของปุ่ม */
    }

    /* ปรับขนาดฟอนต์ของหัวข้อ */
    h1 {
        font-size: 20px; /* ลดขนาดฟอนต์ของหัวข้อ */
    }

    h2 {
        font-size: 16px; /* ลดขนาดฟอนต์ของหัวข้อ */
    }
    
    .input-group input {
        font-size: 12px; /* ลดขนาดฟอนต์ในช่องค้นหา */
        padding: 6px;
    }

    .input-group button {
        font-size: 12px;
        padding: 6px;
    }
    .video-embed {
            width: 200px;
            height: 200px;
            border: none;
        }
        
}
.input-group-text {
    background-color:rgb(226, 220, 220); /* พื้นหลังของไอคอน */
    border-right: none; /* ลบเส้นขอบด้านขวา */
    padding: 0.5rem 0.75rem; /* จัดการ padding */
    display: flex;
    align-items: center; /* จัดไอคอนให้อยู่ตรงกลาง */
}

.input-group-text i {
    color: black; /* สีของไอคอน */
    font-size: 1.2rem; /* ขนาดไอคอน */
}

.form-control {
    border-left: none; /* ลบเส้นขอบด้านซ้าย */
    padding: 0.5rem; /* ปรับ padding ของช่องกรอก */
    font-size: 1rem;
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
        <h1 class="text-center mb-4">บทเรียนออนไลน์</h1>
    
        <div id="loadingSpinner">
    <div class="spinner-container">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">กำลังโหลด...</span>
        </div>
        <p class="mt-2" id="loadingMessage">กำลังโหลด...</p>
    </div>
</div>



        <div class="d-flex justify-content-between mb-4">
        <a href="table.php" class="btn btn-outline-primary"onclick="handleDownload()">
    <i class="bi bi-arrow-left"></i> กลับ
</a>

            <a href="add_lesson.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-success"onclick="handleDownload()">
    <i class="bi bi-plus-circle"></i> เพิ่มบทเรียน
</a>
        </div>
      
      <!-- ฟอร์มค้นหา -->
      <form method="POST" class="mb-4" onsubmit="return false;">
    <div class="input-group">
        <span class="input-group-text">
            <i class="bi bi-search"></i>
        </span>
        <input type="text" class="form-control" id="search" name="search" placeholder="ค้นหาบทเรียน..." oninput="fetchLessons()">
    </div>
</form>


<div class="table-responsive">
    <table class="table table-hover table-bordered">
        <thead>
            <tr>
                <th>ลำดับที่</th>
                <th>ชื่อบทเรียน</th>
                <th>ข้อสอบก่อนเรียน</th>
                <th>วิดีโอ</th>
                <th>ข้อสอบหลังเรียน</th>
                <th>แบบฝึกหัด</th>
                <th>งานที่นักเรียนส่ง</th>
                <th>สถานะ</th>
                <th>การจัดการ</th>
            </tr>
        </thead>
        <tbody id="lesson-table-body">
            <!-- ตารางจะอัปเดตข้อมูลตรงนี้ -->
            <?php if ($lessons): ?>
                <?php $index = 1; ?>
                <?php foreach ($lessons as $lesson): ?>
                    <tr>
                        <td><?php echo $index++; ?></td>
                        <td><?php echo htmlspecialchars($lesson['title']); ?></td>
                        <td class="text-center">
                        <a href="javascript:void(0);" class="btn btn-secondary btn-sm" onclick="window.open('http://www.ctnphrae.com/th/add-exam.html', '_blank', 'width=800,height=600');">
                            <i class="bi bi-plus"></i> เพิ่มข้อสอบ
                        </a>
                        </td>
                        <td>
                            <?php if (!empty($lesson['video_url'])): ?>
                                <iframe class="video-embed" src="<?php echo htmlspecialchars($lesson['video_url']); ?>" frameborder="0" allowfullscreen></iframe>
                            <?php else: ?>
                                <p class="text-center text-muted">ไม่มีวิดีโอ</p>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                        <a href="javascript:void(0);" class="btn btn-secondary btn-sm" onclick="window.open('http://www.ctnphrae.com/th/add-exam.html', '_blank', 'width=800,height=600');">
                            <i class="bi bi-plus"></i> เพิ่มข้อสอบ
                        </a>
                        </td>
                        <td>
                            <a href="file.php?lesson_id=<?php echo $lesson['lesson_id']; ?>&subject_id=<?php echo $subject_id; ?>" onclick="handleDownload()" class="btn btn-success btn-sm">
                                <i class="bi bi-eye"></i> ดูแบบฝึกหัด
                            </a>
                        </td>
                        <td class="text-center">
                            <a href="view_status.php?lesson_id=<?php echo $lesson['lesson_id']; ?>&subject_id=<?php echo $subject_id; ?>" onclick="handleDownload()" class="btn btn-warning btn-sm">
                                <i class="bi bi-graph-up"></i> ดูงาน
                            </a>
                        </td>
                        <td class="text-center">
                            <input type="checkbox" class="file-check" data-lesson-id="<?php echo $lesson['lesson_id']; ?>" <?php echo $lesson['is_checked'] ? 'checked' : ''; ?>>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-2">
                                <a href="edit_lesson.php?lesson_id=<?php echo $lesson['lesson_id']; ?>&subject_id=<?php echo $lesson['subject_id']; ?>" onclick="handleDownload()" class="btn btn-primary btn-sm">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="delete_lesson.php?lesson_id=<?php echo $lesson['lesson_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('คุณแน่ใจหรือว่าต้องการลบบทเรียนนี้?');">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" class="text-center text-muted">ยังไม่มีบทเรียน</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

    <script>
    document.querySelectorAll('.file-check').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const lessonId = this.dataset.lessonId;
            const isChecked = this.checked ? 'true' : 'false';

            // ส่งข้อมูลไปยัง PHP เพื่ออัปเดตฐานข้อมูล
            fetch('update_checkbox_status.php', {
                method: 'POST',
                body: new URLSearchParams({
                    lesson_id: lessonId,
                    is_checked: isChecked
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Checkbox status updated successfully');
                } else {
                    console.error('Failed to update checkbox status');
                }
            });
        });
    });
    function showLoading() {
    document.getElementById('loadingSpinner').style.display = 'flex';
}

function hideLoading() {
    document.getElementById('loadingSpinner').style.display = 'none';
}

</script>
<script>
function fetchLessons() {
    const searchInput = document.getElementById('search').value;
    const subjectId = <?php echo json_encode($subject_id); ?>;

    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            ajax: 'true',
            search: searchInput,
            subject_id: subjectId,
        }),
    })
    .then(response => response.json())
    .then(data => {
        const tableBody = document.getElementById('lesson-table-body');
        tableBody.innerHTML = ''; // ล้างข้อมูลเก่าออก

        if (data.lessons.length > 0) {
            data.lessons.forEach((lesson, index) => {
                const row = `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${lesson.title}</td>
                        <td class="text-center">
                            <a href="javascript:void(0);" class="btn btn-secondary btn-sm">
                                <i class="bi bi-plus"></i> เพิ่มข้อสอบ
                            </a>
                        </td>
                        <td>
                            ${lesson.video_url ? `<iframe class="video-embed" src="${lesson.video_url}" frameborder="0" allowfullscreen></iframe>` : '<p class="text-center text-muted">ไม่มีวิดีโอ</p>'}
                        </td>
                        <td class="text-center">
                            <a href="javascript:void(0);" class="btn btn-secondary btn-sm">
                                <i class="bi bi-plus"></i> เพิ่มข้อสอบ
                            </a>
                        </td>
                        <td>
                            <a href="file.php?lesson_id=${lesson.lesson_id}&subject_id=${subjectId}" class="btn btn-success btn-sm">
                                <i class="bi bi-eye"></i> ดูแบบฝึกหัด
                            </a>
                        </td>
                        <td class="text-center">
                            <a href="view_status.php?lesson_id=${lesson.lesson_id}&subject_id=${subjectId}" class="btn btn-warning btn-sm">
                                <i class="bi bi-graph-up"></i> ดูงาน
                            </a>
                        </td>
                        <td class="text-center">
                            <input type="checkbox" class="file-check" data-lesson-id="${lesson.lesson_id}" ${lesson.is_checked ? 'checked' : ''}>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-2">
                                <a href="edit_lesson.php?lesson_id=${lesson.lesson_id}&subject_id=${lesson.subject_id}" class="btn btn-primary btn-sm">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="delete_lesson.php?lesson_id=${lesson.lesson_id}" class="btn btn-danger btn-sm" onclick="return confirm('คุณแน่ใจหรือว่าต้องการลบบทเรียนนี้?');">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                `;
                tableBody.innerHTML += row;
            });
        } else {
            tableBody.innerHTML = '<tr><td colspan="9" class="text-center text-muted">ไม่มีข้อมูลบทเรียน</td></tr>';
        }
    })
    .catch(error => console.error('Error fetching lessons:', error));
}
</script>
<script>
    // ฟังก์ชันแสดงการแจ้งเตือน
    function showLoading(message = "กำลังโหลด...") {
        const loadingSpinner = document.getElementById("loadingSpinner");
        const loadingMessage = document.getElementById("loadingMessage");

        // เปลี่ยนข้อความให้ตรงกับสถานการณ์ที่เกิดขึ้น
        loadingMessage.textContent = message;
        loadingSpinner.style.display = "flex";  // แสดงการแจ้งเตือน
    }

    // ฟังก์ชันซ่อนการแจ้งเตือน
    function hideLoading() {
        document.getElementById("loadingSpinner").style.display = "none";  // ซ่อนการแจ้งเตือน
    }
    function handleDownload() {
    showLoading("กำลังโหลด...");

    setTimeout(() => {
        hideLoading();
        setTimeout(() => hideLoading(), 1000); 
    },3000); // จำลองการดาวน์โหลดที่ใช้เวลาสักครู่
}

</script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
