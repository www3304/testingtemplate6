<?php
// Include the configuration file
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['official_admin_logged_in']) || $_SESSION['official_admin_logged_in'] !== true) {
    $_SESSION['error_message'] = 'You do not have permission to perform this action.';
    header('Location: index.php');
    exit;
}

// Check if ID is provided via GET
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = 'No API Setting ID specified for deletion.';
    header('Location: api_settings.php'); // Redirect back to the list
    exit;
}

// Sanitize the ID
$setting_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

if ($setting_id === false || $setting_id <= 0) {
    $_SESSION['error_message'] = 'Invalid API Setting ID provided.';
    header('Location: api_settings.php');
    exit;
}

// Prepare the DELETE statement
$sql = "DELETE FROM api_settings WHERE id = ?";
$stmt = $official_admin_connection->prepare($sql);

if ($stmt === false) {
    $_SESSION['error_message'] = 'Failed to prepare delete statement: ' . $official_admin_connection->error;
    header('Location: api_settings.php');
    exit;
}

// Bind the setting ID parameter
$stmt->bind_param('i', $setting_id);

// Execute the statement
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $_SESSION['success_message'] = 'API Setting deleted successfully.';
    } else {
        $_SESSION['error_message'] = 'API Setting not found or could not be deleted.';
    }
} else {
    $_SESSION['error_message'] = 'Error deleting API setting: ' . $stmt->error;
}

// Close the statement
$stmt->close();

// Redirect back to the list page
header('Location: api_settings.php');
exit;

?> 