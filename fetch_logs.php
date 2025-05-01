<?php
include "connection.php";

$query = "SELECT admin_log.admin_log, login.username, admin_log.Date, admin_log.log_activity, admin_log.page, 
                 admin_log.action_type, admin_log.target_entity, admin_log.entity_id
          FROM admin_log
          JOIN login ON admin_log.login_id = login.id
          ORDER BY admin_log.Date DESC";

$result = $conn->query($query);

$logs = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($logs);
?>
