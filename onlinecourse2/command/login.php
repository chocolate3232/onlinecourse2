<?php
session_start();

if (isset($_POST['username']) && isset($_POST['password'])) {
    require_once 'conn.php';
    
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $result = $conn->prepare("SELECT * FROM on_member WHERE member_code = :username LIMIT 1");
    $result->bindParam(':username', $username);
    $result->execute();
    $query = $result->fetch(PDO::FETCH_ASSOC);

    if ($query) {
        if (password_verify($password, $query['member_password'])) {
            $_SESSION['member_code'] = $query['member_code'];
            $_SESSION['member_id'] = $query['member_id'];
            $_SESSION['member_firstname'] = $query['member_firstname'];
            $_SESSION['member_lastname'] = $query['member_lastname'];
            $_SESSION['member_type'] = $query['member_type'];

            if (isset($_POST['remember'])) {
                setcookie('username', $username, time() + (86400 * 30), "/");
                setcookie('password', $password, time() + (86400 * 30), "/");
            } else {
                setcookie('username', '', time() - 3600, "/");
                setcookie('password', '', time() - 3600, "/");
            }

            if ($_SESSION['member_type'] === 'teacher') {
                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
                echo "<script>
                window.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'เข้าสู่ระบบสำเร็จ',
                        text: 'ยินดีต้อนรับเข้าสู่ระบบ',
                        width: '90%',
                        padding: '1.5rem',
                    }).then(() => window.location.href = '../teacher/table.php');
                });
                </script>";
            } else if ($_SESSION['member_type'] === 'student') {
                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
                echo "<script>
                window.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'เข้าสู่ระบบสำเร็จ',
                        text: 'ยินดีต้อนรับเข้าสู่ระบบ',
                        width: '90%',
                        padding: '1.5rem',
                    }).then(() => window.location.href = '../student/user_dashboard.php');
                });
                </script>";
            } else if ($_SESSION['member_type'] === 'admin') {
                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
                echo "<script>
                window.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'เข้าสู่ระบบสำเร็จ',
                        text: 'ยินดีต้อนรับเข้าสู่ระบบ',
                        width: '90%',
                        padding: '1.5rem',
                    }).then(() => window.location.href = '../admin/dashboard.php');
                });
                </script>";
            } else {
                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
                echo "<script>
                window.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'ขออภัย กรุณาติดต่อ admin',
                        width: '90%',
                        padding: '1.5rem',
                    }).then(() => window.location.href = '../index.php');
                });
                </script>";
                exit();
            }

        } else {
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
            echo "<script>
            window.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'ขออภัย รหัสผ่านไม่ถูกต้อง',
                    text: 'กรุณาลองใหม่อีกครั้ง',
                    width: '90%',
                    padding: '1.5rem',
                }).then(() => window.location.href = '../index.php');
            });
            </script>";
        }
    } else {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
        window.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'ไม่พบชื่อผู้ใช้นี้ในระบบ',
                width: '90%',
                padding: '1.5rem',
            }).then(() => window.location.href = '../index.php');
        });
        </script>";
    }
} else {
    header('Location: ../index.php');
    exit();
}
?>
<style>
/* ปรับขนาดฟอนต์ของข้อความ */
.swal2-popup {
    font-size: 1.2rem; /* เพิ่มขนาดฟอนต์ */
}
</style>
