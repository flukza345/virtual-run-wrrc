<?php
session_start(); // เริ่ม session (หากยังไม่เริ่ม)
if (!isset($_SESSION['username'])) { // ถ้ายังไม่ได้เข้าสู่ระบบให้ redirect ไปยังหน้า login.php
    header("Location: login.php");
    exit();
}

// Include config file
require_once "config.php";

// เริ่มการตรวจสอบหลังจากที่ผู้ใช้กด submit form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['user'], $_POST['distance']) && !empty($_FILES['image'])) {
        $user_id = $_POST['user'];
        $distance = $_POST['distance'];

        // File upload variables
        $file_name = $_FILES['image']['name'];
        $file_size = $_FILES['image']['size'];
        $file_tmp = $_FILES['image']['tmp_name'];
        $file_type = $_FILES['image']['type'];
        $file_error = $_FILES['image']['error'];

        // Check file extension
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $extensions = array("jpeg", "jpg", "png");

        if (in_array($file_ext, $extensions)) {
            if ($file_error === 0) {
                if ($file_size < 5242880) { // 5 MB limit for file size
                    // Prepare image for database storage
                    $imgContent = addslashes(file_get_contents($file_tmp));

                    // Insert distance and image into database
                    $sql = "INSERT INTO runs (user_id, distance, image_path, created_at) VALUES (?, ?, ?, NOW())";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ids", $user_id, $distance, $imgContent);

                    if ($stmt->execute()) {
                        $message = "Distance and image added successfully!";
                    } else {
                        $message = "Error: " . $conn->error;
                    }

                    $stmt->close();
                } else {
                    $message = "File size exceeds 5 MB limit.";
                }
            } else {
                $message = "Error uploading file.";
            }
        } else {
            $message = "Invalid file type. Only JPEG, JPG, and PNG files are allowed.";
        }
    } else {
        $message = "Please select a user, enter distance, and upload an image.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Run Distance and Image</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f3f3f3;
            margin: 0;
            padding: 20px;
        }
        .navbar {
            background-color: #333;
            overflow: hidden;
            border-bottom: 2px solid #e91e63;
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
        }
        .navbar-item {
            color: white;
            text-align: center;
            padding: 10px 15px;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        .navbar-item:hover {
            background-color: #ddd;
            color: black;
        }
        .navbar a {
            float: left;
            display: block;
            color: #f2f2f2;
            text-align: center;
            padding: 14px 20px;
            text-decoration: none;
        }
        .navbar a:hover {
            background-color: #ddd;
            color: black;
        }
        .navbar a.logout {
            float: right;
        }
        .navbar a.active {
            background-color: #e91e63;
        }
        .content {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        label {
            font-weight: bold;
            margin-bottom: 10px;
            display: block;
        }
        input[type="number"], select {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        input[type="file"] {
            margin-bottom: 20px;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
        }
        .message-popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
            z-index: 1000;
        }
        .message-popup p {
            margin-bottom: 10px;
        }
        .message-popup button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        .message-popup button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
<div class="navbar">
    <a href="index.php" class="navbar-item">หน้าแรก</a>
    <a href="admin_panel.php" class="navbar-item active">Admin Panel</a>
    <a href="logout.php" class="navbar-item">Logout</a>
</div>
<br>

<div class="content">
    <h1>Add Run Distance and Image</h1>
    <form action="add_distance.php" method="POST" enctype="multipart/form-data">
        <label for="user">Select User:</label>
        <select id="user" name="user" required>
            <option value="">Select User</option>
            <?php
            // Fetch all users
            $sql_users = "SELECT id, name FROM users";
            $result_users = $conn->query($sql_users);

            if ($result_users->num_rows > 0) {
                while ($row = $result_users->fetch_assoc()) {
                    echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['name']) . "</option>";
                }
            }
            ?>
        </select>
        <label for="distance">Distance (km):</label>
        <input type="number" id="distance" name="distance" placeholder="Enter distance in kilometers" required>
        <label for="image">Upload Image:</label>
        <input type="file" id="image" name="image" accept="image/*" required>
        <input type="submit" value="Add Distance and Image">
    </form>
</div>

<!-- Message Popup -->
<?php if (isset($message)): ?>
    <div class="message-popup">
        <p><?php echo $message; ?></p>
        <button onclick="closeMessagePopup()">OK</button>
    </div>
<?php endif; ?>

<script>
    function closeMessagePopup() {
        var popup = document.querySelector('.message-popup');
        popup.style.display = 'none';
        window.location.href = 'admin_panel.php'; // Redirect to admin_panel.php
    }

    // Show message popup if PHP message is set
    <?php if (isset($message)): ?>
        var popup = document.querySelector('.message-popup');
        popup.style.display = 'block';
    <?php endif; ?>
</script>

</body>
</html>

<?php $conn->close(); ?>
