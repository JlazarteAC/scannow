<?php
session_start();
include 'connection.php';
include 'activity_logger.php';
require './dompdf/autoload.inc.php'; // Load DomPDF

use Dompdf\Dompdf;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fromDate = $_POST['date_from'] ?? '';
    $toDate = $_POST['date_to'] ?? '';
    $department = $_POST['department_id'] ?? '';
    $status = $_POST['status_id'] ?? '';
    $exportType = $_POST['export_type'] ?? '';

    if (empty($fromDate) || empty($toDate)) {
        echo "Please select both a 'From' and 'To' date.";
        exit;
    }

    // Query to filter logs based on selected criteria
    $query = "
        SELECT log_entry.school_id, 
               TRIM(CONCAT(user.first_name, ' ', IFNULL(user.middle_name, ''), ' ', user.last_name)) AS full_name, 
               log_entry.timestamp AS timestamp, 
               'Entry' AS action,
               department.department_name AS department,
               status.status_name AS status
        FROM log_entry
        JOIN user ON log_entry.school_id = user.school_id
        JOIN status ON user.status_id = status.status_id
        JOIN department ON status.department_id = department.department_id
        WHERE log_entry.timestamp BETWEEN ? AND ?
    ";

    $params = [$fromDate, $toDate];
    if ($department) {
        $query .= " AND department.department_id = ?";
        $params[] = $department;
    }
    if ($status) {
        $query .= " AND status.status_id = ?";
        $params[] = $status;
    }

    // Execute the query with parameters
    $stmt = $conn->prepare($query);
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch the data for exporting
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }

    if (empty($logs)) {
        echo "No logs found for the selected criteria.";
        exit;
    }

    // Log the export action
    if (isset($_SESSION['login_id'])) {
        $admin_id = $_SESSION['login_id'];
        $criteria = "From: $fromDate, To: $toDate";
        if ($department) $criteria .= ", Department: $department";
        if ($status) $criteria .= ", Status: $status";

        $description = "Requested export as $exportType with criteria ($criteria)";
        logAdminActivity($admin_id, "export.php", "EXPORT", $description);
    }

    // Handle export based on selected format
    switch ($exportType) {
        case 'csv':
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment;filename="logs.csv"');
            $output = fopen('php://output', 'w');
            fputcsv($output, array_keys($logs[0]));
            foreach ($logs as $log) {
                fputcsv($output, $log);
            }
            fclose($output);
            break;

        case 'json':
            header('Content-Type: application/json');
            header('Content-Disposition: attachment;filename="logs.json"');
            echo json_encode($logs, JSON_PRETTY_PRINT);
            break;

        case 'txt':
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment;filename="logs.txt"');
            foreach ($logs as $log) {
                echo implode("\t", $log) . PHP_EOL;
            }
            break;

        case 'excel':
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="logs.xls"');
            echo "<table border='1'>";
            echo "<tr><th>" . implode("</th><th>", array_keys($logs[0])) . "</th></tr>";
            foreach ($logs as $log) {
                echo "<tr><td>" . implode("</td><td>", $log) . "</td></tr>";
            }
            echo "</table>";
            break;

        case 'pdf':
            // Generate HTML for the PDF
            $html = '
                <h1>Log Activity Report</h1>
                <table border="1" cellspacing="0" cellpadding="5">
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
                    <tbody>';
            foreach ($logs as $log) {
                $html .= '
                        <tr>
                            <td>' . htmlspecialchars($log['school_id']) . '</td>
                            <td>' . htmlspecialchars($log['full_name']) . '</td>
                            <td>' . htmlspecialchars($log['department']) . '</td>
                            <td>' . htmlspecialchars($log['status']) . '</td>
                            <td>' . htmlspecialchars($log['timestamp']) . '</td>
                            <td>' . htmlspecialchars($log['action']) . '</td>
                        </tr>';
            }
            $html .= '
                    </tbody>
                </table>';

            // Use DomPDF to create the PDF
            $dompdf = new Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            // Stream the PDF to the browser
            $dompdf->stream("logs.pdf", ["Attachment" => 1]);
            break;

        default:
            echo "Invalid export type selected.";
            break;
    }

    exit;
}
?>
