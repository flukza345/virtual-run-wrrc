<?php
include 'config.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if run_id and new_distance are provided in POST data
if (!isset($_POST['run-id']) || !isset($_POST['new-distance'])) {
    header("Location: admin_panel.php"); // หากไม่มีข้อมูลส่งมาให้ redirect กลับไปที่หน้า admin_panel.php
    exit();
}

$run_id = $_POST['run-id'];
$new_distance = $_POST['new-distance'];

// Fetch the run details
$sql = "SELECT user_id FROM runs WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $run_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Run not found.";
    exit();
}

$row = $result->fetch_assoc();

// Check if the logged-in user is the owner of the run
if ($row['user_id'] != $user_id) {
    header("Location: admin_panel.php"); // หากไม่ใช่เจ้าของให้ redirect กลับไปที่หน้า admin_panel.php
    exit();
}

// Update the distance in the database
$sql_update = "UPDATE runs SET distance = ? WHERE id = ?";
$stmt_update = $conn->prepare($sql_update);
$stmt_update->bind_param("di", $new_distance, $run_id);

if ($stmt_update->execute()) {
    // Success
    echo "Distance updated successfully.";
} else {
    // Error
    echo "Error updating distance: " . $conn->error;
}

$stmt_update->close();
$conn->close();
?>
