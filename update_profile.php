<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("location: index.php");
    exit;
}


$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["profile_image"])) {
    $user_id = $_SESSION['user_id'];

    // Upload profile image
    $target_dir = "uploads/profiles/";
    $profile_image = basename($_FILES["profile_image"]["name"]);
    $target_file = $target_dir . $profile_image;
    move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file);

    $sql = "UPDATE users SET profile_image = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $profile_image, $user_id);

    if ($stmt->execute()) {
        // Profile updated successfully
        $message = "Profile updated successfully!";
        echo "<script>
                alert('$message');
                window.location.href = 'profile.php';
              </script>";
        exit; // Ensure no further output
    } else {
        // Error updating profile
        $message = "Error: " . $sql . "<br>" . $conn->error;
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
    <title>เปลี่ยนรูปโปรไฟล์</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f3f3f3;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 80%;
            margin: 0 auto;
            text-align: center;
            padding-top: 20px;
        }
        h1 {
            color: #e91e63;
        }
        form {
            display: inline-block;
            text-align: left;
            margin-top: 20px;
        }
        input[type="file"], input[type="submit"] {
            display: block;
            margin: 10px 0;
            padding: 10px;
            width: 100%;
            box-sizing: border-box;
        }
        .navbar {
            overflow: hidden;
            background-color: #333;
            margin-bottom: 20px;
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
        .navbar a.right {
            float: right;
        }
        input[type="submit"] {
    background-color: #e91e63;
    color: white;
    border: none;
    border-radius: 5px;
    font-size: 18px;
    padding: 15px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

input[type="submit"]:hover {
    background-color: #c2185b;
}

input[type="submit"]:active {
    background-color: #a31545;
}

    </style>
</head>
<body>
    <div class="navbar">
    <a href="javascript:history.back()" class="back-button">ย้อนกลับ</a>
    </div>
    <div class="container">
        <h1>เปลี่ยนรูปโปรไฟล์</h1>
        <form method="post" action="" enctype="multipart/form-data">
           เลือกรูปภาพที่ต้องการเปลี่ยน: <input type="file" name="profile_image" required><br>
            <input type="submit" value="ตกลง">
        </form>
    </div>

    <!-- Script for displaying popup -->
    <script>
        <?php if (!empty($message)): ?>
            alert('<?php echo $message; ?>');
            window.location.href = 'profile.php';
        <?php endif; ?>
    </script>
</body>
</html>

