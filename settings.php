<?php
session_start();
include "activity_logger.php";
include "connection.php";

// Check if user is logged in
if (!isset($_SESSION['login_id'])) {
    header("Location: index.php");
    exit();
}

// Fetch the user's role from the database
$login_id = $_SESSION['login_id'];
$userRole = null; // Default to null

$roleQuery = $conn->prepare("
    SELECT role.role_name 
    FROM login 
    JOIN role ON login.role_id = role.role_id 
    WHERE login.id = ?
");
$roleQuery->bind_param("i", $login_id);
$roleQuery->execute();
$roleResult = $roleQuery->get_result();
if ($roleResult && $roleResult->num_rows > 0) {
    $userRole = $roleResult->fetch_assoc()['role_name'];
}
$roleQuery->close();

// Ensure $userRole is defined to prevent undefined variable warnings
if ($userRole === null) {
    header("Location: login.php?error=role_not_found");
    exit();
}

// Log the page view
logAdminActivity($_SESSION['login_id'], basename($_SERVER['PHP_SELF']), "VIEW");

// Load display settings from JSON file
$displayConfig = json_decode(file_get_contents('display_config.json'), true);
$videoFile = $displayConfig['video_file'] ?? '';
$loopVideo = $displayConfig['loop_video'] ?? false;
$muteVideo = $displayConfig['mute_video'] ?? false;
$volume = $displayConfig['volume'] ?? 50;
$roleText = $displayConfig['role_text'] ?? '';

// Load academic configuration from JSON file
$academicConfig = json_decode(file_get_contents('academic_config.json'), true);
$academicYearStart = $academicConfig['academicYearStart'] ?? '';
$academicYearEnd = $academicConfig['academicYearEnd'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['saveAcademicSettings'])) {
        // Save semester dates for creating new users
        $academicYearStart = $_POST['academicYearStart'];
        $academicYearEnd = $_POST['academicYearEnd'];

        if ($academicYearStart <= $academicYearEnd) {
            // Save semester config to JSON file
            $academicConfig = [
                'academicYearStart' => $academicYearStart,
                'academicYearEnd' => $academicYearEnd,
            ];
            file_put_contents('academic_config.json', json_encode($academicConfig));

            echo '<script>alert("Semester configuration saved successfully!");</script>';

            // Log the save action
            $description = "Saved new semester configuration: Start - $academicYearStart, End - $academicYearEnd";
            logAdminActivity($_SESSION['login_id'], "settings.php", "SAVE", $description);
        } else {
            echo '<script>alert("Error: Start date must be before or equal to end date.");</script>';
        }
    }

    if (isset($_POST['updateSemesterDates'])) {
        // Update semester dates for existing students
        $academicYearStart = $_POST['academicYearStart'];
        $academicYearEnd = $_POST['academicYearEnd'];

        if ($academicYearStart <= $academicYearEnd) {
            // Update semester dates in the database
            $updateQuery = "
                UPDATE user 
                SET enroll_date = ?, expiration_date = ? 
                WHERE status_id IN (
                    SELECT status_id 
                    FROM status 
                    WHERE status_name LIKE 'Student%'
                )
            ";
            $stmt = $conn->prepare($updateQuery);
            if ($stmt) {
                $stmt->bind_param('ss', $academicYearStart, $academicYearEnd);
                if ($stmt->execute()) {
                    echo '<script>alert("Semester dates updated successfully for students!");</script>';

                    // Log the update action
                    $description = "Updated semester dates for students: Start - $academicYearStart, End - $academicYearEnd";
                    logAdminActivity($_SESSION['login_id'], "settings.php", "UPDATE", $description);
                }
                $stmt->close();
            } else {
                echo '<script>alert("Error preparing the update query.");</script>';
            }
        } else {
            echo '<script>alert("Error: Start date must be before or equal to end date.");</script>';
        }
    }


    if (isset($_POST['saveDisplaySettings'])) {
        $newSettings = $displayConfig;
    
        // Handle file upload
        if (isset($_FILES['videoFile']) && $_FILES['videoFile']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'Display-Video/';
            $videoFile = $uploadDir . basename($_FILES['videoFile']['name']);
            move_uploaded_file($_FILES['videoFile']['tmp_name'], $videoFile);
            $newSettings['video_file'] = $videoFile;
        }
    
        // Update settings
        $newSettings['loop_video'] = isset($_POST['loopVideo']);
        $newSettings['mute_video'] = isset($_POST['muteVideo']);
        $newSettings['role_text'] = $_POST['roleText'] ?? $displayConfig['role_text'];
    
        $newSettings['refresh'] = true;
    
        // Save updated settings to JSON file
        file_put_contents('display_config.json', json_encode($newSettings));
        echo '<script>alert("Display settings saved successfully!");</script>';
    
        // Log the update
        $description = "Updated Display Settings: Mute - " . ($newSettings['mute_video'] ? "Yes" : "No") . ", Loop Video - " . ($newSettings['loop_video'] ? "Enabled" : "Disabled");
        logAdminActivity($_SESSION['login_id'], "settings.php", "UPDATE", $description);
    
        header('Location: settings.php?status=success');
        exit;
    }

    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['generateBackupCSV'])) {
            // Generate backup as CSV
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment;filename="backup_' . date('Ymd_His') . '.csv"');
    
            $output = fopen('php://output', 'w');
    
            // Add headers for Users section
            fputcsv($output, ['Users Data']);
            fputcsv($output, ['ID', 'First Name', 'Last Name', 'School ID', 'Enroll Date', 'Expiration Date']);
    
            // Fetch Users data
            $userQuery = "SELECT id, first_name, last_name, school_id, enroll_date, expiration_date FROM user";
            $userResult = $conn->query($userQuery);
    
            if ($userResult && $userResult->num_rows > 0) {
                while ($row = $userResult->fetch_assoc()) {
                    fputcsv($output, $row);
                }
            } else {
                fputcsv($output, ['No user data available']);
            }
    
            // Add a blank line to separate sections
            fputcsv($output, []);
    
            // Add headers for Logs section
            fputcsv($output, ['Time Logs']);
            fputcsv($output, ['Log Type', 'ID', 'Name', 'School ID', 'Timestamp', 'Status']);
    
            // Fetch Logs data with User Names
            $logQuery = "
                SELECT 
                    'Entry' AS log_type,
                    le.id AS log_id,
                    CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) AS name,
                    le.school_id,
                    le.timestamp,
                    le.status
                FROM log_entry le
                LEFT JOIN user u ON le.school_id = u.school_id
    
                UNION ALL
    
                SELECT 
                    'Exit' AS log_type,
                    lx.id AS log_id,
                    CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) AS name,
                    lx.school_id,
                    lx.timestamp,
                    lx.status
                FROM log_exit lx
                LEFT JOIN user u ON lx.school_id = u.school_id
    
                ORDER BY timestamp;
            ";
            $logResult = $conn->query($logQuery);
    
            if ($logResult && $logResult->num_rows > 0) {
                while ($row = $logResult->fetch_assoc()) {
                    fputcsv($output, $row);
                }
            } else {
                fputcsv($output, ['No log data available']);
            }
    
            fclose($output);
    
            // Log the backup generation action
            logAdminActivity($_SESSION['login_id'], "settings.php", "GENERATE_BACKUP", "Generated CSV backup");
            exit;
        }
    
        if (isset($_POST['clearTimeLogs'])) {
            // Clear log_entry and log_exit tables
            $clearLogEntryQuery = "DELETE FROM log_entry";
            $clearLogExitQuery = "DELETE FROM log_exit";
    
            $conn->begin_transaction(); // Start transaction
            try {
                $conn->query($clearLogEntryQuery);
                $conn->query($clearLogExitQuery);
                $conn->commit(); // Commit the transaction
    
                echo '<script>alert("Time logs cleared successfully!");</script>';
    
                // Log the clearing logs action
                logAdminActivity($_SESSION['login_id'], "settings.php", "CLEAR_LOGS", "Cleared all time logs");
            } catch (Exception $e) {
                $conn->rollback(); // Rollback on error
                echo '<script>alert("Error clearing time logs: ' . $e->getMessage() . '");</script>';
            }
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <script src="./js/popper.min.js"></script>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="./css/bootstrap.css" >
    <script src="./js/bootstrap.js"></script>
    <script src="./js/jquery-3.7.1.min.js"></script>
    <style>
        body {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }
        .sidebar {
            width: 250px;
            height: 100%;
            background: #343a40;
            padding: 15px;
            position: fixed;
        }
        .sidebar a {
            color: #fff;
            text-decoration: none;
            display: block;
            padding: 10px 15px;
        }
        .sidebar li {
            color: #fff;
            padding: 0;
            display: block;
            text-decoration: none;
        }
        .sidebar button {
            text-align: left;
            color: #fff;
            height: 40px;
            width: 250px;
            padding: 10px 15px;
            border-radius: 0;
        }
        .sidebar ul {
            background: #4e575f;
            padding-bottom: 0;
            margin-bottom: 0;
        }
        .active {
            color: #16b1c5;
            background: #282c30;
            text-decoration: none;
        }
        .sidebar a:hover, .sidebar button:hover {
            color: #16b1c5;
        }
        .sidebar .bottom {
            position: absolute;
            bottom: 15px;
            width: 100%;
        }
        .content {
            margin-left: 250px;
            padding: 20px;
            width: 100%;
            overflow-y: auto;
        }
    </style>
</head>
<body>
<div class="sidebar p-0">
    <div style="height: 130px; background: white; padding: 0px;" class="p-1 border border-secondary">
        <img src="Image/AC.png" class="img-fluid px-4 py-2" alt="Responsive image">
    </div>
    <a href="dashboard.php">Dashboard</a>
    <li>
        <button class="btn btn-toggle align-items-center" data-bs-toggle="collapse" data-bs-target="#home-collapse" aria-expanded="false">
        User Management
        </button>
        <div class="collapse" id="home-collapse">
          <ul class="btn-toggle-nav list-unstyled fw-normal small">
            <li><a href="create_user.php">Register User</a></li>
            <?php if ($userRole === 'SuperAdmin'): ?>
                <li><a href="admin_logs.php">Admin Logs</a></li>
            <?php endif; ?>
          </ul>
        </div>
    </li>

    <a href="log_activity.php">Log Activity</a>
    <div class="bottom">
        <a href="settings.php" class="active">Settings</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="content p-0">
    <nav class="navbar navbar-expand-lg navbar-light px-2 m-0" style="background-color: #343a40;">
        <h3 style="color:white">Settings</h3>
    </nav>
    
    <div class="container-md border m-3 pb-3">
    <h2 class="p-2">Display Settings</h2>
    <form method="POST" enctype="multipart/form-data" action="settings.php">

        <!-- Video File Input -->
        <div class="mb-3">
            <label for="videoFile" class="form-label">Select Video File</label>
            <input class="form-control" type="file" id="videoFile" name="videoFile" accept="video/*">
        </div>

        <!-- Loop Checkbox -->
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="loopVideo" name="loopVideo" <?php if ($loopVideo) echo 'checked'; ?>>
            <label class="form-check-label" for="loopVideo">Repeat</label>
        </div>

        <!-- Mute Checkbox -->
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="muteVideo" name="muteVideo" <?php if ($muteVideo) echo 'checked'; ?>>
            <label class="form-check-label" for="muteVideo">Mute Audio</label>
        </div>

        <!-- Role Text Input -->
        <div class="mb-3">
            <label for="roleText" class="form-label">Announcement Section</label>
            <input type="text" class="form-control" id="roleText" name="roleText" placeholder="Enter text" value="<?php echo htmlspecialchars($roleText); ?>">
        </div>

        <div>
            <button type="submit" name="saveDisplaySettings" class="btn btn-primary">Save</button>
        </div>
    </form>
</div>

<script>
    // Check the URL for a "status=success" parameter
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('status') === 'success') {
        alert('Display settings saved successfully!');
    }
</script>

    <div class="container-md border m-3 pb-3">
        <h2 class="p-2">Semester Config</h2>
        <form method="POST" action="settings.php">
            <!-- Academic Year Start and End -->
            <label for="academicYearStart" class="form-label">Set the School Semester Date</label>
            <div class="row">
                <div class="form-group col-md-4">
                    <label for="academicYearStart">Start</label>       
                    <input type="date" class="form-control" id="academicYearStart" name="academicYearStart" 
                        value="<?php echo htmlspecialchars($academicYearStart); ?>" required>
                </div>
                <div class="form-group col-md-4">
                    <label for="academicYearEnd">End</label>       
                    <input type="date" class="form-control" id="academicYearEnd" name="academicYearEnd" 
                        value="<?php echo htmlspecialchars($academicYearEnd); ?>" required>
                </div>
                <div class="row">
                <div class="form-group col-md-4">
                    <button type="submit" name="saveAcademicSettings" class="btn btn-primary mt-4">Save</button>
                    <p>It save the Config for create a new User</p>
                </div>
                <div class="form-group col-md-4">
                    <button type="submit" name="updateSemesterDates" class="btn btn-secondary mt-4">Update</button>
                    <p>It Update The Student Semester</p>
                </div>
                </div>
            </div>
        </form>
    </div>




    <div class="container-md border m-3 pb-3">
        <h2 class="p-2">Backups</h2>
        <form method="POST" action="settings.php">
            <p>Generate User and Time Log Backup as CSV:</p>
            <button type="submit" name="generateBackupCSV" class="btn btn-primary">Generate CSV Backup</button>
        </form>
        <form method="POST" action="settings.php" onsubmit="return confirm('Are you sure you want to clear all time logs?');">
        <p>Clear All Time Logs:</p>
        <button type="submit" name="clearTimeLogs" class="btn btn-danger">Clear Time Logs</button>
    </form>
    </div> 
</div>



</body>
<script>
        // Format date to "Month Day, Year"
        function formatDateForDisplay(dateString) {
            const dateObj = new Date(dateString);
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            return dateObj.toLocaleDateString('en-US', options);
        }

        // Update date fields to display in the desired format
        document.addEventListener('DOMContentLoaded', function() {
            const academicYearStart = document.getElementById('academicYearStart');
            const academicYearEnd = document.getElementById('academicYearEnd');
            const semesterStart = document.getElementById('semesterStart');
            const semesterEnd = document.getElementById('semesterEnd');

            // Set placeholder text to formatted date for each field
            if (academicYearStart.value) academicYearStart.setAttribute('data-display', formatDateForDisplay(academicYearStart.value));
            if (academicYearEnd.value) academicYearEnd.setAttribute('data-display', formatDateForDisplay(academicYearEnd.value));
            if (semesterStart.value) semesterStart.setAttribute('data-display', formatDateForDisplay(semesterStart.value));
            if (semesterEnd.value) semesterEnd.setAttribute('data-display', formatDateForDisplay(semesterEnd.value));
        });
    </script>
</html>
