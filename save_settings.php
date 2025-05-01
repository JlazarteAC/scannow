<?php
include 'connection.php';

$uploadDir = 'Display-Video/';
$videoFile = '';
$loopVideo = isset($_POST['loopVideo']) ? 1 : 0;
$muteVideo = isset($_POST['muteVideo']) ? 1 : 0;
$volume = isset($_POST['volume']) ? (int)$_POST['volume'] : 50;
$roleText = isset($_POST['roleText']) ? $_POST['roleText'] : '';

// Fetch the current video file from the database
$result = $conn->query("SELECT video_file FROM display_settings LIMIT 1");
$row = $result->fetch_assoc();
$currentVideoFile = $row['video_file'];

if ($_FILES['videoFile']['error'] === UPLOAD_ERR_OK) {
    $tmpName = $_FILES['videoFile']['tmp_name'];
    $fileName = basename($_FILES['videoFile']['name']);
    $videoFile = $uploadDir . $fileName;

    // Check if the new file has a different name than the current one
    if ($videoFile !== $currentVideoFile) {
        // Move the uploaded file to the Display-Video directory
        if (move_uploaded_file($tmpName, $videoFile)) {
            // Delete the old video file if a new one is uploaded successfully
            if ($currentVideoFile && file_exists($currentVideoFile)) {
                unlink($currentVideoFile);
            }
            echo "File uploaded and old video deleted successfully.";
        } else {
            echo "Failed to upload file.";
            $videoFile = $currentVideoFile; // Revert to the old video if the upload fails
        }
    } else {
        // If the filenames are the same, do not delete the file
        echo "The same file was uploaded. No changes were made to the file.";
    }
} else {
    $videoFile = $currentVideoFile; // Keep the previous video if no new one is uploaded
}

// Update settings in the database (assuming there's only one row in the table)
$sql = "UPDATE display_settings SET video_file = ?, loop_video = ?, mute_video = ?, volume = ?, role_text = ? WHERE id = 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("siiis", $videoFile, $loopVideo, $muteVideo, $volume, $roleText);

if ($stmt->execute()) {
    echo "Settings updated successfully.";
} else {
    echo "Error updating settings: " . $conn->error;
}

$stmt->close();
$conn->close();

header("Location: settings.php");
exit();
?>
