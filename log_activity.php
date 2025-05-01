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

// Fetch departments for the dropdown
$departmentQuery = "SELECT department_id, department_name FROM department";
$departmentResult = $conn->query($departmentQuery);

// Fetch statuses for the dropdown
$statusQuery = "SELECT status_id, status_name FROM status";
$statusResult = $conn->query($statusQuery);

// Combine Entry and Exit Logs into a single query
$combinedLogsQuery = "
    SELECT log_entry.school_id, 
           CONCAT(user.first_name, ' ', COALESCE(user.middle_name, ''), ' ', user.last_name) AS full_name, 
           log_entry.timestamp AS timestamp, 
           'Entry Gate' AS action,
           department.department_name AS department,
           department.department_id AS department_id,  
           status.status_name AS status,
           status.status_id AS status_id
    FROM log_entry
    JOIN user ON log_entry.school_id = user.school_id
    JOIN status ON user.status_id = status.status_id
    JOIN department ON status.department_id = department.department_id
    WHERE log_entry.status != 'Access Denied'
    UNION ALL
    SELECT log_exit.school_id, 
           CONCAT(user.first_name, ' ', COALESCE(user.middle_name, ''), ' ', user.last_name) AS full_name, 
           log_exit.timestamp AS timestamp, 
           'Exit Gate' AS action,
           department.department_name AS department,
           department.department_id AS department_id, 
           status.status_name AS status,
           status.status_id AS status_id
    FROM log_exit
    JOIN user ON log_exit.school_id = user.school_id
    JOIN status ON user.status_id = status.status_id
    JOIN department ON status.department_id = department.department_id
    WHERE log_exit.status = 'Access Granted'
    ORDER BY timestamp DESC";

$combinedLogsResult = $conn->query($combinedLogsQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Activity</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="./css/bootstrap.css">
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
<script>
$(document).ready(function() {
    // Existing filter functionality
    $('#searchInput, #departmentFilter, #statusFilter, #dateFilter, #actionFilter').on('keyup change', function() {
        var searchValue = $('#searchInput').val().toLowerCase();
        var selectedDepartment = $('#departmentFilter').val();
        var selectedStatus = $('#statusFilter').val();
        var dateRange = $('#dateFilter').val();
        var selectedAction = $('#actionFilter').val();
        filterLogs(searchValue, selectedDepartment, selectedStatus, dateRange, selectedAction);
    });

    // Clear filters functionality
    $('#clearFiltersButton').on('click', function() {
        $('#searchInput').val('');
        $('#departmentFilter').val('');
        $('#statusFilter').val('');
        $('#dateFilter').val('');
        $('#actionFilter').val('');
        filterLogs('', '', '', '', '');
    });

    function filterLogs(searchValue, selectedDepartment, selectedStatus, dateRange, selectedAction) {
        var currentDate = new Date();

        $('#logsTable tbody tr').each(function() {
            var idMatch = $(this).find("td:eq(0)").text().toLowerCase().indexOf(searchValue) > -1;
            var nameMatch = $(this).find("td:eq(1)").text().toLowerCase().indexOf(searchValue) > -1;
            var departmentMatch = selectedDepartment === "" || $(this).data('department-id') == selectedDepartment;
            var statusMatch = selectedStatus === "" || $(this).data('status-id') == selectedStatus;
            var actionMatch = selectedAction === "" || $(this).find("td:eq(5)").text() === selectedAction;
            var timestampText = $(this).find("td:eq(4)").text();
            var timestampDate = new Date(timestampText);

            var dateMatch = false;
            if (dateRange === 'Today') {
                dateMatch = timestampDate.toDateString() === currentDate.toDateString();
            } else if (dateRange === 'Yesterday') {
                var yesterday = new Date();
                yesterday.setDate(currentDate.getDate() - 1);
                dateMatch = timestampDate.toDateString() === yesterday.toDateString();
            } else if (dateRange === 'Week') {
                var oneWeekAgo = new Date();
                oneWeekAgo.setDate(currentDate.getDate() - 6);
                dateMatch = timestampDate >= oneWeekAgo && timestampDate <= currentDate;
            } else if (dateRange === 'Month') {
                var startOfMonth = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
                dateMatch = timestampDate >= startOfMonth && timestampDate <= currentDate;
            } else {
                dateMatch = true;
            }

            $(this).toggle((idMatch || nameMatch) && departmentMatch && statusMatch && dateMatch && actionMatch);
        });
    }
});
    </script>

</head>
<body>
<div class="sidebar p-0">
    <div style="height: 130px; background: white; padding: 0px; margin: 0px" class="p-1 border border-secondary">
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
            <?php if ($user_role == 1): ?> <!-- Only SuperAdmin can see this -->
                    <li><a href="admin_logs.php">Admin logs</a></li>
                <?php endif; ?>
          </ul>
        </div>
    </li>

    <a href="log_activity.php" class="active">Log Activity</a>
    <div class="bottom">
        <a href="settings.php">Settings</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="content p-0">
    <nav class="navbar navbar-expand-lg navbar-light p-2" style="background-color: #343a40;">
        <?php
    // Today's date
    $today = date('Y-m-d');

    // Query to count today's entrances with "Access Granted"
    $sql_today_entrances = "
        SELECT COUNT(*) AS today_entrances 
        FROM log_entry 
        WHERE DATE(timestamp) = '$today' AND status = 'Access Granted'
    ";
    $result_today_entrances = $conn->query($sql_today_entrances);
    $today_entrances = ($result_today_entrances && $result_today_entrances->num_rows > 0) 
        ? $result_today_entrances->fetch_assoc()['today_entrances'] 
        : 0;

    // Query to count today's exits with "Access Granted"
    $sql_today_exits = "
        SELECT COUNT(*) AS today_exits 
        FROM log_exit 
        WHERE DATE(timestamp) = '$today' AND status = 'Access Granted'
    ";
    $result_today_exits = $conn->query($sql_today_exits);
    $today_exits = ($result_today_exits && $result_today_exits->num_rows > 0) 
        ? $result_today_exits->fetch_assoc()['today_exits'] 
        : 0;
    ?>
        
        <div class="alert alert-info p-2 m-0 ">
            Today Entrance: <?php echo $today_entrances; ?><br>
        </div>
        <div class="alert alert-info p-2 m-0 mx-1">
            Today Exit: <?php echo $today_exits; ?>
        </div>
        
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center">
            <h2>Log Activity</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exportModal">
                Export Logs
            </button>

            <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exportModalLabel">Export Logs</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="exportForm" method="POST" action="export_logs.php">
                                <!-- Date Range Selection -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="dateFrom" class="form-label">Date From</label>
                                        <input type="date" class="form-control" id="dateFrom" name="date_from" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="dateTo" class="form-label">Date To</label>
                                        <input type="date" class="form-control" id="dateTo" name="date_to" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="nameSearchInput" class="form-label">Name</label>
                                    <input type="text" id="nameSearchInput" name="full_name" class="form-control" placeholder="Enter Name">
                                </div>
                                <!-- Department and Status Filters -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="departmentExportFilter" class="form-label">Department</label>
                                        <select id="departmentExportFilter" name="department_id" class="form-control form-select">
                                            <option value="">Select Departments</option>
                                            <?php foreach ($departmentResult as $departmentRow): ?>
                                                <option value="<?= $departmentRow['department_id'] ?>">
                                                    <?= $departmentRow['department_name'] ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="statusExportFilter" class="form-label">Status</label>
                                        <select id="statusExportFilter" name="status_id" class="form-control form-select">
                                            <option value="">Select Status</option>
                                            <?php foreach ($statusResult as $statusRow): ?>
                                                <option value="<?= $statusRow['status_id'] ?>">
                                                    <?= $statusRow['status_name'] ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Export Type Selection -->
                                <div class="mb-3">
                                    <label class="form-label">Export</label>
                                    <select id="exportType" name="export_type" class="form-control form-select">
                                        <option value="csv">CSV</option>
                                        <option value="json">JSON</option>
                                        <option value="txt">TXT</option>
                                        <option value="excel">Excel</option>
                                        <option value="pdf">PDF</option>
                                    </select>
                                </div>

                                <!-- Preview and Export Buttons -->
                                <div class="d-flex justify-content-end">
                                    <button type="button" id="previewButton" class="btn btn-secondary me-2">Preview</button>
                                    <button type="submit" class="btn btn-primary">Export</button>
                                </div>
                            </form>

                            <!-- Preview Section -->
                            <div id="previewSection" class="mt-4" style="display: none;">
                                <h6>Log Preview</h6>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>School ID</th>
                                            <th>Full Name</th>
                                            <th>Department</th>
                                            <th>Status</th>
                                            <th>Timestamp</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="previewTableBody">
                                        <!-- Data will be populated dynamically -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            
        </div>
            <div class="row mb-2">
                <div class="col-md-12 py-2">
                    <input type="text" id="searchInput" class="form-control" placeholder="Search by School ID or Name">
                </div>
                <div class="col-2 ml-2 mr-0 ">
                    <select id="dateFilter" class="form-control form-select">
                        <option value="">Select Date</option>
                        <option value="Today">Today</option>
                        <option value="Yesterday">Yesterday</option>
                        <option value="Week">This Week</option>
                        <option value="Month">This Month</option>
                    </select>
                </div>
                <div class="col-2 p-0">
                <select id="departmentFilter" class="form-control form-select">
                    <option value="">Departments</option>
                    <?php 
                    $departmentResult = $conn->query($departmentQuery); // Run query again to make sure it's fresh
                    if ($departmentResult) {
                        while ($departmentRow = $departmentResult->fetch_assoc()) {
                            echo "<option value=\"" . $departmentRow['department_id'] . "\">" . $departmentRow['department_name'] . "</option>";
                        }
                    } else {
                        echo "<option value=\"\">Error loading departments</option>";
                    }
                    ?>
                </select>
                </div>

                <div class="col-2 pl-2">
                    <select id="statusFilter" class="form-control form-select">
                        <option value="">Status</option>
                        <?php
                        // Fetch statuses for the dropdown
                        $statusQuery = "SELECT status_id, status_name FROM status";
                        $statusResult = $conn->query($statusQuery);
                        while ($statusRow = $statusResult->fetch_assoc()):
                        ?>
                            <option value="<?php echo $statusRow['status_id']; ?>"><?php echo $statusRow['status_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-2 p-0">
                    <select id="actionFilter" class="form-control form-select">
                        <option value="">Actions</option>
                        <option value="Entry Gate">Entry Gate</option>
                        <option value="Exit Gate">Exit Gate</option>
                    </select>
                </div>
                <div class="col-2 d-flex align-items-center">
                    <button type="button" id="clearFiltersButton" class="btn btn-danger w-50">Clear</button>
                </div>
            </div>
            
            <table id="logsTable" class="table table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>School ID</th>
                        <th>Full Name</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Timestamp</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($logRow = $combinedLogsResult->fetch_assoc()): ?>
                        <tr data-department-id="<?php echo $logRow['department_id']; ?>" data-status-id="<?php echo $logRow['status_id']; ?>">
                            <td><?php echo $logRow['school_id']; ?></td>
                            <td><?php echo $logRow['full_name']; ?></td>
                            <td><?php echo $logRow['department']; ?></td>
                            <td><?php echo $logRow['status']; ?></td>
                            <td><?php echo $logRow['timestamp']; ?></td>
                            <td><?php echo $logRow['action']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
$(document).ready(function() {
    $('#previewButton').on('click', function() {
        var dateFrom = $('#dateFrom').val();
        var dateTo = $('#dateTo').val();
        var departmentId = $('#departmentExportFilter').val();
        var statusId = $('#statusExportFilter').val();
        var fullName = $('#nameSearchInput').val().toLowerCase();
        // Fetch preview data through AJAX
        $.ajax({
            url: 'preview_logs.php',
            type: 'POST',
            data: {
                date_from: dateFrom,
                date_to: dateTo,
                department_id: departmentId,
                status_id: statusId,
                full_name: fullName
            },
            success: function(response) {
                $('#previewTableBody').html(response);
                $('#previewSection').show();
            },
            error: function() {
                alert('Error fetching preview data.');
            }
        });
    });
});
</script>

</body>
</html>
<?php
// Close the database connection
$conn->close();
?>