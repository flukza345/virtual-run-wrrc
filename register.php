<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $name = $_POST['name']; // เพิ่มการรับค่า 'name' จากฟอร์ม
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if passwords match
    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match. Please try again.'); window.location.href='register.php';</script>";
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Check if username already exists
    $check_sql = "SELECT id FROM users WHERE username = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        $check_stmt->close();
        $conn->close();
        echo "<script>alert('Username already exists. Please choose a different username.'); window.location.href='register.php';</script>";
        exit();
    }

    // Proceed with registration if username is not taken
    $upload_dir = "uploads/profiles/";
    $profile_image = $_FILES['profile_image']['name'];
    $target_file = $upload_dir . basename($profile_image);
    move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file);

    $sql = "INSERT INTO users (username, name, password, profile_image) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $username, $name, $hashed_password, $profile_image); // เพิ่ม 'name' ในการ bind_param

    if ($stmt->execute()) {
        echo "<script>alert('Registration successful!'); window.location.href='index.php';</script>";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <style>
    body {
        background-color: #f3f3f3;
        font-family: Arial, sans-serif;
        color: #333;
    }
    .container {
        width: 80%;
        margin: 0 auto;
        text-align: center;
    }
    h1 {
        color: #e91e63;
        font-size: 28px; /* ปรับขนาดตัวหนังสือใหญ่ขึ้น */
        margin-bottom: 20px; /* ขอบล่างของ h1 */
    }
    form {
        display: inline-block;
        text-align: left;
        max-width: 400px; /* ปรับขนาดฟอร์มให้ไม่เกินความกว้างของหน้าจอ */
        margin: 0 auto; /* จัดตำแหน่งกลางฟอร์มใน container */
    }
    input[type="text"], input[type="password"], input[type="file"], input[type="submit"] {
        display: block;
        margin: 10px 0;
        padding: 10px;
        width: calc(100% - 20px); /* คำนวณขนาดเพื่อให้พอดีกับขนาด input */
        box-sizing: border-box;
        border: 1px solid #ccc; /* เส้นขอบของช่องกรอกข้อมูล */
        border-radius: 4px; /* มุมขอบช่องกรอกข้อมูล */
    }
    input[type="file"] {
        padding: 8px;
    }
    input[type="submit"] {
        background-color: #e91e63; /* สีพื้นหลังปุ่ม (ชมพู) */
        color: white; /* สีตัวอักษรของปุ่ม */
        padding: 12px 20px; /* ขนาดของ padding ในปุ่ม */
        border: none; /* ไม่มีเส้นขอบ */
        border-radius: 4px; /* มุมขอบปุ่ม */
        cursor: pointer; /* เปลี่ยนเคอร์เซอร์เป็นลูกศรเมื่อชี้ที่ปุ่ม */
        transition: background-color 0.3s; /* เพิ่มเอฟเฟกต์การเปลี่ยนสีพื้นหลัง */
        width: 100%; /* ปรับขนาดให้เต็มความกว้าง */
        font-size: 16px; /* ปรับขนาดตัวหนังสือในปุ่ม */
    }
    input[type="submit"]:hover {
        background-color: #d81b60; /* เปลี่ยนสีพื้นหลังเมื่อชี้ที่ปุ่ม */
    }
    .navbar {
        overflow: hidden;
        background-color: #333;
    }
    .navbar a {
        float: left;
        display: block;
        color: white;
        text-align: center;
        padding: 14px 20px;
        text-decoration: none;
    }
    .navbar a:hover {
        background-color: #ddd;
        color: black;
    }
</style>

</head>
<body>
    <div class="navbar">
    <a href="javascript:history.back()" class="back-button">ย้อนกลับ</a>
    </div>
    <div class="container">
        <h1>ลงทะเบียน</h1>
        <form method="post" action="" enctype="multipart/form-data">
            ชื่อ: <input type="text" name="name" required><br> <!-- เพิ่มฟิลด์ 'name' -->
            ชื่อผู้ใช้: <input type="text" name="username" required><br>
            รหัสผ่าน: <input type="password" name="password" required><br>
            ยืนยันรหัสผ่าน: <input type="password" name="confirm_password" required><br>
            รูปภาพ: <input type="file" name="profile_image"><br>
            <input type="submit" value="ลงทะเบียน">
        </form>
    </div>
</body>
</html>
