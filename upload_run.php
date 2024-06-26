<?php
include 'config.php';
session_start();

// Redirect user to login page if not logged in
if (!isset($_SESSION['user_id'])) {
    header("location: login.php");
    exit;
}

// ฟังก์ชันสำหรับการส่งการแจ้งเตือนไปยัง LINE
function sendLineNotify($message, $image_path) {
    $access_token = 'Tb8ahI7AiCzmf52vNGTLedoF6T1WeF9Lm9shKB7U2qG'; // แทนที่ด้วย Access Token ของคุณ
    $api_url = 'https://notify-api.line.me/api/notify';

    $data = array('message' => $message);
    $headers = array(
        'Content-Type: multipart/form-data',
        'Authorization: Bearer ' . $access_token
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);

    // เพิ่มรูปภาพถ้ามี
    if ($image_path && file_exists($image_path)) {
        $data['imageFile'] = new CURLFile($image_path);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }

    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $distance = $_POST['distance'];

    $upload_dir = "uploads/";
    $image_name = $_FILES['image']['name'];
    $target_file = $upload_dir . basename($image_name);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if image file is a actual image or fake image
    $check = getimagesize($_FILES['image']['tmp_name']);
    if ($check === false) {
        echo "Error: File is not an image.";
        exit;
    }

    // Check if file already exists
    if (file_exists($target_file)) {
        echo "Error: File already exists.";
        exit;
    }

    // Check file size (maximum 5MB)
    if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
        echo "Error: File size exceeds maximum limit (5MB).";
        exit;
    }

    // Allow certain file formats
    $allowed_formats = array("jpg", "jpeg", "png", "gif");
    if (!in_array($imageFileType, $allowed_formats)) {
        echo "Error: Only JPG, JPEG, PNG, and GIF files are allowed.";
        exit;
    }

    // Move uploaded file to destination directory
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
        echo "Error: There was an error uploading your file.";
        exit;
    }

    // Set approved status to 0 (pending approval)
    $approved = 0;

    // Prepare and execute SQL statement to insert run data into database
    $sql = "INSERT INTO runs (user_id, distance, image_path, approved) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("idsi", $user_id, $distance, $target_file, $approved);

    if ($stmt->execute()) {
        // ดึงชื่อผู้ใช้จากฐานข้อมูล
        $user_sql = "SELECT name FROM users WHERE id = ?";
        $user_stmt = $conn->prepare($user_sql);
        $user_stmt->bind_param("i", $user_id);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        $user = $user_result->fetch_assoc();
        $user_name = $user['name'];

        // สร้างข้อความแจ้งเตือน
        $message = "ชื่อ: $user_name\nระยะทาง: $distance กิโลเมตร\nสถานะ: รออนุมัติ";
        sendLineNotify($message, $target_file);

        echo "<script>alert('!!..ส่งผลการวิ่งสำเร็จ รอแอดมิน อนุมัติผล..!!'); window.location.href='index.php';</script>";
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
    <title>Upload Run</title>
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
        input[type="text"], input[type="file"], input[type="submit"] {
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

        /* Add styles for submit button */
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
        <h1>ส่งผลการวิ่ง</h1>
        <form method="post" action="" enctype="multipart/form-data">
            ระยะ (กิโลเมตร): <input type="text" name="distance" required><br>
            รูปภาพอ้างอิง: <input type="file" name="image" required><br>
            <input type="submit" value="ส่งผล">
        </form>
    </div>
</body>
</html>
