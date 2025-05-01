<?php
session_start();
include("activity_logger.php");
include("connection.php");

// Fetch and validate user ID
$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$user_id) {
    header("Location: dashboard.php?error=user_id_invalid");
    exit();
}

// Fetch user details
$stmt = $conn->prepare("
    SELECT user.*, department.department_name, status.status_name
    FROM user
    JOIN status ON user.status_id = status.status_id
    JOIN department ON status.department_id = department.department_id
    WHERE user.id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: dashboard.php?error=user_not_found");
    exit();
}

// Log the action
if (isset($_SESSION['login_id'])) {
    $admin_id = $_SESSION['login_id'];
    $description = "Viewed User Profile of " . htmlspecialchars($user['first_name']) . " " . htmlspecialchars($user['last_name']);
    logAdminActivity($admin_id, "view_user.php", "VIEW", $description);
}

// Prepare and bind for fetching log entry data (only Access Granted)
$log_entry_stmt = $conn->prepare("
    SELECT timestamp 
    FROM log_entry
    WHERE school_id = ? AND status = 'Access Granted'
    ORDER BY timestamp DESC
");
$log_entry_stmt->bind_param("s", $user['school_id']);
$log_entry_stmt->execute();
$log_entry_result = $log_entry_stmt->get_result();
$log_entries = $log_entry_result->fetch_all(MYSQLI_ASSOC);
$log_entry_stmt->close();

// Prepare and bind for fetching log exit data (only Access Granted)
$log_exit_stmt = $conn->prepare("
    SELECT timestamp 
    FROM log_exit
    WHERE school_id = ? AND status = 'Access Granted'
    ORDER BY timestamp DESC
");
$log_exit_stmt->bind_param("s", $user['school_id']);
$log_exit_stmt->execute();
$log_exit_result = $log_exit_stmt->get_result();
$log_exits = $log_exit_result->fetch_all(MYSQLI_ASSOC);
$log_exit_stmt->close();

// Get the total number of logs (entry + exit)
$total_logs = count($log_entries) + count($log_exits);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View User</title>
    <script src="./js/popper.min.js"></script>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="./css/bootstrap.css" >
    <script src="./js/bootstrap.js"></script>
    <script src="./js/jquery-3.7.1.min.js"></script>
    <style>
        .user-details {
            margin-top: 50px;
        }
        .user-image {
            width: 200px;
            height: 200px;
            object-fit: cover;
        }
        .activity-log {
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light p-2" style="background-color: #343a40;">
        <a href="dashboard.php" class="btn btn-info">Back</a>
    </nav>

    <div class="container user-details">
        <h2>User Details</h2>
        <?php if ($user): ?>
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <img src="<?= htmlspecialchars($user['image']) ?>" alt="User Image" class="img-thumbnail user-image">
                        </div>
                        <div class="col-md-9">
                            <h4 class="card-title"><?= htmlspecialchars($user['first_name']) . ' ' . htmlspecialchars($user['middle_name'] ?? '') . ' ' . htmlspecialchars($user['last_name']) ?></h4>
                            <p class="card-text"><strong>School ID:</strong> <?= htmlspecialchars($user['school_id']) ?></p>
                            <p class="card-text"><strong>RFID:</strong> <?= htmlspecialchars($user['rfid_signature']) ?></p>
                            <p class="card-text"><strong>Status:</strong> <?= htmlspecialchars($user['status_name']) ?></p>
                            <p class="card-text"><strong>Department:</strong> <?= htmlspecialchars($user['department_name']) ?></p>
                            <p class="card-text"><strong>User Created At:</strong> <?= htmlspecialchars(date("F j, Y, g:i a", strtotime($user['created_at']))) ?></p>
                            <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-warning">Edit</a>
                            <a href="delete.php?id=<?= $user['school_id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger" role="alert">
                User not found.
            </div>
        <?php endif; ?>
    </div>
    
    <div class="container activity-log">
        <h2>Activity Log</h2>
        <p><strong>Total Logs:</strong> <?= $total_logs ?></p>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Time In</th>
                    <th>Time Out</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($log_entries || $log_exits): ?>
                    <?php
                    // Assuming log_entries and log_exits are ordered by timestamp DESC
                    $max_logs = max(count($log_entries), count($log_exits));
                    for ($i = 0; $i < $max_logs; $i++):
                        $entry_time = $log_entries[$i]['timestamp'] ?? null;
                        $exit_time = $log_exits[$i]['timestamp'] ?? null;
                    ?>
                        <tr>
                            <td><?= $entry_time ? htmlspecialchars(date("F j, Y, g:i a", strtotime($entry_time))) : 'N/A' ?></td>
                            <td><?= $exit_time ? htmlspecialchars(date("F j, Y, g:i a", strtotime($exit_time))) : 'N/A' ?></td>
                        </tr>
                    <?php endfor; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="2" class="text-center">No activity logs found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
</body>
</html>
