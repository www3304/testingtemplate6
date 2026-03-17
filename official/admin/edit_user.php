<?php
// Include the configuration file
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['official_admin_logged_in']) || $_SESSION['official_admin_logged_in'] !== true) {
    // Redirect to login page if not logged in
    header('Location: index.php');
    exit;
}

// Check if an ID is provided in the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirect back to user list if no ID provided
    header('Location: users.php');
    exit;
}

$user_id = intval($_GET['id']);
$user_data = [];
$error_message = '';
$success_message = '';

// Get user data from database initially to populate form
// Updated query to select only existing columns
$user_query = "SELECT id, username, created_at FROM users WHERE id = ?"; 
$stmt_get = $official_admin_connection->prepare($user_query);
if ($stmt_get === false) {
     // Handle error, maybe redirect or show generic error
     error_log("Prepare failed: (" . $official_admin_connection->errno . ") " . $official_admin_connection->error);
     header('Location: users.php'); // Redirect on error
     exit;
}
$stmt_get->bind_param('i', $user_id);
$stmt_get->execute();
$result = $stmt_get->get_result();

if ($result->num_rows === 0) {
    // User not found, redirect back to user list
    header('Location: users.php');
    exit;
}
$user_data = $result->fetch_assoc();
$stmt_get->close();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? ''); // New password (optional)

    // Validate required fields and data types
    if (empty($username)) { 
        $error_message = 'Invalid data submitted. Please check username.';
    } else {
         // Check if username needs to be updated and if it conflicts
        if ($username !== $user_data['username']) {
            $check_query = "SELECT COUNT(*) as count FROM users WHERE username = ? AND id != ?";
            $stmt_check = $official_admin_connection->prepare($check_query);
             if ($stmt_check === false) {
                 $error_message = 'Error shoppingwithpointg username: ' . $official_admin_connection->error;
            } else {
                 $stmt_check->bind_param('si', $username, $user_id);
                 $stmt_check->execute();
                 $result_check = $stmt_check->get_result();
                 $row_check = $result_check->fetch_assoc();
                 $stmt_check->close();
                 if ($row_check['count'] > 0) {
                     $error_message = 'Username already taken by another user.';
                 }
            }
        }
        
        // Proceed if no username conflict error
        if (empty($error_message)) {
             // Prepare base update query
            $update_fields = [];
            $update_params = [];
            $types = '';

             // Add fields to update
            $update_fields[] = "username = ?";
            $update_params[] = $username;
            $types .= 's';

             // Check if password needs to be updated
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                if ($hashed_password === false) {
                    $error_message = 'Error hashing new password.';
                } else {
                    $update_fields[] = "password = ?";
                    $update_params[] = $hashed_password;
                    $types .= 's';
                }
            }
            
            // Only proceed if password hashing didn't fail (if attempted)
            if (empty($error_message)) {
                // Add the user ID for the WHERE clause
                $update_params[] = $user_id;
                $types .= 'i';

                // Construct the final query
                $update_query = "UPDATE users SET " . implode(", ", $update_fields) . " WHERE id = ?";
                
                $stmt_update = $official_admin_connection->prepare($update_query);
                if ($stmt_update === false) {
                     $error_message = 'Error preparing update statement: ' . $official_admin_connection->error;
                } else {
                     $stmt_update->bind_param($types, ...$update_params);
                    
                    if ($stmt_update->execute()) {
                        $success_message = 'User updated successfully!';
                        // Re-fetch data to show updated values in the form
                        $stmt_get = $official_admin_connection->prepare($user_query); // Re-use the get query
                         if($stmt_get) {
                             $stmt_get->bind_param('i', $user_id);
                            $stmt_get->execute();
                            $result = $stmt_get->get_result();
                            $user_data = $result->fetch_assoc(); // Update $user_data array
                            $stmt_get->close();
                        }
                    } else {
                        $error_message = 'Error updating user: ' . $stmt_update->error;
                    }
                     $stmt_update->close();
                }
            }
        }
    }
     // If there was an error, ensure the form shows the submitted (but potentially invalid) data
    if (!empty($error_message)) {
        $user_data['username'] = $username; // Keep submitted username
        // Don't repopulate password field for security
    }
}

// Function to format datetime for display (can be moved to a helper file)
function formatDisplayDateTime($dateTimeStr) {
    if (empty($dateTimeStr) || $dateTimeStr === '0000-00-00 00:00:00') {
        return 'N/A';
    }
    try {
        $date = new DateTime($dateTimeStr);
        return $date->format('M d, Y H:i:s');
    } catch (Exception $e) {
        return 'Invalid Date';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Tailwind Color -->
    <script src="assets/js/tailwind_color.js"></script>
    <style>
        /* Improved input styles */
        .form-input {
            transition: all 0.2s ease-in-out;
            border-width: 1px;
        }
        .form-input:focus {
            border-color: #0ea5e9;
            box-shadow: 0 0 0 2px rgba(14, 165, 233, 0.25);
        }
        .input-icon-container {
            position: relative;
        }
        .input-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
        }
        .input-with-icon {
            padding-left: 2.5rem;
        }
        .form-select {
            background-position: right 0.75rem center;
            background-repeat: no-repeat;
            background-size: 1em 1em;
            padding-right: 2.5rem;
            transition: all 0.2s ease-in-out;
        }
        .form-select:focus {
            border-color: #0ea5e9;
            box-shadow: 0 0 0 2px rgba(14, 165, 233, 0.25);
        }
    </style>
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
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Edit User (#<?php echo $user_id; ?>)</h2>
                            <p class="mt-1 text-sm text-gray-500">Modify user account details</p>
                        </div>
                        <div>
                            <a href="users.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                Back to Users
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Form Card -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="p-6">
                        <?php if (!empty($error_message)): ?>
                        <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 text-red-700">
                            <p><?php echo htmlspecialchars($error_message); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success_message)): ?>
                        <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 text-green-700">
                            <p><?php echo htmlspecialchars($success_message); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <form action="edit_user.php?id=<?php echo $user_id; ?>" method="POST">
                            <div class="space-y-6">
                                 <!-- Username Field -->
                                <div>
                                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                                    <div class="input-icon-container">
                                        <input type="text" name="username" id="username" required class="form-input input-with-icon shadow-sm block w-full px-3 py-2.5 border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:ring-0" value="<?php echo htmlspecialchars($user_data['username']); ?>">
                                    </div>
                                </div>

                                <!-- Password Field (Optional) -->
                                <div>
                                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Password (Optional)</label>
                                    <div class="relative input-icon-container">
                                        <input type="password" name="password" id="password" class="form-input input-with-icon shadow-sm block w-full px-3 py-2.5 border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:ring-0" placeholder="Leave blank to keep current password">
                                        <button type="button" id="toggle-password" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" id="eye-icon">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" id="eye-off-icon">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Created Time (Readonly) - Moved to be the only item in this row -->
                                <div class="md:col-span-2"> 
                                    <label for="created_time" class="block text-sm font-medium text-gray-700 mb-1">Created At</label>
                                    <input type="text" name="created_time" id="created_time" class="form-input shadow-sm block w-full px-3 py-2.5 border-gray-300 rounded-md text-sm bg-gray-50 focus:outline-none focus:ring-0 cursor-not-allowed" value="<?php echo formatDisplayDateTime($user_data['created_at']); // Changed to created_at ?>" readonly>
                                </div>

                                <!-- Form Actions -->
                                <div class="flex justify-end space-x-3 pt-8">
                                    <a href="users.php" class="inline-flex justify-center py-2.5 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors">
                                        Cancel
                                    </a>
                                    <button type="submit" class="inline-flex justify-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all transform hover:scale-105">
                                        Save Changes
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script>
        // Toggle password visibility
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('toggle-password');
            const passwordField = document.getElementById('password'); // Corrected ID
            const eyeIcon = document.getElementById('eye-icon');
            const eyeOffIcon = document.getElementById('eye-off-icon');
            // const viewPasswordText = document.getElementById('view-password-text'); // Removed, as it wasn't in the original HTML
            
            if (togglePassword && passwordField) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordField.setAttribute('type', type);
                    
                    // Toggle eye icons
                    eyeIcon.classList.toggle('hidden');
                    eyeOffIcon.classList.toggle('hidden');
                    
                    // Toggle text (Removed, as element didn't exist)
                    // viewPasswordText.textContent = type === 'password' ? 'View Password' : 'Hide Password'; 
                });
            }
        });
    </script>
</body>
</html>
