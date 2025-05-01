<?php
$settings_file = 'display_config.json';

// Check if the JSON file exists
if (file_exists($settings_file)) {
    $settings = json_decode(file_get_contents($settings_file), true);

    if ($settings['refresh'] ?? false) {
        // Set 'refresh' to false
        $settings['refresh'] = false;
        file_put_contents($settings_file, json_encode($settings));

        // Send response indicating the page should refresh
        echo json_encode(['refresh' => true]);
    } else {
        // Send response indicating no refresh needed
        echo json_encode(['refresh' => false]);
    }
} else {
    // Handle the case where the file is missing
    echo json_encode(['error' => 'Settings file not found.']);
}
?>
