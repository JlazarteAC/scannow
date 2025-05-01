<?php
session_start();
include("connection.php");

// Ensure the user is logged in
if (!isset($_SESSION['login_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        echo '<script>alert("Passwords do not match.");</script>';
    } else {
        $login_id = $_SESSION['login_id'];

        // Update the password and mark first_login as completed
        $stmt = $conn->prepare("UPDATE login SET password = ?, first_login = 0 WHERE id = ?");
        $stmt->bind_param("si", $new_password, $login_id);

        if ($stmt->execute()) {
            echo '<script>
                alert("Password changed successfully.");
                window.location.href = "dashboard.php";
            </script>';
        } else {
            echo '<script>alert("Error updating password.");</script>';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link rel="stylesheet" href="./css/bootstrap.css">
    <link rel="stylesheet" href="style.css">
</head>
<body >
<section class="background-radial-gradient overflow-hidden d-flex justify-content-center align-items-center vh-100">
    <div class="container bg-glass mt-5 border border-2 p-3">
        <h2>Change Password</h2>
        <form method="POST" action="">
            <div class="mb-3">
                <label for="new_password" class="form-label border">New Password</label>
                <input type="password" class="form-control" id="new_password" name="new_password" required>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-primary">Change Password</button>
        </form>
    </div>
</section>
</body>
</html>
