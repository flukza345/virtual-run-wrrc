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
        }
        form {
            display: inline-block;
            text-align: left;
        }
        input[type="text"], input[type="password"], input[type="file"], input[type="submit"] {
            display: block;
            margin: 10px 0;
            padding: 10px;
            width: 100%;
            box-sizing: border-box;
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
        <a href="index.php">Home</a>
        <a href="register.php">Register</a>
    </div>
    <div class="container">
        <h1>Register</h1>
        <form method="post" action="" enctype="multipart/form-data">
            Username: <input type="text" name="username" required><br>
            Name: <input type="text" name="name" required><br> <!-- เพิ่มฟิลด์ 'name' -->
            Password: <input type="password" name="password" required><br>
            Confirm Password: <input type="password" name="confirm_password" required><br>
            Profile Image: <input type="file" name="profile_image" required><br>
            <input type="submit" value="Register">
        </form>
    </div>
</body>
</html>
