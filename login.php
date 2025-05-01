<?php 
include("connection.php");
session_start(); // Start the session

if (isset($_POST['submit'])) {
    $username = $_POST['user'];
    $password = $_POST['pass'];

    // Prepare and bind
    $stmt = $conn->prepare("SELECT id, username, first_login FROM login WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $username, $password);

    // Execute the statement
    $stmt->execute();

    // Get the result
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        // Fetch the user details
        $row = $result->fetch_assoc();
        $login_id = $row['id'];
        $first_login = $row['first_login'];
        $logged_in_user = $row['username']; // Get the username

        // Store login ID and username in session
        $_SESSION['login_id'] = $login_id;
        $_SESSION['username'] = $logged_in_user; // Store username in session

        // Check if it's the first login
        if ($first_login == 1) {
            // Redirect to change_password.php for first-time login
            header("Location: change_password.php");
        } else {
            // Redirect to the dashboard
            header("Location: dashboard.php");
        }
        exit();
    } else {
        echo '<script>
          alert("Login failed. Invalid username or password");
          window.location.href = "index.php";
        </script>';
    }

    // Close the statement
    $stmt->close();
}
?>
