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
    } elseif ($action == 'edit') {
        // Redirect to edit_run.php with run_id
        header("Location: edit_run.php?run_id=" . $run_id);
        exit();
    }

    $stmt_approve = $conn->prepare($sql_approve);
    $stmt_approve->bind_param("i", $run_id);
    $stmt_approve->execute();
    $stmt_approve->close();
}

// Edit distance for a run based on POST data
if (isset($_POST['action']) && $_POST['action'] == 'edit' && isset($_POST['run_id']) && isset($_POST['new_distance'])) {
    $run_id = $_POST['run_id'];
    $new_distance = $_POST['new_distance'];

    $sql_edit_distance = "UPDATE runs SET distance = ? WHERE id = ?";
    $stmt_edit_distance = $conn->prepare($sql_edit_distance);
    $stmt_edit_distance->bind_param("di", $new_distance, $run_id);
    $stmt_edit_distance->execute();
    $stmt_edit_distance->close();
}

// Fetch all runs for admin panel
$sql_runs = "SELECT r.id, u.username, r.distance, r.run_date, r.image_path, r.approved 
             FROM runs r
             INNER JOIN users u ON r.user_id = u.id
             ORDER BY r.run_date DESC";
$result_runs = $conn->query($sql_runs);
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
        <a href="index.php" class="navbar-item">Home</a>
        <a href="admin_panel.php" class="navbar-item active">Admin Panel</a>
        <a href="logout.php" class="navbar-item">Logout</a>
    </div>
    <div class="container">
        <h1>Admin Panel - Running Dashboard</h1>
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Date</th>
                    <th>Distance (km)</th>
                    <th>Image</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                while ($row = $result_runs->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['run_date']) . "</td>";
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
                <form id="edit-distance-form">
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
            var form = document.getElementById('edit-distance-form');
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
            var formData = new FormData(form);
            xhr.send(new URLSearchParams(formData).toString());
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
$conn->close();
?>
