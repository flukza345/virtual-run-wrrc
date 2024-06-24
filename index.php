<?php
include 'config.php';
session_start();

$current_month = date('Y-m');

$sql = "SELECT users.name, users.profile_image, SUM(runs.distance) as total_distance 
        FROM runs 
        JOIN users ON runs.user_id = users.id 
        WHERE DATE_FORMAT(runs.created_at, '%Y-%m') = ? AND runs.approved = 1
        GROUP BY users.name 
        ORDER BY total_distance DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $current_month);
$stmt->execute();
$result = $stmt->get_result();

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    // Get the name, profile image, and role of the logged-in user
    $user_id = $_SESSION['user_id'];
    $sql_user = "SELECT name, profile_image, role FROM users WHERE id = ?";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $stmt_user->store_result();
    $stmt_user->bind_result($name, $profile_image, $role);
    $stmt_user->fetch();
    $stmt_user->close();

    // Navbar links based on role
    $navbar_links = '<a href="profile.php" class="navbar-item"><img src="' . htmlspecialchars($profile_image) . '" alt="Profile Image" class="profile"> ' . htmlspecialchars($name) . '</a>';
    if ($role == 'admin') {
        $navbar_links .= '<a href="admin_panel.php" class="navbar-item">Admin Panel</a>';
    }
    $navbar_links .= '<a href="upload_run.php" class="navbar-item">ส่งผลการวิ่ง</a>';
    $logout_button = '<a href="logout.php" class="navbar-item">ออกจากระบบ</a>';
} else {
    $navbar_links = '<a href="index.php" class="navbar-item">หน้าแรก</a>';
    $navbar_links .= '<a href="register.php" class="navbar-item">ลงทะเบียน</a>'; // เพิ่มลิงก์สำหรับ Register
    $logout_button = '<a href="javascript:void(0)" onclick="document.getElementById(\'loginModal\').style.display=\'block\'" class="navbar-item">เข้าสู่ระบบ</a>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Running Dashboard</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
        }
        .navbar-item {
            color: white;
            text-decoration: none;
            font-size: 16px;
            padding: 14px 20px;
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
        .navbar .logo {
            width: auto;
            height: 73px; /* Adjust the height to fit nicely in the navbar */
        }
        .menu-icon {
            display: none;
            cursor: pointer;
        }
        .menu-icon .bar {
            width: 25px;
            height: 3px;
            background-color: white;
            margin: 5px auto;
            transition: background-color 0.3s;
        }
        @media screen and (max-width: 768px) {
            .menu-icon {
                display: block;
            }
            .menu-items {
                display: none;
                position: absolute;
                background-color: #333;
                width: 100%;
                top: 99px;
                text-align: center;
            }
            .menu-items.active {
                display: block;
            }
            .menu-items a {
                display: block;
                padding: 10px;
                color: white;
                text-decoration: none;
            }
            .menu-items a:hover {
                background-color: #ddd;
                color: black;
            }
        }
        h1 {
            color: #e91e63;
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
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
        .profile-info {
            display: flex;
            align-items: center;
        }
        .profile-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            border: 2px solid #e91e63;
        }
        .profile-info span {
            font-weight: bold;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
            padding-top: 60px;
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 400px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        .modal-content input[type="text"],
        .modal-content input[type="password"],
        .modal-content input[type="submit"] {
            display: block;
            margin: 10px 0;
            padding: 10px;
            width: 100%;
            box-sizing: border-box;
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
        <a href="index.php">
            <img src="image/LOGO-01.png" alt="Logo" class="logo">
        </a>
        <div class="menu-icon" onclick="toggleMenu()">
            <div class="bar"></div>
            <div class="bar"></div>
            <div class="bar"></div>
        </div>
        <div class="menu-items" id="menuItems">
            <?php echo $navbar_links; ?>
            <?php echo $logout_button; ?>
        </div>
    </div>
    <div class="container">
        <h1>เดินวิ่งกลิ้งคลาน วิ่งสะสมระยะ ครั้งที่1</h1>
        <table>
            <thead>
                <tr>
                    <th>อันดับ</th>
                    <th>ผู้เข้าร่วม</th>
                    <th>ระยะสะสม (กิโลเมตร)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $rank = 1;
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $rank++ . "</td>";
                    echo "<td class='profile-info'><img class='profile' src='uploads/profiles/" . htmlspecialchars($row['profile_image']) . "' alt='Profile Image'><span>" . htmlspecialchars($row['name']) . "</span></td>";
                    echo "<td>" . $row['total_distance'] . "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Login Modal -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('loginModal').style.display='none'">&times;</span>
            <form method="post" action="login.php">
                <label for="username">ชื่อผู้ใช้:</label>
                <input type="text" id="username" name="username" required>
                <label for="password">รหัสผ่าน:</label>
                <input type="password" id="password" name="password" required>
                <input type="submit" value="เข้าสู่ระบบ">
            </form>
        </div>
    </div>

    <script>
        function toggleMenu() {
            var menuItems = document.getElementById('menuItems');
            menuItems.classList.toggle('active');
        }

        // Close the modal when clicking outside of it
        window.onclick = function(event) {
            var modal = document.getElementById('loginModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
