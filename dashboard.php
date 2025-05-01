<?php
session_start(); // Start the session
include "activity_logger.php"; // Include the activity logger
include "connection.php";

$login_id = $_SESSION['login_id'];
$role_check_query = $conn->prepare("SELECT role_id FROM login WHERE id = ?");
$role_check_query->bind_param("i", $login_id);
$role_check_query->execute();
$role_result = $role_check_query->get_result();
$user_role = $role_result->fetch_assoc()['role_id'];
$role_check_query->close();

// Check if user is logged in
if (!isset($_SESSION['login_id']) || !isset($_SESSION['username'])) {
    // Redirect to login page if not logged in
    header("Location: index.php");
    exit();
}

// Log the page view
logAdminActivity($_SESSION['login_id'],  basename($_SERVER['PHP_SELF']), "VIEW");
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <script src="./js/popper.min.js"></script>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="./css/bootstrap.css" >
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
    <a href="dashboard.php" class="active">Dashboard</a>
    <li>
        <button class="btn btn-toggle align-items-center" data-bs-toggle="collapse" data-bs-target="#home-collapse" aria-expanded="false">
        User Management
        </button>
        <div class="collapse" id="home-collapse">
          <ul class="btn-toggle-nav list-unstyled fw-normal small">
            <li><a href="create_user.php">Register User</a></li>
            <?php if ($user_role == 1): ?>
                <li><a href="admin_logs.php">Admin logs</a></li> <!-- Only Super Admins can see this -->
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
    <nav class="navbar navbar-expand-lg navbar-light p-2 d-flex justify-content-between" style="background-color: #343a40;">
        <?php
            // Include database connection
            include "connection.php";

            // SQL query to count total users
            $sql_total_users = "SELECT COUNT(*) AS total_users FROM user";
            $result_total_users = $conn->query($sql_total_users);

            // Check if the query was successful and fetch the result
            if ($result_total_users->num_rows > 0) {
                $row_total_users = $result_total_users->fetch_assoc();
                $total_users = $row_total_users['total_users'];
            } else {
                $total_users = 0;
            }
        ?>
            <div class="alert alert-info p-2 m-0">
                Total Users: <?php echo $total_users; ?>
            </div>


            <?php
                // Check if the user is logged in
                if (!isset($_SESSION['login_id']) || !isset($_SESSION['username'])) {
                    header("Location: index.php"); // Redirect to login page if not logged in
                    exit();
                }

                // Retrieve the username from the session
                $username = $_SESSION['username'];
            ?>
            <div class="text-white">
            Hi!, <?php echo ($username); ?>
            </div>
    </nav>

    <div class="container my-2">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Dashboard</h2>
            <a href="create_user.php" class="btn" style="color:white;background-color: #28a745; border-color: #00751B; border-width:2px;">Create User</a>
        </div>

        <div class="border p-2">
    <!-- Unified Search Form -->
    <form method="GET" action="dashboard.php" id="searchForm" class="mb-2">
        <div class="row mb-2">
            <!-- Search Bar -->
            <div class="col-md-12">
                <input class="form-control me-2" type="search" name="search" placeholder="Search by Name or ID" aria-label="Search" autocomplete="off" value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>">
            </div>
        </div>
        <div class="row">
            <!-- Department Dropdown -->
            <div class="col-md-6">
                <label for="department">Department:</label>
                <select id="department" name="department" class="form-select" onchange="loadStatuses()">
                    <option value="">Select Department</option>
                    <?php
                    include "connection.php";
                    $dept_query = "SELECT * FROM department";
                    $dept_result = $conn->query($dept_query);
                    while ($dept_row = $dept_result->fetch_assoc()) {
                        $selected = (isset($_GET['department']) && $_GET['department'] == $dept_row['department_id']) ? 'selected' : '';
                        echo "<option value='" . $dept_row['department_id'] . "' $selected>" . $dept_row['department_name'] . "</option>";
                    }
                    ?>
                </select>
            </div>
            <!-- Status Dropdown -->
            <div class="col-md-6">
                <label for="status">Status:</label>
                <select id="status" name="status" class="form-select">
                    <option value="">Select Status</option>
                    <?php
                    if (isset($_GET['department']) && !empty($_GET['department'])) {
                        $status_query = "SELECT status_id, status_name FROM status WHERE department_id = " . intval($_GET['department']);
                        $status_result = $conn->query($status_query);
                        while ($status_row = $status_result->fetch_assoc()) {
                            $selected = (isset($_GET['status']) && $_GET['status'] == $status_row['status_id']) ? 'selected' : '';
                            echo "<option value='" . $status_row['status_id'] . "' $selected>" . $status_row['status_name'] . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>
        </div>
        <div class="d-flex justify-content-between mt-2">
            <!-- Search and Clear Filters -->
            <div>
                <button class="btn btn-primary" type="submit">Search</button>
                <a href="dashboard.php" class="btn btn-secondary">Clear Filters</a>
            </div>
            <!-- Quick Search Buttons -->
            <div>
                <label>Quick Search:</label>
                <button type="button" class="btn btn-secondary" onclick="quickSearch('Student (ABM),Student (GAS),Student (HUMSS),Student (HE Caregiving),Student (HE Cookery),Student (HE Housekeeping),Student (HE Bread and Pastry Production),Student (HE Food and Beverage Services),Student (HE Tour Guiding and Travel Services),Student (ICT Computer Programming),Student (IA Consumer Electronics Servicing),Student (DP in Information Technology),Student (DP in Hospitality Technology),Student (BS in Computer Engineering),Student (BS in Electronics Technology),Student (BS in Information Technology),Student (BS in Computer Science),Student (BS in Accountancy),Student (BS in Business Administration - Financial Management),Student (BS in Business Administration - Marketing Management),Student (BS in Business Administration - Operations Management),Student (BS in Hospitality Management),Student (BS in Tourism Management)')">Student</button>
                <button type="button" class="btn btn-secondary" onclick="quickSearch('Teachers (SHS),Faculty (CSE),Faculty (BAA),Faculty (HTM),Trainers/Faculty (TVET)')">Teacher/Faculty</button>
            </div>
        </div>
    </form>
</div>


        <!-- Table to Display Filtered Users -->
 
        <table class="table table-striped">
            <thead>
                <tr>
                    <th scope="col">School ID</th>
                    <th scope="col">Name</th>
                    <th scope="col">Department</th>
                    <th scope="col">Status</th>
                    <th scope="col">Image</th>
                    <th scope="col">Time In</th>  
                    <th scope="col">Time Out</th> 
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php
                
                $search_query = "";

                if (isset($_GET['search']) && !empty($_GET['search'])) {
                    $search = $conn->real_escape_string($_GET['search']);
                    $search_query .= " AND (user.school_id LIKE '%$search%' 
                                        OR CONCAT(user.first_name, ' ', user.middle_name, ' ', user.last_name) LIKE '%$search%' 
                                        OR CONCAT(user.first_name, ' ', user.last_name) LIKE '%$search%')";
                }
                $filter_query = "
                                SELECT user.*, 
                                    status.status_name, 
                                    department.department_name,
                                    MAX(CASE WHEN log_entry.status = 'Access Granted' THEN log_entry.timestamp END) AS time_in, 
                                    MAX(CASE WHEN log_exit.status = 'Access Granted' THEN log_exit.timestamp END) AS time_out
                                FROM user 
                                JOIN status ON user.status_id = status.status_id
                                JOIN department ON status.department_id = department.department_id
                                LEFT JOIN log_entry ON user.school_id = log_entry.school_id
                                LEFT JOIN log_exit ON user.school_id = log_exit.school_id
                                WHERE 1=1 $search_query
                                ";
            
            

                // Filter by department
                if (isset($_GET['department']) && !empty($_GET['department'])) {
                    $department = intval($_GET['department']);
                    $filter_query .= " AND department.department_id = $department";
                }

                // Filter by status
                if (isset($_GET['status']) && !empty($_GET['status'])) {
                    $status = intval($_GET['status']);
                    $filter_query .= " AND status.status_id = $status";
                }

                // Quick Search Check
                if (isset($_GET['quick_search']) && !empty($_GET['quick_search'])) {
                    $quick_search = $conn->real_escape_string($_GET['quick_search']);
                    $statuses = explode(',', $quick_search); // Split by comma
                    $status_list = "'" . implode("','", array_map('trim', $statuses)) . "'"; // Prepare for SQL IN clause
                    $filter_query .= " AND status.status_name IN ($status_list)";
                }

                $filter_query .= " GROUP BY user.school_id ORDER BY user.created_at DESC";

                $result = $conn->query($filter_query);
                if (!$result) {
                    die("Invalid Query! " . $conn->error);
                }

                while ($row = $result->fetch_assoc()) {
                    $time_in_formatted = isset($row['time_in']) ? date('F j, Y, g:i a', strtotime($row['time_in'])) : 'N/A'; 
                    $time_out_formatted = isset($row['time_out']) ? date('F j, Y, g:i a', strtotime($row['time_out'])) : 'N/A';  
                    
                    // Display rows
                    echo "
                    <tr>
                        <td class='align-middle'>{$row['school_id']}</td>
                        <td class='align-middle'>{$row['first_name']} {$row['middle_name']} {$row['last_name']}</td>
                        <td class='align-middle'>{$row['department_name']}</td>
                        <td class='align-middle'>{$row['status_name']}</td>
                        <td class='align-middle'><img src='{$row['image']}' alt='Profile Image' style='width:50px; height:50px;'></td>
                        <td class='align-middle'>{$time_in_formatted}</td>
                        <td class='align-middle'>{$time_out_formatted}</td>
                        <td class='align-middle'>
                            <div class='dropdown'>
                                <!-- Dropdown Toggle Button -->
                                <button class='btn' type='button' id='dropdownMenuButton1' data-bs-toggle='dropdown' aria-expanded='false'>
                                    <svg xmlns='css/three-dots-vertical.svg' width='16' height='16' fill='currentColor' class='bi bi-three-dots-vertical' viewBox='0 0 16 16'>
                                        <path d='M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0'/>
                                    </svg>
                                </button>
                                <!-- Dropdown Menu -->
                                <ul class='dropdown-menu' aria-labelledby='dropdownMenuButton1' style='width: 300px;'>";
                    
                    if (strpos($row['status_name'], 'Student') !== false) {
                        echo "
                            <li>
                                <div class='form-check form-switch m-2'>
                                    <label class='form-check-label' for='enrollSwitch'>Enroll End: " . 
                                        (isset($row['expiration_date']) ? date('F j, Y', strtotime($row['expiration_date'])) : 'N/A') . "
                                    </label>
                                    <input class='form-check-input' type='checkbox' role='switch' id='enrollSwitch'
                                        onclick='toggleEnroll({$row['id']})'
                                        " . ($row['enroll'] ? 'checked' : '') . ">
                                </div>
                            </li>";
                    } else {
                        echo "
                            <li>
                                <div class='form-check form-switch m-2'>
                                    <label class='form-check-label' for='rfidSwitch'>RFID Card Activation</label>
                                    <input class='form-check-input' type='checkbox' role='switch' id='rfidSwitch'
                                        onclick='toggleEnroll({$row['id']})'
                                        " . ($row['enroll'] ? 'checked' : '') . ">
                                </div>
                            </li>";
                    }
                    
                    echo "
                                    <li><hr class='dropdown-divider'></li>
                                    <li>
                                        <a class='dropdown-item' href='view_user.php?id={$row['id']}'>
                                            View
                                        </a>
                                    </li>
                                    <li>
                                        <a class='dropdown-item' href='edit_user.php?id={$row['id']}'>
                                            Edit
                                        </a>
                                    </li>
                                    <li>
                                        <a href='#' class='dropdown-item text-danger' onclick=\"confirmDelete('{$row['school_id']}')\">Delete</a>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>";
                    
                }
            ?>
        </tbody>
    </table>
    </div>
</div>
<script>
function confirmDelete(schoolId) {
    console.log("Attempting to delete user with school ID:", schoolId); // Debug log
    if (confirm("Are you sure you want to delete this user? it include Delete User Data and Logs Data")) {
        window.location.href = `delete.php?id=${schoolId}`;
    }
}

</script>
<script>
function loadStatuses() {
    var departmentId = document.getElementById("department").value;
    $.ajax({
        url: 'get_status.php',
        type: 'GET',
        data: { department_id: departmentId },
        success: function(response) {
            $('#status').html(response);
        }
    });
}

function quickSearch(status) {
    // Get the form using a more reliable method (by ID)
    const form = document.getElementById('searchForm'); // Add an ID "searchForm" to your form element
    if (!form) {
        console.error("Form with ID 'searchForm' not found.");
        return;
    }

    // Remove any existing quick search input
    const existingQuickSearchInput = form.querySelector('input[name="quick_search"]');
    if (existingQuickSearchInput) {
        form.removeChild(existingQuickSearchInput);
    }

    // Create and append the new quick search input
    const statusInput = document.createElement('input');
    statusInput.type = 'hidden';
    statusInput.name = 'quick_search';
    statusInput.value = status;
    form.appendChild(statusInput);

    // Submit the form
    form.submit();
}

function toggleEnroll(userId) {
    // Get the checkbox element
    const checkbox = event.target;

    // Determine the enroll status (1 if checked, 0 if unchecked)
    const enrollStatus = checkbox.checked ? 1 : 0;

    // Send an AJAX request to update the database
    $.ajax({
        url: 'toggle_enroll.php', // The PHP script that handles the update
        type: 'POST',
        data: { id: userId, enroll: enrollStatus },
        success: function() {
            // Successfully updated, no alert needed
        },
        error: function(xhr, status, error) {
            console.error('Error:', error); // Log any errors to the console
        }
    });
}


</script>
</body>

</html>
