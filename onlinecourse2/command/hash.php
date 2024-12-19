
<?php
require_once 'conn.php';

// ดึงข้อมูลผู้ใช้ที่ยังไม่ได้เข้ารหัสรหัสผ่าน
$re = $conn->prepare("SELECT * FROM on_member WHERE member_code = '65301280055' ");
$re->execute();
$members = $re->fetchAll(PDO::FETCH_ASSOC);

foreach ($members as $member) {
    // ตรวจสอบว่ารหัสผ่านถูกเข้ารหัสหรือยัง
    if (!password_verify('test', $member['member_password']) && strlen($member['member_password']) < 60) { // assume <60 chars means not hashed
        // เข้ารหัสรหัสผ่านใหม่
        $hashedPassword = password_hash($member['member_password'], PASSWORD_DEFAULT);

        // อัปเดตรหัสผ่านในฐานข้อมูล
        $update = $conn->prepare("UPDATE on_member SET member_password = :hashedPassword WHERE member_id = :id");
        $update->bindParam(':hashedPassword', $hashedPassword);
        $update->bindParam(':id', $member['member_id']);
        $update->execute();
    }
}

echo "เข้ารหัสรหัสผ่านเรียบร้อยแล้ว";


?>
