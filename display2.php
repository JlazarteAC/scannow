<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ScanNow</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="./css/bootstrap.css">
    <script src="./js/bootstrap.js"></script>

    <style>
        body {
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
            width: 1500px;
            max-width: 2500px;
            padding: 30px;
            background-color: white;
            border-radius: 20px;
            border: 3px solid black;
            box-shadow: 0 0 50px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
        }
        .id-card .user-image {
            width: 500px;
            height: 500px;
            object-fit: cover;
            border-radius: 5%;
            margin-right: 30px;
            border: 3px solid black;
        }
        .id-card .user-info {
            flex: 1;
        }
        .id-card .user-info p, h1 {
            margin: 0;
            font-size: 2.5em;
        }

        /* Screensaver styles */
        #screensaver {
            display: flex;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: black;
            z-index: 2; 
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
            width: 150px;
        }

        .align-top {
        text-transform: uppercase; 
         }
    </style>
<?php 
$settings_file = 'display_config.json';  // Ensure this path matches where you saved the file
if (file_exists($settings_file)) {
    $settings = json_decode(file_get_contents($settings_file), true);
} else {
    // Fallback/default settings in case the file is missing
    $settings = [
        'video_file' => 'Display-Video/basket.mp4',
        'loop_video' => true,
        'mute_video' => false,
        'role_text' => 'Welcome to the Asian College!'
    ];
}

// Assign the settings values for use in your page
$videoFile = $settings['video_file'];
$loopVideo = $settings['loop_video'] ? 'loop' : '';
$muteVideo = $settings['mute_video'] ? true : false;  // Boolean for mute setting
$roleText = $settings['role_text'];
?>
<script>
    function checkRefresh() {
        fetch('check_refresh.php')
            .then(response => response.json())
            .then(data => {
                if (data.refresh) {
                    // If refresh is true, reload the page
                    location.reload();
                }
            })
            .catch(error => console.error('Error checking refresh status:', error));
    }

    // Check refresh status every 3 seconds
    setInterval(checkRefresh, 3000);
</script>
<script>
        let idleTime = 0;
        const screensaverDelay = 3; // 10 seconds of inactivity

        function showScreensaver() {
            document.getElementById('screensaver').style.display = 'flex';
        }

        function hideScreensaver() {
            idleTime = 0;
            document.getElementById('screensaver').style.display = 'none';
        }

        function timerIncrement() {
            idleTime++;
            if (idleTime >= screensaverDelay) {
                showScreensaver();
            }
        }


        // Increment the idle timer every second
        setInterval(timerIncrement, 1000);
    </script>
<script>
let lastFetchedLogId = null; // Track the timestamp of the last fetched log
let fetchCount = 0;

// Function to fetch the latest log data
function fetchLatestLog() {
    fetch('fetch_latest_log.php')
        .then(response => response.json())
        .then(data => {
            fetchCount++;

            if (!data.error) {
                // Check if the fetched log has a different timestamp or unique identifier
                if (data.log_id !== lastFetchedLogId) {
                    lastFetchedLogId  = data.log_id; // Update the last fetched timestamp

                    if (data.access_denied) {
                        document.querySelector('.rfid-display .id-card').style.display = 'flex';
                        document.getElementById('guestMessageContainer').style.display = 'none';
                        document.getElementById('accessDeniedAlert').style.display = 'block';
                        document.querySelector('.user-image').src = data.image_path;
                        document.querySelector('.align-top').textContent = data.full_name;
                        document.querySelector('.school-id').textContent = data.school_id;
                        document.querySelector('.department').textContent = data.department_name;
                        document.querySelector('.status').textContent = data.status_name;
                        document.querySelector('.log-time').textContent = data.display_time_in;
                        document.querySelector('.department-logo').src = data.logo_image;
                        document.body.style.backgroundImage = `url(${data.bg_image})`;

                    } else if (data.isGuest) {
                        const guestContainer = document.getElementById('guestMessageContainer');
                        guestContainer.style.display = 'block'; // Show the guest container
                        guestContainer.querySelector('.time-in').textContent = `Time In: ${data.display_time_in}`;
                        document.getElementById('accessDeniedAlert').style.display = 'none';
                        document.querySelector('.rfid-display .id-card').style.display = 'none';
                        document.body.style.backgroundImage = `url(${data.bg_image})`;
                    } else {
                        // Update the id-card details with fetched data
                        document.querySelector('.user-image').src = data.image_path;
                        document.querySelector('.align-top').textContent = data.full_name;
                        document.querySelector('.school-id').textContent = data.school_id;
                        document.querySelector('.department').textContent = data.department_name;
                        document.querySelector('.status').textContent = data.status_name;
                        document.querySelector('.log-time').textContent = data.display_time_in;
                        document.querySelector('.department-logo').src = data.logo_image;
                        document.getElementById('accessDeniedAlert').style.display = 'none';
                        document.body.style.backgroundImage = `url(${data.bg_image})`;

                        // Show or hide the logo based on data.logo_image
                        const logoElement = document.querySelector('.department-logo');
                        if (data.logo_image) {
                            logoElement.src = data.logo_image;
                            logoElement.style.display = 'block'; // Show the logo
                        } else {
                            logoElement.style.display = 'none'; // Hide the logo
                        }

                        // Show the regular id-card content
                        document.querySelector('.rfid-display .id-card').style.display = 'flex';
                        // Hide the guest message
                        document.getElementById('guestMessageContainer').style.display = 'none';
                    }

                    // Hide the screensaver once new data is fetched
                    hideScreensaver();
                } else {
                    // If data is the same as last fetched, do nothing
                    console.log('No new data available.');
                }
            } else {
                console.error(data.error);
            }

        })
        .catch(error => console.error('Error fetching log:', error));
}


// Automatically refresh every 5 seconds
setInterval(fetchLatestLog, 1000); // Adjust interval as needed (5000ms = 5 seconds)

</script>
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



<div id="screensaver">
    <video autoplay <?php echo $loopVideo; ?> <?php echo $muteVideo ? 'muted' : ''; ?> controls>
        <source src="<?php echo $videoFile; ?>" type="video/mp4">
        Your browser does not support the video tag.
    </video>
</div>


<div class="container rfid-display" onload="fetchLatestLog()"> 
    <div class="id-card">
        <div>
            <img src="" alt="User Image" class="user-image">
        </div>
        <div style="width: 800px;">
            <div style="padding-bottom: 20px;">
                <h1 style="font-size: 3.5em;" class="align-top"></h1>
            </div>
            <div class="user-info">
                <div class="row">
                    <h1 class="col-4 text-end">School ID:</h1>
                    <p class="col-8 school-id"></p>
                </div>
                <div class="row">
                    <h1 class="col-4 text-end">Department:</h1>
                    <p class="col-8 department"></p>
                </div>
                <div class="row">
                    <h1 class="col-4 text-end">Status:</h1>
                    <p class="col-8 status"></p>
                </div>
                <div class="row">
                    <h1 class="col-4 text-end">Time In:</h1>
                    <p class="col-8 log-time"></p>
                </div>
                <div class="row d-flex flex-row-reverse">
                    <img src="<?= htmlspecialchars($department_logo); ?>" alt="Logo Department" class="department-logo">
                </div>
            </div>
        </div>
    </div>

    <div id="guestMessageContainer" style="display: none; z-index: 1; position: absolute;" class="id-card">
        <div class="mt-2" style="height: 500px;">
            <h1 style="font-size: 80px;">
                <center>WELCOME <br>TO<br><b>Asian College!</b></center>
            </h1>
            <div class="row mt-5 fs-1">
                <p class="text-center time-in">Time In: N/A</p>
            </div>
        </div>
    </div>
    <div id="accessDeniedAlert" class="alert m-0" style="display: none; z-index: 1; position: absolute; height: 360px; width: 1100px; background-color: white; border: 5px solid #F67656; border-radius: 12px;">
    <div class="row" style="height: 300px;">
        <div class="col-6 align-self-center">
            <img src="Image/wrongaccess.png" style="height: 150px; width: auto; position: relative; left: 160px;">
        </div>
        <div class="col-6 align-self-center">
            <h1 class="display-3"><strong><u style="text-decoration-color: #F67656;">Access Denied</u></strong></h1>
        </div>
    </div>
</div>




<footer class="fixed-bottom bg-body-tertiary text-center text-lg-start z-3">
  <div class="text-center p-2">
  <h1><marquee><?php echo htmlspecialchars($roleText); ?></marquee></h1>
  </div>
</footer>

</body>
</html>

