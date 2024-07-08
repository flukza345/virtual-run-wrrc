<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['reset_username'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password === $confirm_password) {
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

        $sql = "UPDATE users SET password = ? WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $hashed_password, $username);

        if ($stmt->execute()) {
            // เปลี่ยนรหัสผ่านสำเร็จ แสดง Alert และ Redirect ไปยังหน้าแรก
            echo "<script>alert('เปลี่ยนรหัสผ่านสำเร็จ'); window.location='index.php';</script>";
            exit();
        } else {
            echo "เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน";
        }

        $stmt->close();
    } else {
        echo "รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน";
    }

    $conn->close();
}
?>
