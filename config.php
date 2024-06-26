<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "running_app";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!is_dir("uploads/profiles")) {
    mkdir("uploads/profiles", 0777, true);
}
?>
