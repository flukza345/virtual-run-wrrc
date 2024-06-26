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

// Approve or reject a run based on POST data
if (isset($_POST['action']) && isset($_POST['run_id'])) {
    $action = $_POST['action'];
    $run_id = $_POST['run_id'];

    if ($action == 'approve') {
        $sql_approve = "UPDATE runs SET approved = 1 WHERE id = ?";
    } elseif ($action == 'reject') {
        $sql_approve = "UPDATE runs SET approved = 0 WHERE id = ?";
    } elseif ($action == 'edit_distance' && isset($_POST['new_distance'])) {
        $new_distance = $_POST['new_distance'];
        $sql_approve = "UPDATE runs SET distance = ? WHERE id = ?";
        $stmt_approve = $conn->prepare($sql_approve);
        $stmt_approve->bind_param("di", $new_distance, $run_id);
        $stmt_approve->execute();
        $stmt_approve->close();
    } else {
        // If action is not recognized, return without processing
        header("Location: admin_panel.php");
        exit();
    }

    if (!isset($stmt_approve)) {
        $stmt_approve = $conn->prepare($sql_approve);
        $stmt_approve->bind_param("i", $run_id);
        $stmt_approve->execute();
        $stmt_approve->close();
    }
}

// Pagination setup
$results_per_page = 20;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start_from = ($page - 1) * $results_per_page;

// Fetch runs with pagination
$sql_runs = "SELECT r.id, u.name, r.distance, r.created_at, r.image_path, r.approved 
             FROM runs r
             INNER JOIN users u ON r.user_id = u.id
             ORDER BY r.created_at DESC
             LIMIT ?, ?";
$stmt_runs = $conn->prepare($sql_runs);
$stmt_runs->bind_param("ii", $start_from, $results_per_page);
$stmt_runs->execute();
$result_runs = $stmt_runs->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Running Dashboard</title>
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
        .status-approved {
            color: green;
            font-weight: bold;
        }
        .status-pending {
            color: orange;
            font-weight: bold;
        }
        .edit-button, .approve-button, .reject-button, .view-image-button {
            padding: 8px 12px;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .edit-button:hover, .approve-button:hover, .reject-button:hover, .view-image-button:hover {
            background-color: #ddd;
        }
        .edit-button {
            background-color: #4CAF50;
            color: white;
        }
        .approve-button {
            background-color: #008CBA;
            color: white;
        }
        .reject-button {
            background-color: #f44336;
            color: white;
        }
        .view-image-button {
            background-color: #2196F3;
            color: white;
        }
        /* Popup CSS */
        .popup-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
        .popup-container {
            position: fixed;
            background-color: white;
            width: 90%;
            max-width: 600px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 20px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            z-index: 1000;
        }
        .popup-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .popup-header h2 {
            margin: 0;
        }
        .popup-body {
            text-align: center;
            padding: 20px;
        }
        .popup-footer {
            text-align: right;
            margin-top: 20px;
        }
        .popup-footer button {
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .popup-footer button:hover {
            background-color: #ddd;
        }
        .close-popup {
            cursor: pointer;
            font-size: 24px;
        }
        @media (max-width: 768px) {
            .navbar-item {
                font-size: 12px;
                padding: 8px 10px;
            }
            table th, table td {
                padding: 8px;
            }
            .edit-button, .approve-button, .reject-button, .view-image-button {
                padding: 6px 8px;
                font-size: 12px;
            }
            .popup-container {
                width: 95%;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="index.php" class="navbar-item">หน้าแรก</a>
        <a href="add_distance.php" class="navbar-item active">ส่งระยะให้กับ</a>
        <a href="logout.php" class="navbar-item">Logout</a>
    </div>
    <div class="container">
        <h1>Admin Panel</h1>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Date</th>
                    <th>Distance (km)</th>
                    <th>Image</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                while ($row = $result_runs->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['distance']) . "</td>";
                    echo "<td><button class='view-image-button' onclick=\"showImagePopup('" . htmlspecialchars($row['image_path']) . "')\">View Image</button></td>";
                    if ($row['approved']) {
                        echo "<td class='status-approved'>Approved</td>";
                    } else {
                        echo "<td class='status-pending'>Pending</td>";
                    }
                    echo "<td>";
                    echo "<button class='edit-button' onclick=\"editRun(" . $row['id'] . ")\">Edit Distance</button>";
                    echo "<button class='approve-button' onclick=\"approveRun(" . $row['id'] . ")\">Approve</button>";
                    echo "<button class='reject-button' onclick=\"rejectRun(" . $row['id'] . ")\">Reject</button>";
                    echo "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <div style="text-align: center; margin-top: 20px;">
            <?php
            $sql_count = "SELECT COUNT(id) AS total FROM runs";
            $result_count = $conn->query($sql_count);
            $row_count = $result_count->fetch_assoc();
            $total_pages = ceil($row_count['total'] / $results_per_page);

            // Previous page button
            if ($page > 1) {
                echo "<a href='admin_panel.php?page=" . ($page - 1) . "' class='edit-button'>Previous</a>";
            }

            // Numbered pages
            for ($i = 1; $i <= $total_pages; $i++) {
                if ($i == $page) {
                    echo "<a href='admin_panel.php?page=" . $i . "' class='approve-button' style='background-color: #e91e63;'>" . $i . "</a>";
                } else {
                    echo "<a href='admin_panel.php?page=" . $i . "' class='approve-button'>" . $i . "</a>";
                }
            }

            // Next page button
            if ($page < $total_pages) {
                echo "<a href='admin_panel.php?page=" . ($page + 1) . "' class='reject-button'>Next</a>";
            }
            ?>
        </div>
    </div>

    <!-- Image Popup -->
    <div class="popup-overlay" id="image-popup">
        <div class="popup-container">
            <div class="popup-header">
                <h2>Running Image</h2>
                <span class="close-popup" onclick="closeImagePopup()">&times;</span>
            </div>
            <div class="popup-body">
                <img id="popup-image" src="" alt="Running Image" style="max-width: 100%; max-height: 80vh;">
            </div>
        </div>
    </div>

    <!-- Edit Distance Popup -->
    <div class="popup-overlay" id="edit-popup">
        <div class="popup-container">
            <div class="popup-header">
                <h2>Edit Distance</h2>
                <span class="close-popup" onclick="closeEditPopup()">&times;</span>
            </div>
            <div class="popup-body">
                <form id="edit-distance-form" onsubmit="return false;">
                    <input type="number" id="new-distance" name="new-distance" placeholder="New Distance (km)" required>
                    <input type="hidden" id="run-id" name="run-id">
                </form>
            </div>
            <div class="popup-footer">
                <button class="save-button" onclick="saveDistance()">Save</button>
                <button class="cancel-button" onclick="closeEditPopup()">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        function showImagePopup(imagePath) {
            var popup = document.getElementById('image-popup');
            var popupImage = document.getElementById('popup-image');

            popupImage.src = imagePath;
            popup.style.display = 'block';
        }

        function closeImagePopup() {
            var popup = document.getElementById('image-popup');
            popup.style.display = 'none';
        }

        function editRun(runId) {
            var popup = document.getElementById('edit-popup');
            var form = document.getElementById('edit-distance-form');
            var runIdInput = document.getElementById('run-id');
            runIdInput.value = runId;
            popup.style.display = 'block';
        }

        function closeEditPopup() {
            var popup = document.getElementById('edit-popup');
            popup.style.display = 'none';
        }

        function saveDistance() {
            var newDistance = document.getElementById('new-distance').value;
            var runId = document.getElementById('run-id').value;

            var xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function() {
                if (xhr.readyState === XMLHttpRequest.DONE) {
                    if (xhr.status === 200) {
                        location.reload();
                    } else {
                        alert('Error: ' + xhr.status);
                    }
                }
            };
            xhr.open('POST', 'admin_panel.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send('action=edit_distance&run_id=' + runId + '&new_distance=' + newDistance);
        }

        function approveRun(runId) {
            var xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function() {
                if (xhr.readyState === XMLHttpRequest.DONE) {
                    if (xhr.status === 200) {
                        location.reload();
                    } else {
                        alert('Error: ' + xhr.status);
                    }
                }
            };
            xhr.open('POST', 'admin_panel.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send('action=approve&run_id=' + runId);
        }

        function rejectRun(runId) {
            var xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function() {
                if (xhr.readyState === XMLHttpRequest.DONE) {
                    if (xhr.status === 200) {
                        location.reload();
                    } else {
                        alert('Error: ' + xhr.status);
                    }
                }
            };
            xhr.open('POST', 'admin_panel.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send('action=reject&run_id=' + runId);
        }
    </script>
</body>
</html>

<?php
$stmt_runs->close();
$conn->close();
?>
