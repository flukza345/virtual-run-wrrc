<?php
include 'config.php';
session_start();

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    // Get user information
    $user_id = $_SESSION['user_id'];
    
    // Fetch user profile information including profile image path
    $sql_profile = "SELECT username, profile_image FROM users WHERE id = ?";
    $stmt_profile = $conn->prepare($sql_profile);
    $stmt_profile->bind_param("i", $user_id);
    $stmt_profile->execute();
    $stmt_profile->store_result();
    $stmt_profile->bind_result($username, $profile_image);
    $stmt_profile->fetch();
    $stmt_profile->close();
    
    // Fetch user's total distance run and approved distance
    $sql_total_distance = "SELECT SUM(distance) AS total_distance FROM runs WHERE user_id = ?";
    $stmt_total_distance = $conn->prepare($sql_total_distance);
    $stmt_total_distance->bind_param("i", $user_id);
    $stmt_total_distance->execute();
    $stmt_total_distance->store_result();
    $stmt_total_distance->bind_result($total_distance);
    $stmt_total_distance->fetch();
    $stmt_total_distance->close();
    
    // Fetch user's running history and approval status
    $sql_runs = "SELECT id, distance, run_date, image_path, approved FROM runs WHERE user_id = ?";
    $stmt_runs = $conn->prepare($sql_runs);
    $stmt_runs->bind_param("i", $user_id);
    $stmt_runs->execute();
    $result_runs = $stmt_runs->get_result();
} else {
    // Redirect to index.php if user is not logged in
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Running Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f3f3f3;
        }
        .container {
            width: 80%;
            margin: 0 auto;
            padding-top: 20px;
        }
        .navbar {
            background-color: #333;
            overflow: hidden;
            border-bottom: 2px solid #e91e63;
        }
        .navbar-item {
            display: inline-block;
            color: white;
            text-align: center;
            padding: 14px 20px;
            text-decoration: none;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        .navbar-item:hover {
            background-color: #ddd;
            color: black;
        }
        .navbar img.profile {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-right: 10px;
            vertical-align: middle;
            border: 3px solid #e91e63;
        }
        .navbar a.right {
            float: right;
        }
        .navbar a.active {
            background-color: #e91e63;
        }
        .navbar .menu-icon {
            display: none;
        }
        @media screen and (max-width: 768px) {
            .navbar .menu-icon {
                display: block;
                float: right;
                cursor: pointer;
                padding: 15px 10px;
            }
            .navbar .menu-icon:hover {
                background-color: #ddd;
            }
            .navbar .menu-icon .bar {
                display: block;
                width: 25px;
                height: 3px;
                background-color: white;
                margin: 5px auto;
                transition: background-color 0.3s;
            }
            .navbar .menu-icon .bar:hover {
                background-color: #e91e63;
            }
            .navbar .menu-items {
                display: none;
                position: absolute;
                background-color: #333;
                width: 100%;
                top: 60px;
                text-align: center;
            }
            .navbar .menu-items.active {
                display: block;
            }
            .navbar .menu-items a {
                display: block;
                padding: 10px;
                color: white;
                text-decoration: none;
            }
            .navbar .menu-items a:hover {
                background-color: #ddd;
                color: black;
            }
        }
        h1 {
            color: #e91e63;
            text-align: center;
        }
        .user-info {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .user-info h2 {
            color: #e91e63;
            margin-bottom: 10px;
        }
        .user-info p {
            font-size: 18px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        table th,
        table td {
            padding: 12px;
            text-align: center;
            border: 1px solid #ddd;
        }
        table th {
            background-color: #e91e63;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            padding-top: 15px;
            padding-bottom: 15px;
        }
        table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .status-approved {
            color: green;
            font-weight: bold;
        }
        .status-pending {
            color: orange;
            font-weight: bold;
        }
        /* Popup box styles */
        .popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: rgba(0, 0, 0, 0.9);
            padding: 20px;
            border-radius: 5px;
            z-index: 1000;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
        }
        .popup-content {
            text-align: center;
            position: relative;
        }
        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            color: white;
            font-size: 30px;
            font-weight: bold;
            cursor: pointer;
        }
        .close-btn:hover {
            color: #e91e63;
        }
        .popup img {
            max-width: 100%;
            max-height: 80vh;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="menu-icon" onclick="toggleMenu()">
            <div class="bar"></div>
            <div class="bar"></div>
            <div class="bar"></div>
        </div>
        <div class="menu-items" id="menuItems">
            <a href="index.php" class="navbar-item">Home</a>
            <a href="profile.php" class="navbar-item"><img src="uploads/profiles/<?php echo htmlspecialchars($profile_image); ?>" alt="Profile Image" class="profile"> <?php echo htmlspecialchars($username); ?></a>
            <a href="upload_run.php" class="navbar-item">Upload Run</a>
            <a href="logout.php" class="navbar-item">Logout</a>
        </div>
    </div>
    <div class="container">
        <div class="user-info">
            <h2>ข้อมูลของ <?php echo htmlspecialchars($username); ?></h2>
            <?php
            $sql_approved_distance = "SELECT SUM(distance) AS approved_distance FROM runs WHERE user_id = ? AND approved = 1";
            $stmt_approved_distance = $conn->prepare($sql_approved_distance);
            $stmt_approved_distance->bind_param("i", $user_id);
            $stmt_approved_distance->execute();
            $stmt_approved_distance->store_result();
            $stmt_approved_distance->bind_result($approved_distance);
            $stmt_approved_distance->fetch();
            $stmt_approved_distance->close();
            ?>
            <p><strong>ระยะทางที่อนุมัติแล้ว:</strong> <?php echo htmlspecialchars($approved_distance); ?> km</p>
            <p><strong>ระยะทางที่รอการอนุมัติ:</strong> <?php echo htmlspecialchars($total_distance - $approved_distance); ?> km</p>
        </div>
        
        <h2>ประวัติการส่งผลวิ่ง</h2>
        <table>
            <thead>
                <tr>
                    <th>วันที่</th>
                    <th>ระยะทาง (km)</th>
                    <th>รูปภาพ</th>
                    <th>สถานะ</th>
                </tr>
            </thead>
            <tbody>
                <?php
                while ($row = $result_runs->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['run_date']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['distance']) . "</td>";
                    // Show image in a popup
                    echo "<td><button onclick=\"showImagePopup('" . htmlspecialchars($row['image_path']) . "')\">View Image</button></td>";
                    if ($row['approved']) {
                        echo "<td class='status-approved'>อนุมัติแล้ว</td>";
                    } else {
                        echo "<td class='status-pending'>รอการอนุมัติ</td>";
                    }
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Popup box -->
    <div class="popup" id="popup">
        <div class="popup-content">
            <span class="close-btn" onclick="closePopup()">&times;</span>
            <img id="popup-image" src="" alt="Popup Image">
        </div>
    </div>

    <script>
        function toggleMenu() {
            var menuItems = document.getElementById('menuItems');
            menuItems.classList.toggle('active');
        }

        function showImagePopup(imagePath) {
            var popup = document.getElementById('popup');
            var popupImage = document.getElementById('popup-image');

            popupImage.src = imagePath;
            popup.style.display = 'block';
        }

        function closePopup() {
            var popup = document.getElementById('popup');
            popup.style.display = 'none';
        }
    </script>
</body>
</html>

<?php
$stmt_runs->close();
$conn->close();
?>
