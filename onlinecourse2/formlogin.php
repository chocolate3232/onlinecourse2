
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link rel="stylesheet" href="css/login1.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800&display=swap" rel="stylesheet">
    
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<style>
  /* Media query สำหรับอุปกรณ์ที่มีขนาดหน้าจอเล็ก (สูงสุด 768px) */
@media (max-width: 768px) {
    .container {
        flex-direction: column; /* เปลี่ยนให้ container อยู่ในแนวตั้ง */
        width: 100%; /* เพิ่มความกว้างของ container */
        height: 100%;
    }
    .signup-form {
        width: 100%;
        padding: 20px;
        order: 1; /* ฟอร์มจะอยู่ข้างบน */
    }

    .signup-form h2 {
        font-size: 24px;
        margin-bottom: 15px;
    }

    .signup-form input[type="text"],
    .signup-form input[type="email"],
    .signup-form input[type="password"] {
        font-size: 14px;
    }

    .btn {
        width: 100%;
        font-size: 16px;
    }

    .signin {
        font-size: 14px;
    }
}

/* Media query สำหรับหน้าจอที่เล็กที่สุด (สูงสุด 480px) */
@media (max-width: 480px) {
    .container {
        width: 100%
        padding: 10px;
        height: 100%;
    }

  .image-background {
    width: 100px;
    background: url('https://img.pikbest.com/wp/202405/cyber-security-technology-3d-rendering-of-background-with-glossy-black-locks-symbolizing-and-data-protection_9793091.jpg!w700wp') no-repeat center center/cover;
    background-blend-mode: overlay;
    background-color: rgb(116, 124, 196);
}

    .signup-form h2 {
        font-size: 22px;
        margin-bottom: 10px;
    }

    .signup-form input[type="text"],
    .signup-form input[type="email"],
    .signup-form input[type="password"] {
        font-size: 12px; /* ลดขนาดฟอนต์ให้เหมาะสมกับหน้าจอเล็ก */
    }

    .btn {
        font-size: 14px;
    }

    .signin {
        font-size: 12px;
    }
}

</style>
<body>
    <div class="container">
        <div class="image-background">
        </div>
        <div class="signup-form">
    <center><h2>LMS</h2></center>
    <h2>Login</h2>

    <form action='command/login.php' method="POST">
        
        <label>รหัสนักศึกษา</label>
        <input type="text" name="username" required>

        <label>รหัสผ่าน</label>
        <input type="password" name="password" required>

        <div style="height: 140px;"></div>

        <!-- Remember Me Checkbox -->
        <label>
            <input type="checkbox" name="remember" value="1"> จดจำฉัน
        </label>
        
        <button type="submit" class="btn" name="submit">เข้าสู่ระบบ</button>
        <div style="height: 60px;"></div>
        
    </form>
</div>

        </div>
    </div>
</body>
</html>
