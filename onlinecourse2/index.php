<?php
session_start(); // เริ่ม session

// ตรวจสอบคุกกี้เพื่อเข้าสู่ระบบอัตโนมัติ
if (isset($_COOKIE['username']) && isset($_COOKIE['password'])) {
    require_once 'command/conn.php'; // เชื่อมต่อฐานข้อมูล

    // รับค่าจากคุกกี้
    $username = $_COOKIE['username'];
    $password = $_COOKIE['password'];

    // ตรวจสอบข้อมูลในฐานข้อมูล
    $result = $conn->prepare("SELECT * FROM on_member WHERE member_code = :username LIMIT 1");
    $result->bindParam(':username', $username);
    $result->execute();
    $query = $result->fetch(PDO::FETCH_ASSOC);

    if ($query && password_verify($password, $query['member_password'])) {
        // เก็บข้อมูลใน session
        $_SESSION['member_code'] = $query['member_code'];
        $_SESSION['member_id'] = $query['member_id'];
        $_SESSION['member_firstname'] = $query['member_firstname'];
        $_SESSION['member_lastname'] = $query['member_lastname'];
        $_SESSION['member_type'] = $query['member_type'];

        // หากเข้าสู่ระบบสำเร็จ
        if ($_SESSION ['member_type'] == 'admin') {
            header("Location: admin/dashboard.php");
            exit();
        }
        else if ($_SESSION ['member_type'] == 'teacher'){
            header("Location: teacher/table.php");
            exit();
        }
        else if  ($_SESSION ['member_type'] == 'student'){
            header("Location: student/user_dashboard.php");
            exit();
        }
        else{

        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LMS</title>
    <link rel="stylesheet" href="css/index.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800&display=swap" rel="stylesheet">
    <style>
             .login-btn {
            background-color: #4CAF50;
        }

        .login-btn a {
            color: white; /* ตัวอักษรในปุ่มเป็นสีขาว */
            text-decoration: none; /* ไม่มีเส้นใต้ */
        }

        .login-btn:hover {
            background-color: #45a049;
        }

        .register-btn {
            background-color: #2196F3;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .register-btn a {
            color: white; /* ตัวอักษรในปุ่มเป็นสีขาว */
            text-decoration: none; /* ไม่มีเส้นใต้ */
        }

        .register-btn:hover {
            background-color: #1e88e5;
        }

        .navbar button a {
            color: white;
            text-decoration: none;
        }
        .button-container {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }

     /* Media queries สำหรับอุปกรณ์มือถือ */
@media (max-width: 768px) {
    .navbar h1 {
        font-size: 1.6rem; /* ลดขนาดฟอนต์ของชื่อ LMS */
    }

    .button-container {
        flex-direction: column; /* เปลี่ยนให้ปุ่มตั้งขึ้น */
        gap: 10px; /* ลดระยะห่างระหว่างปุ่ม */
    }

    button {
        width: 100%; /* ให้ปุ่มเต็มความกว้าง */
        font-size: 16px; /* ลดขนาดฟอนต์ของปุ่ม */
    }

    .container  {
    height: 350px;
    width: 250px;
    color: #fff;
    background-color: #fff;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    border-radius: 10px;
    overflow: hidden;
    background: url(https://images.hdqwalls.com/download/apple-abstract-dark-red-4k-3m-1680x1050.jpg);
    padding: 20px ;
    font-size: 36px;
    text-align: center;
    }

    .container h2 {
        font-size: 1rem; /* ลดขนาดตัวอักษรใน h2 */
    }
}

@media (max-width: 480px) {
    .navbar h1 {
        font-size: 1.4rem; /* ลดขนาดฟอนต์ของชื่อ LMS */
    }

    button {
        font-size: 14px; /* ลดขนาดฟอนต์ของปุ่ม */
    }

    .container h1 {
        font-size: 1rem; /* ลดขนาดตัวอักษรใน h1 */
    }

    .container h2 {
        font-size: 0.9rem; /* ลดขนาดตัวอักษรใน h2 */
    }
}
    </style>
</head>
<body style="background-color: pink;">
<header>
        <div class="navbar">           
            <h1>LMS</h1>
        </div>
    </header>

    <main >
        
        <center><div class="container">
            
                <br><h2>LMS (Learning Management System)</h2>
                <br><h1 style="color: white;">ระบบจัดการการเรียนการสอนออนไลน์</h1>
                <div class="button-container">
                <button class="login-btn"><a href="formlogin.php">เข้าสู่ระบบ</a></button>
            </div>
            
        </div></center>
    </main>
</body>
</html>
