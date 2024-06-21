<?php
include 'config.php';
session_start();

// Redirect user to login page if not logged in
if (!isset($_SESSION['user_id'])) {
    header("location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $distance = $_POST['distance'];
    $run_date = $_POST['run_date'];

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
    $sql = "INSERT INTO runs (user_id, distance, run_date, image_path, approved) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("idssi", $user_id, $distance, $run_date, $target_file, $approved);

    if ($stmt->execute()) {
        echo "<script>alert('Run uploaded successfully! Please wait for admin approval.'); window.location.href='index.php';</script>";
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
        input[type="text"], input[type="date"], input[type="file"], input[type="submit"] {
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
    </style>
</head>
<body>
    <div class="navbar">
        <a href="index.php">Home</a>
        <a href="profile.php">Profile</a>
        <a href="upload_run.php">Upload Run</a>
        <a href="logout.php" style="float: right;">Logout</a>
    </div>
    <div class="container">
        <h1>Upload Run</h1>
        <form method="post" action="" enctype="multipart/form-data">
            Distance (km): <input type="text" name="distance" required><br>
            Date: <input type="date" name="run_date" required><br>
            Image: <input type="file" name="image" required><br>
            <input type="submit" value="Upload">
        </form>
    </div>
</body>
</html>
