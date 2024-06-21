<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["profile_image"])) {
    $user_id = $_SESSION['user_id'];

    // Upload profile image
    $target_dir = "uploads/profiles/";
    $profile_image = basename($_FILES["profile_image"]["name"]);
    $target_file = $target_dir . $profile_image;
    move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file);

    $sql = "UPDATE users SET profile_image = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $profile_image, $user_id);

    if ($stmt->execute()) {
        echo "Profile updated successfully!";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }

    $stmt->close();
    $conn->close();
}
?>

<form method="post" action="" enctype="multipart/form-data">
    New Profile Image: <input type="file" name="profile_image" required><br>
    <input type="submit" value="Update Profile">
</form>
