<?php
include "connection.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the user ID and enroll status from the request
    $id = intval($_POST['id']);
    $enroll = intval($_POST['enroll']);

    // Only update enroll_date and expiration_date if enroll = 1
    if ($enroll === 1) {
        // Read academic year data from academic_config.json
        $configFile = 'academic_config.json';
        if (file_exists($configFile)) {
            $configData = json_decode(file_get_contents($configFile), true);

            // Get the academic year start and end dates
            $enrollDate = $configData['academicYearStart'];
            $expirationDate = $configData['academicYearEnd'];

            // Update the enroll status and dates in the database
            $stmt = $conn->prepare("UPDATE user SET enroll = ?, enroll_date = ?, expiration_date = ? WHERE id = ?");
            $stmt->bind_param("issi", $enroll, $enrollDate, $expirationDate, $id);
        } else {
            echo "Error: academic_config.json not found.";
            exit;
        }
    } else {
        // Only update the enroll status if unchecked
        $stmt = $conn->prepare("UPDATE user SET enroll = ? WHERE id = ?");
        $stmt->bind_param("ii", $enroll, $id);
    }

    // Execute the query
    $stmt->execute();

    // Close the statement and connection
    $stmt->close();
    $conn->close();

    echo "Success: Data updated.";
}
?>
