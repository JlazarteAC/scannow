<?php
include 'connection.php';

date_default_timezone_set('Asia/Manila');
$rfid_signature = $_GET['rfid_signature'] ?? '';
$first_name = $middle_name = $last_name = $name = $image = $status = $department = $school_id = $time_out = '';

if (!empty($rfid_signature)) {
    // Prepare the SQL statement to fetch user details from user
    $stmt = $conn->prepare("
            SELECT user.first_name, user.middle_name, user.last_name, user.image, user.school_id, 
                status.status_name, department.department_name,
                display_color.bg_image, display_color.logo_image
            FROM user
            LEFT JOIN status ON user.status_id = status.status_id
            LEFT JOIN department ON status.department_id = department.department_id
            LEFT JOIN display_color ON department.department_id = display_color.department_id
            WHERE user.rfid_signature = ?
            LIMIT 1
    ");
    $stmt->bind_param("s", $rfid_signature);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Fetch the user details
        $row = $result->fetch_assoc();
        $first_name = $row['first_name'];
        $middle_name = $row['middle_name'] ? $row['middle_name'] : '';
        $last_name = $row['last_name'];
        $name = strtoupper(trim("$first_name $middle_name $last_name"));  // Combine first, middle, and last name
        $image = $row['image'] ? $row['image'] : "Image/default.png";
        $status = $row['status_name'];
        $department = $row['department_name'];
        $school_id = $row['school_id'];
        $bg_image = $row['bg_image'] ? $row['bg_image'] : "Image/banner.jpg"; // Set the department background image
        $department_logo = $row['logo_image'] ?? ''; // Set the department logo

        // Record the current timestamp as "Time Out"
        $time_out = date('Y-m-d H:i:s');

        // Insert the time_out into log_exit table
        $insert_stmt = $conn->prepare("
            INSERT INTO log_exit (school_id, timestamp)
            VALUES (?, ?)
        ");
        $insert_stmt->bind_param("ss", $school_id, $time_out);
        $insert_stmt->execute();
        $insert_stmt->close();

        // Format the time to be displayed in a human-readable format (e.g., 12-hour format with AM/PM)
        $display_time_out = date('h:i A', strtotime($time_out));
    } else {
        // Use default values if user is not found
        $name = "UNKNOWN USER";
        $image = "Image/default.png"; 
        $status = "UNKNOWN";
        $department = "UNKNOWN";
        $school_id = "UNKNOWN";
        $time_out = "N/A";
        $display_time_out = "N/A";
        $bg_image = "Image/banner.jpg"; 
        $department_logo = "Image/default-logo.png"; 
    }

    $stmt->close();
} else {
    $image = "Image/default.png";
    $display_time_out = "";
    $bg_image = "Image/banner.jpg";
}
// Read settings from the JSON config file or directly from settings.php
$settings_file = 'display_config.json';  // Ensure this path matches where you saved the file
if (file_exists($settings_file)) {
    $settings = json_decode(file_get_contents($settings_file), true);
} else {
    // Fallback/default settings in case the file is missing
    $settings = [
        'video_file' => 'media/default-video.mp4',
        'loop_video' => true,
        'mute_video' => true,
        'volume' => 50,
        'role_text' => 'Welcome to the system!'
    ];
}

// Assign the settings values for use in your page
$videoFile = $settings['video_file'];
$loopVideo = $settings['loop_video'] ? 'loop' : '';
$muteVideo = $settings['mute_video'] ? 'muted' : '';
$volume = $settings['volume'] / 100;  // Convert volume percentage
$roleText = $settings['role_text'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ScanNow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="./css/bootstrap.css">
    <script src="./js/bootstrap.js"></script>

    <style>
        body {
            background-image: url("<?php echo htmlspecialchars($bg_image); ?>");
            background-size: cover;
            background-position: center;
            height: 100%;
            background-repeat: no-repeat;

        }
        .rfid-display {
            height: 80vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .id-card {
            width: 1200px;
            max-width: 1200px;
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            border: 3px solid gray;
            box-shadow: 0 0 50px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
        }
        .id-card .user-image {
            width: 450px;
            height: 450px;
            object-fit: cover;
            border-radius: 1%;
            margin-right: 30px;
            border: 3px solid gray;
            border-radius: 10px;
        }
        .id-card .user-info {
            flex: 1;
        }
        .id-card .user-info p, h1 {
            margin: 0;
            font-size: 2em;
        }
         .user-info .status {
            color: #6c757d;
        }
        /* Screensaver styles */
        #screensaver {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: black;
            z-index: 1; 
        }
        #screensaver video { 
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        img.error {
            display: none; 
        }

        img.department-logo {
            position: absolute;
            width: 150px;
            margin-left: 1px;
            bottom: 10vw;
            right: 15vw;

        }
    </style>
</head>
<body>
<nav class="z-3 navbar navbar-expand-lg navbar-light p-2" style="background-color: white;">
    <div class="container-fluid">
        <div style="width: 150px;">
            <img src="Image/AC.png" class="img-fluid" alt="Responsive image">
        </div>
        <div class="d-flex flex-column font-weight-bold">
            <div>
                <span id="currentDate" class="text-black fs-3"></span>
            </div>
            <div class="text-center ">
                <span id="currentTime" class="text-black fw-bold fs-4"></span>
            </div>
        </div>
    </div>
</nav>

<script>
    function updateDateTime() {
        const now = new Date();
        
        // Format options for date (Month, Day, Year)
        const dateOptions = { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        };
        
        // Format options for time (12-hour format)
        const timeOptions = { 
            hour: '2-digit', 
            minute: '2-digit', 
            second: '2-digit',
            hour12: true 
        };
        
        // Display the formatted date and time
        document.getElementById('currentDate').textContent = now.toLocaleDateString('en-US', dateOptions);
        document.getElementById('currentTime').textContent = now.toLocaleTimeString('en-US', timeOptions);
    }

    // Update date and time every second
    setInterval(updateDateTime, 1000);
    updateDateTime(); // Initialize immediately
</script>


 <!-- User Info Display -->
<div class="container rfid-display">    
    <div class="id-card" >
        <img src="<?php echo htmlspecialchars($image); ?>" alt="User Image" class="user-image">
        <img src="<?php echo htmlspecialchars($department_logo); ?>" alt="Logo Department" class="department-logo" onerror="this.classList.add('error');">
        <div style="width: 600px;">
            <div style="padding-bottom: 60px;">
                <h1 style="font-size: 3.2em; "><?php echo htmlspecialchars($name); ?></h1>
            </div>   
            <div class="user-info">
                <div class="row ">
                    <h1 class="col-4 text-end">School ID:</h1>
                    <p class="col-8"> <?php echo htmlspecialchars($school_id); ?></p>
                </div>
                <div class="row">
                    <h1 class="col-4 text-end ">Department:</h1>
                    <p class="col-8"> <?php echo htmlspecialchars($department); ?></p>
                </div>
                    
                <div class="row ">
                    <h1 class="col-4 text-end ">Status:</h1>
                    <p class="col-8"><?php echo htmlspecialchars($status); ?></p>
                </div>
                <div class="row">
                    <h1 class="col-4 text-end ">Time Out:</h1>
                    <p class="col-8"><?php echo htmlspecialchars($display_time_out); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="fixed-bottom bg-body-tertiary text-center text-lg-start">
  <div class="text-center p-4">
  <h1><marquee><?php echo htmlspecialchars($roleText); ?></marquee></h1>
  </div>
</footer>

<!-- Screensaver Overlay -->
<div id="screensaver">
    <video autoplay <?php echo $muteVideo; ?> <?php echo $loopVideo; ?> style="volume: <?php echo $volume; ?>;">
        <source src="<?php echo $videoFile; ?>" type="video/mp4">
        Your browser does not support the video tag.
    </video>
</div>

<!-- Hidden input field to capture RFID data -->
<input type="text" id="rfid_signature" style="position: absolute; top: -1000px;">

<script>
let idleTime = 0;
const screensaverDelay = 0.1667; // 10 seconds of inactivity

function showScreensaver() {
    document.getElementById('screensaver').style.display = 'flex';
}

function hideScreensaver() {
    idleTime = 0;
    document.getElementById('screensaver').style.display = 'none';
}

function timerIncrement() {
    idleTime += 0.1667;
    if (idleTime >= screensaverDelay) {
        showScreensaver();
    }
}

function hasRFIDSignature() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.has('rfid_signature');
}

if (hasRFIDSignature()) {
    hideScreensaver();
    setTimeout(() => {
        setInterval(timerIncrement, 10000);
    }, 10000);
} else {
    showScreensaver();
    setInterval(timerIncrement, 10000);
}

document.onmousemove = hideScreensaver;
document.onkeypress = hideScreensaver;
document.ontouchstart = hideScreensaver;

const rfidInput = document.getElementById('rfid_signature');
rfidInput.focus();

rfidInput.addEventListener('input', () => {
    if (rfidInput.value.length === 10) {
        hideScreensaver();
        window.location.href = `display_exit.php?rfid_signature=${rfidInput.value}`;
    }
});
</script>

</body>
</html>
