<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Logs</title>
    <script src="./js/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="./css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="./css/bootstrap.css" >
    <script src="./js/jquery.dataTables.min.js"></script>
    <script src="./js/bootstrap.js"></script>
    
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

        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #343a40;
            color: white;
        }

    </style>
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
        <div class="collapse show" id="home-collapse">
          <ul class="btn-toggle-nav list-unstyled fw-normal small">
            <li><a href="create_user.php">Register User</a></li>
            <li><a href="admin_logs.php"  class="active">Admin logs</a></li>
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
        <h3 style="color:white">Admin Logs</h3>
    </nav>
    <div class="container my-2">

        <div class="d-flex justify-content-between align-items-center mb-4">
            
        </div>
        
        <table id="adminLogsTable" class="display">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Date</th>
                    <th>Activity</th>
                    <th>Page</th>
                    <th>Action Type</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>
<script>
    $(document).ready(function () {
        // Fetch logs data via AJAX
        $.ajax({
            url: "fetch_logs.php",
            method: "GET",
            dataType: "json",
            success: function (data) {
                // Populate table with fetched data
                let tableBody = $("#adminLogsTable tbody");
                data.forEach(log => {
                    tableBody.append(`
                        <tr>
                            <td>${log.username}</td>
                            <td>${log.Date}</td>
                            <td>${log.log_activity}</td>
                            <td>${log.page}</td>
                            <td>${log.action_type}</td>
                        </tr>
                    `);
                });

                // Initialize DataTables
                $("#adminLogsTable").DataTable({
                    order: [[1, "desc"]] // Sort by Date column descending
                });
            },
            error: function () {
                alert("Failed to fetch logs.");
            }
        });
    });
</script>
</body>
</html>
