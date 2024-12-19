<?php
// เชื่อมต่อฐานข้อมูล
require_once('../command/conn.php');

// กำหนดค่า default สำหรับการค้นหา
$search_query = '';

// ตรวจสอบว่า `lesson_id` ถูกส่งมาใน URL หรือไม่
if (isset($_GET['lesson_id'])) {
    $lesson_id = $_GET['lesson_id'];

    // เช็คข้อมูลการค้นหา
    if (isset($_GET['search_query'])) {
        $search_query = $_GET['search_query'];
    }
    
    $query = $conn->prepare("
    SELECT 
        vp.member_id, 
        m.member_code, 
        m.member_firstname, 
        m.member_lastname, 
        m.member_title,  -- Add member_title to the SELECT query
        s.subject_id,
        s.subject_name, 
        l.title AS lesson_title, 
        COALESCE(vp.progress_percen, 0) AS progress_percen,
        GROUP_CONCAT(f.file_name SEPARATOR ', ') AS all_file_names,
        GROUP_CONCAT(f.file_path SEPARATOR ', ') AS all_file_paths,
        f.upload_time,
        (SELECT COUNT(DISTINCT vp.member_id) 
         FROM tb_video_progress vp 
         WHERE vp.lesson_id = :lesson_id) AS student_count,
        (SELECT COUNT(DISTINCT f.member_id) 
         FROM tb_uploaded_sd f
         WHERE f.lesson_id = :lesson_id AND f.is_student_submission = 1) AS submission_count
    FROM 
        tb_video_progress vp
    JOIN 
        on_member m ON vp.member_id = m.member_id
    JOIN 
        tb_subject2 s ON vp.subject_id = s.subject_id
    JOIN 
        tb_lesson l ON vp.lesson_id = l.lesson_id
    LEFT JOIN 
        tb_uploaded_sd f ON f.member_id = vp.member_id AND f.lesson_id = l.lesson_id AND f.is_student_submission = 1
    WHERE 
        l.lesson_id = :lesson_id
        AND (m.member_code LIKE :search_query OR CONCAT(m.member_firstname, ' ', m.member_lastname) LIKE :search_query)
    GROUP BY 
        vp.member_id, l.lesson_id
    ORDER BY 
        m.member_code ASC
");

    $query->bindParam(':lesson_id', $lesson_id, PDO::PARAM_INT);
    $query->bindValue(':search_query', "%$search_query%", PDO::PARAM_STR);
    $query->execute();
    $lesson_data = $query->fetchAll(PDO::FETCH_ASSOC);

    // ดึงข้อมูลวิชาและบทเรียน
    $lesson_info = $lesson_data ? $lesson_data[0] : null;
} else {
    // ถ้าไม่มี `lesson_id` ให้แสดงข้อความว่าไม่พบข้อมูล
    echo "ไม่พบข้อมูลบทเรียน";
    exit;
}

if (!$lesson_info) {
    $query_subject = $conn->prepare("SELECT subject_id FROM tb_lesson WHERE lesson_id = :lesson_id");
    $query_subject->bindParam(':lesson_id', $lesson_id, PDO::PARAM_INT);
    $query_subject->execute();
    $lesson_info = $query_subject->fetch(PDO::FETCH_ASSOC);
}

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>งานของนักเรียน</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .progress-bar {
            transition: width 0.4s ease;
        }
        .table th, .table td {
            vertical-align: middle;
        }
        .table th {
            background-color: #f8f9fa;
        }
        .table-striped tbody tr:nth-child(odd) {
            background-color: #f9f9f9;
        }
        .table-bordered th, .table-bordered td {
            border: 1px solid #ddd;
        }
        .btn-custom {
            background-color: #007bff;
            color: white;
        }
        .btn-custom:hover {
            background-color: #0056b3;
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

        /* ปรับขนาดของฟอนต์และ padding สำหรับมือถือ */
        @media (max-width: 768px) {
            .btn-custom {
                font-size: 12px;
                padding: 6px 12px;
            }
            .table td, .table th {
                font-size: 12px;
                padding: 8px;
            }
            .progress-bar {
                height: 20px;
            }
        }
        
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1 class="text-center mb-4 text-primary">งานของนักเรียน</h1>
        <div id="loadingSpinner">
    <div class="spinner-container">
        <div class="spinner-border" role="status"></div>
        <p>กำลังโหลด...</p>
    </div>
</div>

        <div class="d-flex justify-content-start mb-3">
        <a href="view_details.php<?php echo isset($lesson_info['subject_id']) ? '?subject_id=' . htmlspecialchars($lesson_info['subject_id']) : ''; ?>" 
   class="btn btn-outline-secondary btn-sm" id="backButton">
    <i class="bi bi-arrow-left-circle"></i> กลับ
</a>
<script>
document.getElementById("backButton").addEventListener("click", function(event) {
    event.preventDefault(); // ป้องกันการทำงานเริ่มต้นของลิงก์
    const loadingSpinner = document.getElementById("loadingSpinner");
    
    // แสดง Loading Spinner
    loadingSpinner.style.display = "flex";

    // เปลี่ยนเส้นทาง URL หลังจากแสดง Spinner (หน่วงเวลาเล็กน้อย)
    setTimeout(() => {
        window.location.href = this.href;
    }, 500); // 0.5 วินาที (หรือปรับตามต้องการ)
});
</script>

        </div>
        <!-- ฟอร์มค้นหานักเรียน -->
        <form class="mb-4" method="get" onsubmit="return false;">
            <input type="hidden" name="lesson_id" value="<?php echo htmlspecialchars($lesson_id); ?>">
            <div class="row">
                <div class="col-md-12">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span> <!-- ไอคอนค้นหา -->
                        <input type="text" class="form-control" name="search_query" id="searchQuery" placeholder="ค้นหาจากรหัสนักศึกษา หรือ ชื่อ-นามสกุล" value="<?php echo htmlspecialchars($search_query); ?>" oninput="submitSearch()">
                    </div>
                </div>
            </div>
        </form>

        <?php if ($lesson_info): ?>
            <!-- แสดงวิชา -->
            <div class="mb-4">
                <h3 class="text-secondary">วิชา: <?php echo isset($lesson_info['subject_name']) ? htmlspecialchars($lesson_info['subject_name']) : 'ยังไม่มีนักเรียนดูวีดิโอ'; ?></h3>
                <h4 class="text-secondary">บทเรียน: <?php echo isset($lesson_info['lesson_title']) ? htmlspecialchars($lesson_info['lesson_title']) : 'ยังไม่มีนักเรียนดูวีดิโอ'; ?></h4>
                <!-- แสดงจำนวนของนักเรียนที่ดูวิดีโอ -->
                <div class="alert alert-info mt-4">
                    <span class="fw-bold">จำนวนนักเรียนที่ดูวิดีโอในบทเรียนนี้: <span class="text-success">
                    <?php echo isset($lesson_info['student_count']) ? htmlspecialchars($lesson_info['student_count']) : '0'; ?> คน</span></span><br>
                    <span class="fw-bold">จำนวนนักเรียนที่ส่งแบบฝึกหัดในบทเรียนนี้: <span class="text-success">
                    <?php echo isset($lesson_info['submission_count']) ? htmlspecialchars($lesson_info['submission_count']) : '0'; ?> คน</span></span>
                </div>
            </div>

            <!-- ตาราง -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-striped">
                    <thead>
                        <tr>
                            <th>รหัสนักศึกษา</th>
                            <th>นักเรียน</th>
                            <th>แถบความคืบหน้า</th>
                            <th>ดูแบบฝึกหัดที่ส่ง</th>
                            <th>วันที่อัปโหลด</th>
                            <th>ดูข้อสอบที่ส่ง</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lesson_data as $row): ?>
                            <tr>
                            <td><?php echo htmlspecialchars($row['member_code']); ?></td>
                            <td>
                           <?php 
                          // ดึงคำนำหน้าจากฐานข้อมูล
                           $prefix = $row['member_title']; // ค่าคำนำหน้าจากฐานข้อมูล
                          echo htmlspecialchars($prefix . $row['member_firstname'] . ' ' . $row['member_lastname']); 
                         ?>
                         </td>

                                <td>
                                    <div class="progress">
                                        <div 
                                            class="progress-bar progress-bar-striped <?php echo $row['progress_percen'] >= 70 ? 'bg-success' : ($row['progress_percen'] >= 50 ? 'bg-warning' : 'bg-danger'); ?>" 
                                            role="progressbar" 
                                            style="width: <?php echo $row['progress_percen']; ?>%;" 
                                            aria-valuenow="<?php echo $row['progress_percen']; ?>" 
                                            aria-valuemin="0" 
                                            aria-valuemax="100">
                                            <?php echo $row['progress_percen']; ?>%
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    if (!empty($row['all_file_paths'])): 
                                        $file_names = explode(', ', $row['all_file_names']);
                                        $file_paths = explode(', ', $row['all_file_paths']);
                                        foreach ($file_paths as $index => $file_path): 
                                            $file_url = "http://localhost/onlinecourse2/student/" . ltrim($file_path, '/');
                                    ?>
                                        <div>
                                            <a href="<?php echo htmlspecialchars($file_url); ?>" target="_blank">
                                                <?php echo htmlspecialchars($file_names[$index] ?? "ไฟล์ไม่ทราบชื่อ"); ?>
                                            </a>
                                        </div>
                                    <?php 
                                        endforeach; 
                                    else: 
                                    ?>
                                        <span class="text-muted">ยังไม่มีแบบฝึกหัดที่ส่ง</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $row['upload_time'] ?? '-'; ?></td>
                                <td>
                                    <a href="javascript:void(0);" onclick="window.open('http://www.ctnphrae.com/th/list-subject-score.html', '_blank', 'width=800,height=600');" class="btn btn-sm btn-outline-info">
                                        <i class="bi bi-link-45deg"></i> ดูคะแนน
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-warning text-center">
                <i class="bi bi-exclamation-circle"></i> ไม่มีข้อมูลสำหรับบทเรียนนี้
            </div>
        <?php endif; ?>
    </div>

    <script>
    // ฟังก์ชันที่ถูกเรียกเมื่อมีการพิมพ์ข้อความในช่องค้นหา
    function submitSearch() {
        var searchQuery = document.getElementById("searchQuery").value.toLowerCase();  // ดึงค่าจากช่องค้นหา
        var rows = document.querySelectorAll(".table tbody tr");  // ดึงแถวทั้งหมดในตาราง

        // วนลูปตรวจสอบทุกแถว
        rows.forEach(function(row) {
            var cells = row.getElementsByTagName("td");
            var studentCode = cells[0].textContent.toLowerCase();  // อ่านรหัสนักศึกษา
            var studentName = cells[1].textContent.toLowerCase();  // อ่านชื่อ-นามสกุลนักเรียน

            // ตรวจสอบว่าข้อมูลตรงกับคำค้นหาหรือไม่
            if (studentCode.includes(searchQuery) || studentName.includes(searchQuery)) {
                row.style.display = "";  // แสดงแถว
            } else {
                row.style.display = "none";  // ซ่อนแถว
            }
        });
    }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
