<?php
// Include the configuration file
require_once 'config.php';

// Check if user is logged in and is superadmin
if (!isset($_SESSION['official_admin_logged_in']) || $_SESSION['official_admin_logged_in'] !== true) {
    // Redirect to login page if not logged in
    header('Location: index.php');
    exit;
}

// Get USER list from database, but keep variable name for less HTML changes if desired
$user_query = "SELECT id, username, created_at FROM users ORDER BY id ASC"; // Fetch only needed columns
$domain_result = $official_admin_connection->query($user_query); // Use user query, keep $domain_result variable

// Function to format datetime strings or return 'N/A' if null/invalid
function formatDateTime($dateTimeStr) {
    if (empty($dateTimeStr) || $dateTimeStr === '0000-00-00 00:00:00') {
        return 'N/A';
    }
    try {
        $date = new DateTime($dateTimeStr);
        return $date->format('M d, Y H:i:s'); // Format includes time
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
    <title>User Management - Admin Dashboard</title> <!-- Changed Title -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <!-- Tailwind Color -->
    <script src="assets/js/tailwind_color.js"></script>
    <style>
        /* Table styles */
        .table-container {
            overflow-x: auto;
        }
        
        /* DataTables custom styling to match current UI */
        .dataTables_wrapper .dataTables_length, 
        .dataTables_wrapper .dataTables_filter, 
        .dataTables_wrapper .dataTables_info, 
        .dataTables_wrapper .dataTables_processing, 
        .dataTables_wrapper .dataTables_paginate {
            color: #6b7280;
            font-size: 0.875rem;
            padding: 1rem;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #f0f9ff;
            border-color: #0ea5e9;
            color: #0284c7 !important;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #f0f9ff;
            border-color: #0ea5e9;
            color: #0284c7 !important;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            font-size: 0.875rem;
            font-weight: 500;
            border-radius: 0.375rem;
            padding: 0.5rem 0.75rem;
        }
        
        table.dataTable thead th {
            position: relative;
            background-image: none !important;
        }
        
        table.dataTable thead th.sorting:after,
        table.dataTable thead th.sorting_asc:after,
        table.dataTable thead th.sorting_desc:after {
            position: absolute;
            right: 8px;
            display: block;
            font-family: "Segoe UI Symbol";
        }
        
        table.dataTable thead th.sorting:after {
            content: "⇕";
            color: #a3a3a3;
            font-size: 0.75em;
        }
        
        table.dataTable thead th.sorting_asc:after {
            content: "↑";
            color: #0ea5e9;
        }
        
        table.dataTable thead th.sorting_desc:after {
            content: "↓";
            color: #0ea5e9;
        }
        
        /* Hide the default search box - we'll use our own */
        .dataTables_filter {
            display: none;
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
                <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Users</h2> <!-- Changed Header -->
                        <p class="mt-1 text-sm text-gray-500">Manage all registered users in the system</p> <!-- Changed Subheader -->
                    </div>
                    <div class="mt-4 md:mt-0 flex space-x-3">
                        <a href="add_user.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"> <!-- Changed Link -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Add User <!-- Changed Text -->
                        </a>
                    </div>
                </div>
                
                <!-- Search Bar -->
                <div class="mb-6">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" id="userSearch" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm" placeholder="Search users by username..."> <!-- Changed ID and Placeholder -->
                    </div>
                </div>

                <!-- Display Success/Error Messages -->
                <?php if (isset($_SESSION['success_message'])) : ?>
                <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 text-green-700">
                    <p><?php echo htmlspecialchars($_SESSION['success_message']); ?></p>
                </div>
                <?php unset($_SESSION['success_message']); endif; ?>

                <?php if (isset($_SESSION['error_message'])) : ?>
                <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 text-red-700">
                    <p><?php echo htmlspecialchars($_SESSION['error_message']); ?></p>
                </div>
                <?php unset($_SESSION['error_message']); endif; ?>
                
                <!-- Table -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="table-container">
                        <table id="userTable" class="min-w-full divide-y divide-gray-200"> <!-- Changed Table ID -->
                            <thead class="bg-gray-50">
                                <!-- KEEPING ORIGINAL DOMAIN HEADERS -->
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        ID
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Username 
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Created Date
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Action
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php 
                                // Use the query result (still named $domain_result for simplicity)
                                if ($domain_result && $domain_result->num_rows > 0) {
                                    // Loop through USERS, map to DOMAIN columns
                                    while ($user = $domain_result->fetch_assoc()) { 
                                ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($user['id']); // USER ID ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center rounded-full bg-primary-100 text-primary-600">
                                                <!-- Generic User Icon -->
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['username']); // USERNAME ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo formatDateTime($user['created_at']); // USER Created Time ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end items-center h-full space-x-2">
                                            <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="text-primary-600 hover:text-primary-900" title="Edit User"> <!-- Edit User Link -->
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                            </a>
                                            <!-- Delete Button -->
                                            <button onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['username'])); ?>')" class="text-red-600 hover:text-red-900" title="Delete User">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php 
                                    }
                                } 
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Initialize DataTable with options to match current UI
            var table = $('#userTable').DataTable({ // Changed Table ID
                responsive: true,
                paging: true,
                ordering: true,
                info: true,
                lengthChange: true,
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50, 100],
                order: [[ 0, 'asc' ]], // Default sort by User ID 
                dom: '<"top"fl>rt<"bottom"ip>',
                language: {
                    search: "", // We use a custom search input
                    searchPlaceholder: "Search users...", // Changed Placeholder
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    emptyTable: 'No users found. <a href="add_user.php" class="text-primary-600 hover:text-primary-900">Add the first user</a>.', // Corrected quotes
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                },
                 columnDefs: [
                     // Disable sorting for Username, Action
                    { targets: [1, 3], orderable: false }, 
                    // Disable search for Action column
                    { targets: [3], searchable: false } 
                 ],
                initComplete: function() {
                    // Hide DataTables' default search and use our custom search box
                    $('.dataTables_filter').hide();
                    
                    // Connect our custom search box to DataTables search for the 'Username' column (index 1)
                    $('#userSearch').on('keyup', function() { // Changed Search Input ID
                        table.columns(1).search(this.value).draw(); // Search Username column
                    });
                }
            });
            
            // Style the "Show entries" dropdown
            $('.dataTables_length select').addClass('border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500');
        });

        // Function to handle user deletion confirmation
        function deleteUser(userId, username) {
            if (confirm(`Are you sure you want to delete the user "${username}" (ID: ${userId})? This action cannot be undone.`)) {
                // If confirmed, redirect to the delete script
                window.location.href = `delete_user.php?id=${userId}`; 
                // Alternatively, use AJAX for a smoother experience without page reload
                // $.post('delete_user.php', { id: userId }, function(response) {
                //     // Handle response (e.g., reload table or show message)
                //     console.log(response); 
                //     table.ajax.reload(); // Requires server-side processing setup for DataTables
                // });
            }
        }
    </script>
</body>
</html>