<?php
// config.php contains credentials, but we need to establish a *new* superadmin connection here
require_once 'config.php'; // We use this for session check and superadmin DB credentials

// Check if user is logged in and is admin
if (!isset($_SESSION['official_admin_logged_in']) || $_SESSION['official_admin_logged_in'] !== true) {
    $_SESSION['error_message'] = 'You do not have permission to access this page.';
    header('Location: index.php');
    exit;
}

// --- Superadmin Database Connection ---
$sa_conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($sa_conn->connect_error) {
    $_SESSION['error_message'] = 'Failed to connect to the administrative database.';
    // Optionally log the detailed error: error_log('Superadmin DB Connection Error: ' . $sa_conn->connect_error);
    header('Location: users.php'); // Redirecting to user page
    exit;
}
// --- End Superadmin Database Connection ---

// Get current domain and game type
$current_domain = $_SERVER['HTTP_HOST'];
// $mini_game_type = 7; // As defined in config.php logic // Now set below

// --- Fetch Domain ID (needed for mini_games query) ---
$domain_id = null;
$fetch_domain_sql = "SELECT id FROM domains WHERE domain = ?";
$fetch_domain_stmt = $sa_conn->prepare($fetch_domain_sql);
if ($fetch_domain_stmt) {
    $fetch_domain_stmt->bind_param("s", $current_domain);
    $fetch_domain_stmt->execute();
    $domain_result = $fetch_domain_stmt->get_result();
    if ($domain_result->num_rows === 1) {
        $domain_row = $domain_result->fetch_assoc();
        $domain_id = (int)$domain_row['id'];
    } else {
        // Domain not found in domains table
        $_SESSION['error_message'] = 'Domain configuration not found in the system.';
        // Clear other variables to prevent further processing
        $domain_id = null; 
    }
    $fetch_domain_stmt->close();
} else {
    $_SESSION['error_message'] = 'Failed to prepare domain lookup: ' . $sa_conn->error;
    $domain_id = null; // Ensure it's null on error
}

$game_type_id = 10; // Specific game type for Official

// --- Handle POST Request (Toggle Action) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if action is provided
    if (!isset($_POST['action']) || ($_POST['action'] !== 'turn_on' && $_POST['action'] !== 'turn_off')) {
        $_SESSION['error_message'] = 'Invalid action specified.';
    } else {
        // Determine the new status based on the action
        $action = $_POST['action'];
        $new_status = ($action === 'turn_on') ? 2 : 1; // Turn ON sets status to 2, Turn OFF sets to 1

        // Prepare the UPDATE statement for mini_games table
        $update_sql = "UPDATE mini_games SET status = ? WHERE domain_id = ? AND game_type = ?";
        $update_stmt = $sa_conn->prepare($update_sql);

        if ($update_stmt) {
            // Bind status, domain_id, game_type_id
            $update_stmt->bind_param("iii", $new_status, $domain_id, $game_type_id);

            // Execute the statement
            if ($update_stmt->execute()) {
                if ($update_stmt->affected_rows > 0) {
                    $status_text_msg = ($new_status === 2) ? 'ON' : 'OFF';
                    $_SESSION['success_message'] = "Maintenance mode successfully turned {$status_text_msg}.";
                } else {
                    $_SESSION['error_message'] = 'Could not update maintenance status. Game configuration may not exist or status was already set.';
                }
            } else {
                $_SESSION['error_message'] = 'Error updating maintenance status: ' . $update_stmt->error;
            }
            $update_stmt->close();
        } else {
            $_SESSION['error_message'] = 'Failed to prepare update statement: ' . $sa_conn->error;
        }
    }
    // Redirect back to the same page using GET to prevent form resubmission on refresh
    header('Location: maintenance.php');
    exit;
}
// --- End Handle POST Request ---


// --- Fetch Current Status for Display (GET Request or after POST redirect) ---
$current_status = null;
$status_text = 'Unknown';
$button_text = 'Toggle Status';
$button_class = 'bg-gray-600 hover:bg-gray-700'; // Default/disabled look
$next_action = ''; // To be determined

// Fetch status from mini_games only if domain_id is valid and no prior error
if ($domain_id !== null && !isset($_SESSION['error_message'])) {
    $fetch_sql = "SELECT status FROM mini_games WHERE domain_id = ? AND game_type = ?";
    $fetch_stmt = $sa_conn->prepare($fetch_sql);

    if ($fetch_stmt) {
        $fetch_stmt->bind_param("ii", $domain_id, $game_type_id);
        $fetch_stmt->execute();
        $result = $fetch_stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $current_status = (int)$row['status'];

            if ($current_status === 1) {
                $status_text = 'OFF (Active)';
                $button_text = 'Turn Maintenance ON';
                $button_class = 'bg-red-600 hover:bg-red-700';
                $next_action = 'turn_on';
            } elseif ($current_status === 2) {
                $status_text = 'ON (Maintenance)';
                $button_text = 'Turn Maintenance OFF';
                $button_class = 'bg-green-600 hover:bg-green-700';
                $next_action = 'turn_off';
            } else {
                // Status is not 1 or 2 (includes 0 inactive, or others)
                $status_text = 'UNKNOWN/INACTIVE'; // Indicate the unusual state
                $button_text = 'Turn Maintenance ON';
                $button_class = 'bg-red-600 hover:bg-red-700';
                $next_action = 'turn_on';
                // Optionally set an info/warning message
                if (!isset($_SESSION['error_message']) && !isset($_SESSION['success_message'])) { // Avoid overwriting previous messages
                    $_SESSION['info_message'] = 'Game status is currently not Active (1) or Maintenance (2). Current status: ' . $current_status . '. Toggle will attempt to set to Maintenance (2).';
                }
            }
        } else {
            // Set error message only if no message is already set from POST handling
            if (!isset($_SESSION['error_message'])) {
                 $_SESSION['error_message'] = 'Could not find configuration for this specific game (Type: ' . $game_type_id . ') on this domain.';
            }
        }
        $fetch_stmt->close();
    } else {
         if (!isset($_SESSION['error_message'])) {
            $_SESSION['error_message'] = 'Failed to prepare statement to fetch maintenance status.';
         }
    }
} else {
    // Set error message only if no message is already set from POST or domain lookup
    if (!isset($_SESSION['error_message'])) {
         $_SESSION['error_message'] = 'Could not find configuration for this specific game (Type: ' . $game_type_id . ') on this domain.';
    }
}

// Close the superadmin connection
$sa_conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Mode - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="assets/js/tailwind_color.js"></script>
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex flex-col">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Header -->
            <?php include 'header.php'; ?>

            <!-- Main Content Area -->
            <main class="flex-1 p-6">
                <!-- Page Header -->
                <div class="mb-6">
                    <h2 class="text-xl font-bold text-gray-900">Maintenance Mode</h2>
                    <p class="mt-1 text-sm text-gray-500">Control website accessibility for maintenance.</p>
                </div>

                <!-- Display Session Messages -->
                <?php 
                // Display messages only once after potential redirect
                if (isset($_SESSION['success_message'])):
                ?>
                    <div class="mb-4 p-4 bg-green-100 text-green-700 border border-green-200 rounded-md">
                        <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php 
                endif;
                if (isset($_SESSION['error_message'])):
                ?>
                    <div class="mb-4 p-4 bg-red-100 text-red-700 border border-red-200 rounded-md">
                        <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif;
                // Display info messages (e.g., for non-standard status)
                if (isset($_SESSION['info_message'])): 
                ?>
                    <div class="mb-4 p-4 bg-blue-100 text-blue-700 border border-blue-200 rounded-md">
                        <?php echo htmlspecialchars($_SESSION['info_message']); ?>
                    </div>
                    <?php unset($_SESSION['info_message']); ?>
                <?php endif; ?>

                <!-- Maintenance Status Display and Control -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Current Status</h3>
                    <p class="text-lg mb-6">
                        Maintenance Mode is currently: <span class="font-semibold <?php echo ($current_status === 2) ? 'text-red-600' : 'text-green-600'; ?>"><?php echo htmlspecialchars($status_text); ?></span>
                    </p>

                    <?php if ($current_status !== null): // Only show button if status could be determined (even if unknown state) ?>
                        <form method="POST"> 
                             <input type="hidden" name="action" value="<?php echo $next_action; ?>"> 
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white <?php echo $button_class; ?> focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <?php echo htmlspecialchars($button_text); ?>
                            </button>
                        </form>
                        <p class="mt-3 text-sm text-gray-500">
                            <?php if ($current_status === 1): ?>
                                Clicking the button will put the Shopping game into maintenance mode (status 2). Users may not be able to access it.
                            <?php elseif ($current_status === 2): ?>
                                Clicking the button will take the Shopping game out of maintenance mode (status 1), making it active again.
                            <?php else: // Handle the case where status is known but not 1 or 2 ?>
                                The game is currently in an unexpected state (<?php echo htmlspecialchars($status_text); ?>). Clicking will attempt to set it to Maintenance (status 2).
                            <?php endif; ?>
                        </p>
                    <?php else: ?>
                         <p class="text-sm text-red-600">Could not determine the current maintenance status or an error occurred. Button is disabled.</p>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</body>
</html> 