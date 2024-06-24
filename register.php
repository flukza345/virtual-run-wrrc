<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $name = $_POST['name'];
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
    
    // Get the file extension
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Check if image file is a actual image or fake image
    $check = getimagesize($_FILES["profile_image"]["tmp_name"]);
    if($check !== false) {
        // Allow certain file formats
        if ($imageFileType != "jpg" && $imageFileType != "jpeg" && $imageFileType != "png" && $imageFileType != "HEIC" && $imageFileType != "") {
            echo "<script>alert('Sorry, only JPG, JPEG, PNG files are allowed.'); window.location.href='register.php';</script>";
            exit();
        }

        // Handle HEIC file type (Convert HEIC to JPEG)
        if ($imageFileType == "heic" || $imageFileType == "heif") {
            // Use Imagick to convert HEIC/HEIF to JPEG
            $imagick = new Imagick($_FILES["profile_image"]["tmp_name"]);
            $imagick->setImageFormat("jpeg");

            // Save the converted JPEG file
            $target_file = $upload_dir . uniqid() . ".jpg";
            if ($imagick->writeImage($target_file)) {
                // Successfully converted and saved as JPEG
                $message = "File is converted and uploaded successfully.";
            } else {
                // Failed to convert
                $message = "Sorry, there was an error converting your file.";
                echo "<script>alert('$message'); window.location.href='register.php';</script>";
                exit();
            }
        } else {
            // Directly move uploaded file if it's already JPEG or PNG
            move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file);
        }

        // Insert user data into database
        $sql = "INSERT INTO users (username, name, password, profile_image) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $username, $name, $hashed_password, $target_file);

        if ($stmt->execute()) {
            echo "<script>alert('Registration successful!'); window.location.href='index.php';</script>";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }

        $stmt->close();
        $conn->close();
    } else {
        echo "<script>alert('File is not an image.'); window.location.href='register.php';</script>";
        exit();
    }
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
        font-size: 28px;
        margin-bottom: 20px;
    }
    form {
        display: inline-block;
        text-align: left;
        max-width: 400px;
        margin: 0 auto;
    }
    input[type="text"], input[type="password"], input[type="file"], input[type="submit"] {
        display: block;
        margin: 10px 0;
        padding: 10px;
        width: calc(100% - 20px);
        box-sizing: border-box;
        border: 1px solid #ccc;
        border-radius: 4px;
    }
    input[type="file"] {
        padding: 8px;
    }
    input[type="submit"] {
        background-color: #e91e63;
        color: white;
        padding: 12px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        transition: background-color 0.3s;
        width: 100%;
        font-size: 16px;
    }
    input[type="submit"]:hover {
        background-color: #d81b60;
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
            ชื่อ: <input type="text" name="name" required><br>
            ชื่อผู้ใช้: <input type="text" name="username" required><br>
            รหัสผ่าน: <input type="password" name="password" required><br>
            ยืนยันรหัสผ่าน: <input type="password" name="confirm_password" required><br>
            รูปภาพ: <input type="file" name="profile_image"><br>
            <input type="submit" value="ลงทะเบียน">
        </form>
    </div>
</body>
</html>
