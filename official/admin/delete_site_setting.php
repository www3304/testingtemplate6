<?php
// Include the configuration file
require_once 'config.php';

// Check if user is logged in and is admin (adjust permission if needed)
if (!isset($_SESSION['official_admin_logged_in']) || $_SESSION['official_admin_logged_in'] !== true) {
    // Redirect to login page if not logged in
    header('Location: index.php');
    exit;
}

// Get the site setting ID from URL
$setting_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($setting_id <= 0) {
    $_SESSION['error_message'] = 'Invalid site setting ID.';
    header('Location: site_settings.php');
    exit;
}

// First, check if the site setting exists
$check_query = "SELECT id, site_title FROM site_settings WHERE id = ?";
$stmt = $official_admin_connection->prepare($check_query);

if ($stmt === false) {
    $_SESSION['error_message'] = 'Error preparing statement: ' . $official_admin_connection->error;
    header('Location: site_settings.php');
    exit;
}

$stmt->bind_param('i', $setting_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = 'Site setting not found.';
    header('Location: site_settings.php');
    exit;
}

$setting_data = $result->fetch_assoc();
$stmt->close();

// Perform the deletion
$delete_query = "DELETE FROM site_settings WHERE id = ?";
$stmt = $official_admin_connection->prepare($delete_query);

if ($stmt === false) {
    $_SESSION['error_message'] = 'Error preparing delete statement: ' . $official_admin_connection->error;
    header('Location: site_settings.php');
    exit;
}

$stmt->bind_param('i', $setting_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $_SESSION['success_message'] = 'Site setting "' . htmlspecialchars($setting_data['site_title']) . '" deleted successfully.';
    } else {
        $_SESSION['error_message'] = 'No site setting was deleted. It may have already been removed.';
    }
} else {
    $_SESSION['error_message'] = 'Error deleting site setting: ' . $stmt->error;
}

$stmt->close();

// Redirect back to the site settings list
header('Location: site_settings.php');
exit;
?>