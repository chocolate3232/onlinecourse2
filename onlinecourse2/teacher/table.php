<?php
session_start();
require_once('../command/conn.php');

// ตรวจสอบว่าเข้าสู่ระบบหรือยัง
if (!isset($_SESSION['member_id'])) {
    echo "<script>alert('กรุณาลงชื่อเข้าใช้');</script>";
    echo "<script>window.location.href = 'login.php';</script>";
    exit;
}

// ดึงข้อมูลของคุณครู
$member_id = $_SESSION['member_id'];
$queryTeacher = $conn->prepare('SELECT member_title,member_firstname, member_lastname FROM on_member WHERE member_id = :id');
$queryTeacher->bindParam(':id', $member_id);
$queryTeacher->execute();
$teacher = $queryTeacher->fetch(PDO::FETCH_ASSOC);

// ดึงข้อมูลรายวิชาเฉพาะของคุณครู
$querytable = $conn->prepare("
    SELECT 
        subject_name,
        subject_year,
        COUNT(*) AS total_subjects
    FROM tb_subject2
    WHERE member_id = :id
     AND subject_year = (SELECT MAX(subject_year) FROM tb_subject2 WHERE member_id = :id)
    GROUP BY subject_name, subject_year
");
$querytable->bindParam(':id', $member_id);
$querytable->execute();
$subjects = $querytable->fetchAll(PDO::FETCH_ASSOC);
$totalSubjects = count($subjects);


// นับจำนวนนักเรียนทั้งหมดจากปีการศึกษาล่าสุด โดยไม่นับ subject_num = 0
$query = $conn->prepare("
    SELECT COUNT(*) 
    FROM tb_student_level2 
    WHERE student_year = (SELECT MAX(student_year) FROM tb_student_level2) 
    AND student_num != 0
");
$query->execute();
$totalStudents = $query->fetchColumn();


// นับจำนวนบทเรียนของครูแต่ละคน
$queryLessons = $conn->prepare("SELECT COUNT(*) FROM tb_lesson WHERE member_id = :id");
$queryLessons->bindParam(':id', $member_id);
$queryLessons->execute();
$totalLessons = $queryLessons->fetchColumn();

// นับจำนวนแบบฝึกหัดของครูแต่ละคน
$queryExercises = $conn->prepare("SELECT COUNT(*) FROM tb_uploaded_files WHERE member_id = :id");
$queryExercises->bindParam(':id', $member_id);
$queryExercises->execute();
$totalExercises = $queryExercises->fetchColumn();


$queryStudentCounts = $conn->prepare("
    SELECT 
        student_level, 
        student_num, 
        student_group, 
        COUNT(*) as total_students
    FROM tb_student_level2
    WHERE student_year = (SELECT MAX(student_year) FROM tb_student_level2) and student_num != 0
    GROUP BY student_level, student_num, student_group
    
");
$queryStudentCounts->execute();
$studentCounts = $queryStudentCounts->fetchAll(PDO::FETCH_ASSOC);

$querySubjectsWithLessons = $conn->prepare("
SELECT DISTINCT
    s.subject_id,
    s.subject_code,
    s.subject_year,
    s.subject_name,
    s.subject_level,
    s.subject_num,
    s.subject_group
FROM tb_subject2 s
WHERE s.member_id = :id
AND s.subject_year = (SELECT MAX(subject_year) FROM tb_subject2 WHERE member_id = :id)
AND TRIM(LOWER(s.subject_level)) IN ('ปวช', 'ปวส', 'ปวสม6')
ORDER BY 
    CASE 
        WHEN TRIM(LOWER(subject_level)) = 'ปวช' THEN 1
        WHEN TRIM(LOWER(subject_level)) = 'ปวส' THEN 2
        WHEN TRIM(LOWER(subject_level)) = 'ปวสม6' THEN 3
        ELSE 4
    END ASC,
    subject_num ASC, 
    subject_name ASC,
    subject_group ASC;
");

$querySubjectsWithLessons->bindParam(':id', $member_id);
$querySubjectsWithLessons->execute();
$subjectsWithLessons = $querySubjectsWithLessons->fetchAll(PDO::FETCH_ASSOC);



// นับจำนวนบทเรียนและแบบฝึกหัดสำหรับแต่ละวิชา
foreach ($subjectsWithLessons as &$subject2) {
    // นับจำนวนบทเรียน
    $queryLessonsCount = $conn->prepare("SELECT COUNT(*) FROM tb_lesson WHERE subject_id = :subject_id");
    $queryLessonsCount->bindParam(':subject_id', $subject2['subject_id']);
    $queryLessonsCount->execute();
    $subject2['total_lessons'] = $queryLessonsCount->fetchColumn();

    // นับจำนวนแบบฝึกหัด
    $queryExercisesCount = $conn->prepare("SELECT COUNT(*) FROM tb_uploaded_files WHERE subject_id = :subject_id");
    $queryExercisesCount->bindParam(':subject_id', $subject2['subject_id']);
    $queryExercisesCount->execute();
    $subject2['total_exercises'] = $queryExercisesCount->fetchColumn();
}




?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อมูลรายวิชา - สำหรับครู</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="table.css">
</head>

<body>
    <div class="container-fluid">
        <div class="header">
            <h2>Teacher</h2>
            <div>
                <span> <?php echo htmlspecialchars($teacher['member_title'] . ' ' . $teacher['member_firstname'] . ' ' . $teacher['member_lastname']); ?></span>
                <a class="logout-btn ms-3" href="../command/logout.php" onclick="return confirmLogout()">
    <i class="fas fa-sign-out-alt"></i> Logout
</a>
<script>
    function confirmLogout() {
        // แสดงกล่องข้อความยืนยันก่อนออกจากระบบ
        return confirm("คุณแน่ใจที่จะออกจากระบบหรือไม่?");
    }
</script>
            </div>
        </div>

        <!-- ข้อมูลสถิติ -->
        <div class="row text-center mb-4">
            <div class="col-md-2 col-6 d-flex justify-content-center mb-3 mb-md-0">
                <div class="card shadow-sm w-100">
                    <div class="card-body">
                        <i class="fas fa-user-graduate"></i>
                        <h5 class="card-title">จำนวนนักเรียนทั้งหมด</h5>
                        <p class="card-text"><?php echo $totalStudents; ?> คน</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-6 d-flex justify-content-center mb-3 mb-md-0">
                <div class="card shadow-sm w-100">
                    <div class="card-body">
                        <i class="fas fa-book"></i>
                        <h5 class="card-title">จำนวนวิชาทั้งหมด</h5>
                        <p class="card-text"><?php echo $totalSubjects; ?> วิชา</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-6 d-flex justify-content-center mb-3 mb-md-0">
                <div class="card shadow-sm w-100">
                    <div class="card-body">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <h5 class="card-title">จำนวนบทเรียนทั้งหมด</h5>
                        <p class="card-text"><?php echo $totalLessons; ?> บทเรียน</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-6 d-flex justify-content-center mb-3 mb-md-0">
                <div class="card shadow-sm w-100">
                    <div class="card-body">
                        <i class="fas fa-tasks"></i>
                        <h5 class="card-title">จำนวนแบบฝึกหัดทั้งหมด</h5>
                        <p class="card-text"><?php echo $totalExercises; ?> แบบฝึกหัด</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-6 d-flex justify-content-center mb-3 mb-md-0">
                <div>
                    <div>
                    </div>
                </div>
            </div>
        </div>


        <div class="row text-center mb-4">
    <?php if (empty($studentCounts)): ?>
        <div class="col-12">
            <div class="alert alert-info">ไม่มีข้อมูลนักเรียนในปีการศึกษานี้</div>
        </div>
    <?php else: ?>
        <?php foreach ($studentCounts as $row): ?>
            <div class="col-md-3 col-6 mb-3">
                <div class="card shadow-sm border-light">
                    <div class="card-body p-3">
                        <!-- ใช้ d-flex และ justify-content-between -->
                        <div class="d-flex justify-content-between">
                        <i class="fas fa-users"></i> 
                            <h6 class="card-title"><?php echo htmlspecialchars($row['student_level']); ?> - ปี <?php echo htmlspecialchars($row['student_num']); ?> </h6>
                            <p class="card-text student-group">กลุ่ม <?php echo htmlspecialchars($row['student_group']); ?></p>
                        </div>
                        <h5 class="card-text text-primary"><?php echo htmlspecialchars($row['total_students']); ?> คน</h5>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
  <!-- กล่องค้นหาและเพิ่มวิชา -->
  <div class="row mb-4">
    <div class="col-12 col-md-8 d-flex align-items-center mb-3 mb-md-0">
        <a class="btn btn-primary me-3" href="http://www.ctnphrae.com/th/manage-instructor.html" target="_blank">
            <i class="fas fa-plus"></i> เพิ่มข้อมูลรายวิชา
        </a>

        <div class="input-group w-100 w-md-25">
        <span class="input-group-text bg-transparent border-end-0">
                <i class="fas fa-search"></i> <!-- ไอคอนค้นหา -->
            </span>
            <input type="text" id="searchInput" class="form-control border-start-0" placeholder="ค้นหารายวิชา...">
         
        </div>
    </div>
</div>
<!-- Overlay แจ้งเตือนการโหลด -->
<div id="loadingSpinner">
    <div class="spinner-container">
        <div class="spinner-border" role="status"></div>
        <p id="loadingMessage">กำลังโหลด...</p>
    </div>
</div>


        <!-- ตารางรายวิชา -->
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="table-primary">
                    <tr>
                        <th>รายวิชา</th>
                        <th>รหัสวิชา</th>
                        <th>ปีการศึกษา</th>
                        <th>ระดับ</th>
                        <th>ชั้นปี</th>
                        <th>กลุ่ม</th>
                        <th>จำนวนบทเรียน</th>
                        <th>จำนวนแบบฝึกหัด</th>
                        <th>การจัดการ</th>
                    </tr>
                </thead>
                <tbody id="subjectTable">
                    <?php if (empty($subjects)): ?>
                        <tr><td colspan="9" class="text-center">ไม่มีข้อมูลรายวิชา</td></tr>
                    <?php else: ?>
                        <?php foreach ( $subjectsWithLessons as $subject): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                <td><?php echo htmlspecialchars($subject['subject_year']); ?></td>
                                <td><?php echo htmlspecialchars($subject['subject_level']); ?></td>
                                <td><?php echo htmlspecialchars($subject['subject_num']); ?></td>
                                <td><?php echo htmlspecialchars($subject['subject_group']); ?></td>
                                <td><?php echo htmlspecialchars($subject['total_lessons']); ?> บทเรียน</td>
                                <td><?php echo htmlspecialchars($subject['total_exercises']); ?> แบบฝึกหัด</td>
                                <td class="d-flex flex-wrap gap-2 justify-content-start">
                                <td class="d-flex flex-wrap gap-2 justify-content-start">
<a class="btn btn-success btn-sm" 
   href="add_lesson.php?subject_id=<?php echo $subject['subject_id']; ?>" 
   title="เพิ่มบทเรียน" 
   onclick="showLoading();">
    <i class="fas fa-plus"></i> เพิ่มบทเรียน
</a>

<a class="btn btn-info btn-sm" 
   href="view_details.php?subject_id=<?php echo $subject['subject_id']; ?>" 
   title="ดูรายละเอียด" 
   onclick="showLoading();">
    <i class="fas fa-info-circle"></i> ดูรายละเอียด
</a>
<script>
    // แสดง Overlay
    function showLoading() {
    // แสดง overlay การโหลด
    document.getElementById('loadingSpinner').style.display = 'flex';
}


    // ซ่อน Overlay (ถ้าต้องการใช้งานในอนาคต)
    function hideLoading() {
        document.getElementById('loadingSpinner').style.display = 'none'; // ซ่อน overlay
    }
</script>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    <script>
        document.getElementById('searchInput').addEventListener('input', function() {
            var searchValue = this.value.toLowerCase();
            var rows = document.querySelectorAll('#subjectTable tr');
            rows.forEach(function(row) {
                var subjectName = row.cells[0] ? row.cells[0].textContent.toLowerCase() : '';
                if (subjectName.includes(searchValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

    </script>
    
</body>
</html> 
