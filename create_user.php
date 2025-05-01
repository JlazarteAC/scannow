<?php
session_start(); // Start the session
include "activity_logger.php"; // Include the activity logger
include "connection.php"; // Include database connection

// Check if user is logged in
if (!isset($_SESSION['login_id'])) {
    // Redirect to login page if not logged in
    header("Location: index.php");
    exit();
}

// Fetch user role from the database
$login_id = $_SESSION['login_id'];
$role_check_query = $conn->prepare("SELECT role_id FROM login WHERE id = ?");
$role_check_query->bind_param("i", $login_id);
$role_check_query->execute();
$role_result = $role_check_query->get_result();
$user_role = $role_result->fetch_assoc()['role_id'];
$role_check_query->close();

// Log the page view
logAdminActivity($_SESSION['login_id'], basename($_SERVER['PHP_SELF']), "VIEW");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register User</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="./css/bootstrap.css">
    <script src="./js/bootstrap.js"></script>
    <script src="./js/jquery-3.7.1.min.js"></script>
</head>
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
    .sidebar a{
        color: #fff;
        text-decoration: none;
        display: block;
        padding: 10px 15px;
    }
    
    .sidebar li{
        color: #fff;
        padding: 0px 0px;
        display: block;
        text-decoration: none;
    }

    .sidebar button{
        text-align: left;
        color: #fff;
        height: 40px;
        width: 250px;
        padding: 10px 15px;
        border-radius: 0px;
    }

    .sidebar ul{
        background: #4e575f;
        padding-bottom: 0px;
        margin-bottom: 0px;
    }

    .active {
        color: #16b1c5;
        background: #282c30;
        text-decoration: none;
    }


    .sidebar a:hover,.sidebar button:hover {
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

<body>
<div class="sidebar p-0">
    <div style="height: 130px; background: white; padding: 0px; margin: 0px" class="p-1 border border-secondary">
        <img src="Image/AC.png" class="img-fluid px-4 py-2" alt="Responsive image">
    </div>
    <a href="dashboard.php" class="">Dashboard</a>
    <li>
        <button class="btn btn-toggle align-items-center" data-bs-toggle="collapse" data-bs-target="#home-collapse" aria-expanded="false">
        User Management
        </button>
        <div class="collapse show" id="home-collapse">
          <ul class="btn-toggle-nav list-unstyled fw-normal small">
            <li><a href="create_user.php" class="active">Register User</a></li>
            <?php if ($user_role == 1): ?> <!-- Only SuperAdmin can see this -->
                    <li><a href="admin_logs.php">Admin logs</a></li>
            <?php endif; ?>
          </ul>
        </div>
    </li>

    <a href="log_activity.php">Log Activity</a>
    <div class="bottom">
        <a href="settings.php">Settings</a>
        <a href="logout.php">Logout</a>
    </div>
</div>
    <div class="content p-0">
        <nav class="navbar navbar-expand-lg navbar-light p-2" style="background-color: #343a40;">
            <h3 style="color:white">Register User</h3>
        </nav>
    

        <?php
    include "connection.php";

    // Fetch academic configuration
    $academicConfig = json_decode(file_get_contents("academic_config.json"), true);
    $enrollDate = $academicConfig["academicYearStart"];
    $expirationDate = $academicConfig["academicYearEnd"];

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Fetch input fields
        $school_id = $_POST['school_id'];
        $rfid_signature = $_POST['rfid_signature'];
        $first_name = $_POST['first_name'];
        $middle_name = $_POST['middle_name'];
        $last_name = $_POST['last_name'];
        $status_id = $_POST['status'];
        $enroll_date = $_POST['enroll_date'];
        $expiration_date = $_POST['expiration_date'];
    
        $target_dir = "ProfilePic/";
        $target_file = $target_dir . basename($_FILES["image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $alertType = "";
        $alertMessage = "";
    
        $enroll = 1; // Default value
    
        // Determine if the status is "Student"
        $checkStudentQuery = "SELECT status_name FROM status WHERE status_id = '$status_id'";
        $result = $conn->query($checkStudentQuery);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (stripos($row['status_name'], "student") !== false) {
                $enroll = 0; // Set enroll to 0 if the status contains "Student"
            } else {
                $expiration_date = "9999-12-31"; // Set expiration date for non-students
            }
        }
    
        // Validation logic
        if (empty($school_id) || empty($rfid_signature) || empty($first_name) || empty($last_name) || empty($status_id)) {
            $alertType = "danger";
            $alertMessage = "All fields are required.";
        } elseif (!getimagesize($_FILES["image"]["tmp_name"])) {
            $alertType = "danger";
            $alertMessage = "Uploaded file is not a valid image.";
        } elseif ($_FILES["image"]["size"] > 5000000) {
            $alertType = "danger";
            $alertMessage = "Image size should not exceed 5MB.";
        } elseif (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            $alertType = "danger";
            $alertMessage = "Only JPG, JPEG, PNG, and GIF formats are allowed.";
        } elseif (file_exists($target_file)) {
            $alertType = "danger";
            $alertMessage = "Image already exists. Please rename or use a different file.";
        } else {
            // Check for existing school ID or RFID
            $checkQuery = "SELECT * FROM user WHERE school_id='$school_id' OR rfid_signature='$rfid_signature'";
            $checkResult = $conn->query($checkQuery);
    
            if ($checkResult->num_rows > 0) {
                $alertType = "danger";
                $alertMessage = "School ID or RFID already exists.";
            } else {
                // Attempt to upload and save
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    $sql = "INSERT INTO user (
                        school_id, rfid_signature, first_name, middle_name, last_name, status_id, image, 
                        enroll_date, expiration_date, enroll, granted_access
                    ) VALUES (
                        '$school_id', '$rfid_signature', '$first_name', '$middle_name', '$last_name', '$status_id', 
                        '$target_file', '$enroll_date', '$expiration_date', 1, 1
                    )";
    
                    if ($conn->query($sql) === TRUE) {
                        $alertType = "success";
                        $alertMessage = "User registered successfully.";

                        $description = "Created user: " . $first_name . " " . $last_name . " (School ID: " . $school_id . ")";
                        logAdminActivity($_SESSION['login_id'], basename($_SERVER['PHP_SELF']), "CREATE",$description );
                    } else {
                        $alertType = "danger";
                        $alertMessage = "Database error: " . $conn->error;
                    }
                } else {
                    $alertType = "danger";
                    $alertMessage = "Failed to upload image.";
                }
            }
        }
    
        if (!empty($alertMessage)) {
            echo '<div class="alert alert-' . $alertType . '">' . $alertMessage . '</div>';
        }
    }
    $conn->close();
    ?>


    <div class="container mt-1 w-50">
        <!-- First Container: School ID and RFID Signature -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Enter School ID and RFID</h5>
                <div class="mb-3">
                    <label for="school_id" class="form-label">School ID</label>
                    <input type="text" class="form-control" id="school_id" name="school_id" placeholder="Enter School ID" required autocomplete="off">
                </div>
                <div class="mb-3">
                    <label for="rfid_signature" class="form-label">RFID</label>
                    <input type="text" class="form-control" id="rfid_signature" name="rfid_signature" placeholder="Enter RFID" required autocomplete="off">
                </div>
                <button type="button" class="btn btn-primary" id="nextBtn">Next</button>
            </div>
        </div>

        <!-- Second Container: User Details -->
        <div class="card mt-1" id="userDetailsCard" style="display: none;">
            <div class="card-body">
                <h5 class="card-title">Enter User Details</h5>
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="row ">
                        <div class="col">
                            <div class="form-outline">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-outline">
                                <label for="middle_name" class="form-label">Middle Name (Optional)</label>
                                <input type="text" class="form-control" id="middle_name" name="middle_name">
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-outline">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                        </div>                        

                    </div>
                    
                    <div class="mb-3">
                        <label for="department" class="form-label">Department</label>
                        <select class="form-control" id="department" name="department" required>
                            <option value="" disabled selected>Select Department</option>
                            <!-- Department options will be dynamically populated here -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="" disabled selected>Select Status</option>
                            <!-- Status options will be dynamically populated based on selected department -->
                        </select>

                        <div id="enrollmentSection" class="mt-2"  style="display: none;">
                            <div class="mb-1 p-2">
                                <div class="row bg-opacity-10 border border-secondary rounded">
                                    <label>YYYY-MM-DD</label>
                                    <p class="col m-0 fw-light">School Semester Start: <?php echo $enrollDate; ?></p>
                                    <p class="col m-0 fw-light">School Semester End: <?php echo $expirationDate; ?></p>
                                </div>
                            </div>
                                <!-- Hidden fields for enrollment and expiration dates -->
                            <input type="hidden" name="enroll_date" value="<?php echo $enrollDate; ?>">
                            <input type="hidden" name="expiration_date" value="<?php echo $expirationDate; ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="image" class="form-label">Upload Image</label>
                        <input type="file" class="form-control" id="image" name="image" required>
                    </div>
                    <input type="hidden" id="hiddenSchoolId" name="school_id">
                    <input type="hidden" id="hiddenRfidSignature" name="rfid_signature">
                    <button type="submit" class="btn btn-success">Save</button>
                </form>
            </div>
        </div>
    </div>
    </div>

    <script>
        // Show the second container on "Next" button click and pass School ID and RFID Signature
        document.getElementById('nextBtn').addEventListener('click', function() {
            var schoolId = document.getElementById('school_id').value;
            var rfidSignature = document.getElementById('rfid_signature').value;

            // Validate inputs
            if (schoolId && rfidSignature) {
                document.getElementById('hiddenSchoolId').value = schoolId;
                document.getElementById('hiddenRfidSignature').value = rfidSignature;
                document.getElementById('userDetailsCard').style.display = 'block'; // Show user details form
            } else {
                alert('Please enter both School ID and RFID.');
            }
        });

        // Fetch departments on page load
        window.onload = function() {
            fetchDepartments();
        };

        function fetchDepartments() {
            fetch('get_departments.php')
                .then(response => response.json())
                .then(data => {
                    let departmentSelect = document.getElementById('department');
                    // Reset the options, keeping the placeholder
                    departmentSelect.innerHTML = '<option value="" disabled selected>Select Department</option>';
                    // Add new options dynamically
                    data.forEach(department => {
                        let option = document.createElement('option');
                        option.value = department.department_id;
                        option.textContent = department.department_name;
                        departmentSelect.appendChild(option);
                    });
                })
                .catch(error => console.error('Error fetching departments:', error));
        }


        // Fetch statuses based on selected department
        document.getElementById('department').addEventListener('change', function() {
            let departmentId = this.value;
            fetchStatuses(departmentId);  // Call function to fetch statuses
        });

        function fetchStatuses(departmentId) {
            // Fetch statuses based on department_id
            fetch(`fetch_statuses.php?department_id=${departmentId}`)
                .then(response => response.text())  // Expecting HTML options
                .then(data => {
                    let statusSelect = document.getElementById('status');
                    statusSelect.innerHTML = '<option value="" disabled selected>Select Status</option>';  // Reset status dropdown
                    statusSelect.innerHTML += data;  // Append fetched options to status dropdown
                })
                .catch(error => {
                    console.error('Error fetching statuses:', error);
                });
        }


        // Show enrollment dates if status includes "Student"
        document.getElementById('status').addEventListener('change', function() {
            const statusText = this.options[this.selectedIndex].text.toLowerCase();
            const enrollmentSection = document.getElementById('enrollmentSection');
            if (statusText.includes("student")) {
                enrollmentSection.style.display = 'block';
            } else {
                enrollmentSection.style.display = 'none';
            }
        });
    </script>

</body>
</html>
