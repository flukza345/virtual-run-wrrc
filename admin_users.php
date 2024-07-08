<?php
include 'config.php';
session_start();

// Check if user is logged in and is admin
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    $sql_user = "SELECT role FROM users WHERE id = ?";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $stmt_user->store_result();
    $stmt_user->bind_result($role);
    $stmt_user->fetch();

    // Redirect non-admin users to index.php
    if ($role !== 'admin') {
        header("Location: index.php");
        exit();
    }

    $stmt_user->close();
} else {
    // Redirect to index.php if user is not logged in
    header("Location: index.php");
    exit();
}

// Fetch users data
$sql_users = "SELECT id, username, name FROM users";
$result_users = $conn->query($sql_users);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Users - Running Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f3f3f3;
        }
        .container {
            width: 90%;
            margin: 0 auto;
            padding-top: 20px;
            max-width: 1200px;
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
        .navbar a.active {
            background-color: #e91e63;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
            display: block;
            white-space: nowrap;
        }
        table th, table td {
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
        .view-button {
            padding: 8px 12px;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .view-button:hover {
            background-color: #ddd;
        }
        .view-button {
            background-color: #2196F3;
            color: white;
        }
        @media (max-width: 768px) {
            .navbar-item {
                font-size: 12px;
                padding: 8px 10px;
            }
            table th, table td {
                padding: 8px;
            }
            .view-button {
                padding: 6px 8px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="index.php" class="navbar-item">หน้าแรก</a>
        <a href="admin_panel.php" class="navbar-item">ผู้ใช้</a>
        <a href="logout.php" class="navbar-item">Logout</a>
    </div>
    <div class="container">
        <h1>Admin Users</h1>
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Name</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                while ($row = $result_users->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                    echo "<td><button class='view-button' onclick=\"viewDetails(" . $row['id'] . ")\">View Details</button></td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <script>
        function viewDetails(userId) {
            // Redirect to view user details page with userId
            window.location.href = 'view_user_details.php?id=' + userId;
        }
    </script>
</body>
</html>

<?php
$conn->close();
?>
