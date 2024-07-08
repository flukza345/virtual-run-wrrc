<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['reset_username'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if the username exists in the database
    $sql_check_username = "SELECT id FROM users WHERE username = ?";
    $stmt_check_username = $conn->prepare($sql_check_username);
    $stmt_check_username->bind_param("s", $username);
    $stmt_check_username->execute();
    $stmt_check_username->store_result();

    if ($stmt_check_username->num_rows > 0) {
        // Username exists, proceed with password reset
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

            $sql_update_password = "UPDATE users SET password = ? WHERE username = ?";
            $stmt_update_password = $conn->prepare($sql_update_password);
            $stmt_update_password->bind_param("ss", $hashed_password, $username);

            if ($stmt_update_password->execute()) {
                // Password updated successfully, show alert and redirect to index.php
                echo "<script>alert('เปลี่ยนรหัสผ่านสำเร็จ'); window.location='index.php';</script>";
                exit();
            } else {
                echo "เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน";
            }

            $stmt_update_password->close();
        } else {
            echo "รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน";
        }
    } else {
        // Username does not exist
        echo "<script>alert('ไม่พบชื่อผู้ใช้นี้ในระบบ'); window.location='index.php';</script>";
        exit();
    }

    $stmt_check_username->close();
    $conn->close();
}
?>
