<?php
include 'config.php';

// Check if admin is logged in
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("location: login.php");
    exit;
}

// Approve run based on run ID
if (isset($_GET['id'])) {
    $run_id = $_GET['id'];

    $sql = "UPDATE runs SET approved = 1 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $run_id);

    if ($stmt->execute()) {
        header("location: admin_panel.php");
        exit;
    } else {
        echo "Error updating record: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
}
?>
