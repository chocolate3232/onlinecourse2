<?php
session_start();
require_once('../command/conn.php');

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
if (!isset($_SESSION['member_id'])) {
    echo "<script>alert('กรุณาเข้าสู่ระบบ');</script>";
    echo "<script>window.location.href = '../index.php';</script>";
    exit();
}

$query = $conn->prepare('
    SELECT DISTINCT s.subject_id, s.subject_name, s.subject_code, s.subject_year, t.member_title, 
           t.member_firstname, t.member_lastname
    FROM tb_subject2 AS s
    INNER JOIN tb_student_level2 AS sl ON 
        s.subject_level = sl.student_level 
        AND s.subject_num = sl.student_num 
        AND s.subject_group = sl.student_group
    LEFT JOIN on_member AS t ON s.member_id = t.member_id
    LEFT JOIN tb_lesson AS l ON s.subject_id = l.subject_id
    WHERE sl.member_id = :member_id
    AND l.lesson_id IS NOT NULL  -- ตรวจสอบว่าในวิชามีบทเรียน
    AND s.subject_year = (
        SELECT MAX(subject_year)
        FROM tb_subject2
        WHERE subject_level = sl.student_level 
          AND subject_num = sl.student_num 
          AND subject_group = sl.student_group
    )
');



$query->bindParam(':member_id', $_SESSION['member_id'], PDO::PARAM_INT);
$query->execute();
$subjects = $query->fetchAll(PDO::FETCH_ASSOC);


// ดึงข้อมูลนักเรียนที่ล็อกอิน รวมถึงคำนำหน้าชื่อ
$studentQuery = $conn->prepare('SELECT member_title, member_firstname, member_lastname FROM on_member WHERE member_id = :member_id');
$studentQuery->bindParam(':member_id', $_SESSION['member_id'], PDO::PARAM_INT);
$studentQuery->execute();
$student = $studentQuery->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บทเรียนออนไลน์</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
     body {
    background-color: #fff5f7;
    font-family: 'Arial', sans-serif;
}

.navbar {
    background-color: #ec407a;
}

.navbar-brand {
    color: white !important;
    font-weight: bold;
}

.navbar .btn, .navbar .nav-link {
    color: white !important;
}

.navbar .btn:hover {
    color: #ffd1e0 !important;
}

.navbar .nav-item {
    display: flex;
    flex-direction: row;
    align-items: center; /* จัดแนวตามแกนหลัก */
    gap: 0.5rem; /* ระยะห่างระหว่างชื่อและปุ่ม */
}

h1 {
    color: #d81b60;
    margin-top: 20px;
}

/* จัดกล่องให้มีขนาดเท่ากัน */
.subject-card {
    background-color: #ffe4ec;
    border: none;
    border-radius: 12px;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s, box-shadow 0.3s;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    justify-content: space-between; /* ให้เนื้อหาภายในกระจายตัว */
    height: 100%; /* ให้สูงเต็มพื้นที่ที่กำหนด */
}

.subject-card:hover {
    transform: scale(1.05);
    box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
}

.card-body {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    height: 100%;
}

.card-title {
    color: #c2185b;
    font-weight: bold;
}

.card-text {
    color: #555;
    margin-top: auto; /* ดันข้อความขึ้น */
}


/* ปรับปุ่มให้แสดงในแถวเดียวกันในมือถือ */
.btn-group-custom {
    display: flex;
    flex-direction: row;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-top: 5px;
}

@media (max-width: 576px) {
    .navbar .nav-item {
        flex-direction: column; /* เปลี่ยนเป็นคอลัมน์ในมือถือ */
        align-items: flex-start; /* ชิดซ้าย */
    }

    .btn-group-custom {
        flex-direction: row; /* แสดงปุ่มในแถวเดียวกัน */
        justify-content: flex-start; /* ชิดซ้าย */
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
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">LMS</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item align-center">
                        <span class="nav-link"><i class="fas fa-user"></i> <?php echo htmlspecialchars($student['member_title'] . ' ' . $student['member_firstname'] . ' ' . $student['member_lastname']); ?></span>
                        <div class="btn-group-custom">
                         <a href="summary_page.php" class="btn btn-outline-light btn-sm" id="summaryButton">สรุปข้อมูล</a>
                        <button class="btn btn-outline-light btn-sm" onclick="confirmLogout()">Logout <i class="fas fa-sign-out-alt"></i></button>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div id="loadingSpinner">
    <div class="spinner-container">
        <div class="spinner-border" role="status"></div>
        <p id="loadingMessage">กำลังโหลด...</p>
    </div>
</div>

    <!-- Content -->
    <div class="container mt-5">
        <h1 class="text-center">บทเรียนออนไลน์</h1>
        <div class="row g-4 mt-4">
            <?php if (!empty($subjects)): ?>
                <?php foreach ($subjects as $subject): ?>
                    <div class="col-md-4">
                    <div class="card subject-card" onclick="window.location.href='lessons_page.php?subject_id=<?php echo htmlspecialchars($subject['subject_id']); ?>'">
    <div class="card-body">
        <h5 class="card-title"><?php echo htmlspecialchars($subject['subject_name']); ?></h5>
        <p class="card-text">
            ครูผู้สอน: <?php echo htmlspecialchars($subject['member_title'] . $subject['member_firstname'] . ' ' . $subject['member_lastname']); ?><br>
            รหัสวิชา: <?php echo htmlspecialchars($subject['subject_code']); ?><br>
            ปีการศึกษา: <?php echo htmlspecialchars($subject['subject_year']); ?>
        </p>
    </div>
</div>

                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center">
                    <p>ยังไม่มีวิชาที่เรียน</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmLogout() {
            if (confirm("คุณต้องการออกจากระบบใช่หรือไม่?")) {
                window.location.href = '../command/logout.php';
            }
        }
        
    </script>
    <script>
    // ฟังก์ชั่นในการแสดงสปินเนอร์
    function showLoadingSpinner() {
        document.getElementById('loadingSpinner').style.display = 'flex'; // แสดงสปินเนอร์
    }

    // ฟังก์ชั่นในการซ่อนสปินเนอร์
    function hideLoadingSpinner() {
        document.getElementById('loadingSpinner').style.display = 'none'; // ซ่อนสปินเนอร์
    }

    // เพิ่ม event listener ให้การ์ดวิชาเพื่อแสดงสปินเนอร์และโหลดหน้า
    document.querySelectorAll('.subject-card').forEach(card => {
        card.addEventListener('click', function() {
            showLoadingSpinner();  // แสดง spinner ขณะโหลดหน้า
            setTimeout(() => {
                window.location.href = this.getAttribute('onclick').replace('window.location.href=', '').replace("'", ""); 
            }, 500); // แสดง spinner 500ms ก่อนที่ URL จะเปลี่ยน
        });
    });
    // เพิ่ม event listener ให้กับปุ่มสรุปข้อมูล
document.getElementById('summaryButton').addEventListener('click', function(event) {
    event.preventDefault(); // ป้องกันการรีเฟรชหน้าโดยตรง
    showLoadingSpinner();  // แสดงสปินเนอร์
    setTimeout(() => {
        window.location.href = this.getAttribute('href');  // ไปยังหน้าสรุปข้อมูล
    }, 500); // แสดง spinner ประมาณ 500ms ก่อนที่ URL จะเปลี่ยน
});
</script>

</body>
</html>
