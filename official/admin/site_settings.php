<?php
// Include the configuration file
require_once 'config.php';

// Check if user is logged in and is admin (adjust permission if needed)
if (!isset($_SESSION['official_admin_logged_in']) || $_SESSION['official_admin_logged_in'] !== true) {
    // Redirect to login page if not logged in
    header('Location: index.php');
    exit;
}

// Get site settings list from database
$settings_query = "SELECT id, site_title, is_active, created_at, updated_at FROM site_settings ORDER BY id ASC";
$settings_result = $official_admin_connection->query($settings_query);

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
    <title>Site Settings - Admin Dashboard</title>
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
            content: "\21f5";
            color: #a3a3a3;
            font-size: 0.75em;
        }
        
        table.dataTable thead th.sorting_asc:after {
            content: "\2191";
            color: #0ea5e9;
        }
        
        table.dataTable thead th.sorting_desc:after {
            content: "\2193";
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
                        <h2 class="text-xl font-bold text-gray-900">Site Settings</h2>
                        <p class="mt-1 text-sm text-gray-500">Manage website appearance and content</p>
                    </div>
                    <div class="mt-4 md:mt-0 flex space-x-3">
                        <a href="add_site_setting.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Add Site Setting
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
                        <input type="text" id="settingsSearch" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm" placeholder="Search settings by title...">
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
                        <table id="settingsTable" class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        ID
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Site Title 
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Created At
                                    </th>
                                     <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Updated At
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Action
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php 
                                if ($settings_result && $settings_result->num_rows > 0) {
                                    while ($setting = $settings_result->fetch_assoc()) { 
                                ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($setting['id']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center rounded-full bg-primary-100 text-primary-600">
                                                <!-- Generic Settings Icon -->
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($setting['site_title']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($setting['is_active'] == 1): ?>
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                Active
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                                Inactive
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo formatDateTime($setting['created_at']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo formatDateTime($setting['updated_at']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end items-center h-full space-x-2">
                                            <a href="edit_site_setting.php?id=<?php echo $setting['id']; ?>" class="text-primary-600 hover:text-primary-900" title="Edit Setting">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                            </a>
                                            <!-- Delete Button -->
                                            <button onclick="deleteSetting(<?php echo $setting['id']; ?>, '<?php echo htmlspecialchars(addslashes($setting['site_title'])); ?>')" class="text-red-600 hover:text-red-900" title="Delete Setting">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php 
                                    }
                                } else {
                                ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No site settings found. <a href="add_site_setting.php" class="text-primary-600 hover:text-primary-900">Add the first one</a>.
                                    </td>
                                </tr>
                                <?php    
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
            var table = $('#settingsTable').DataTable({
                responsive: true,
                paging: true,
                ordering: true,
                info: true,
                lengthChange: true,
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50, 100],
                order: [[ 0, 'asc' ]], // Default sort by ID 
                dom: '<"top"l>rt<"bottom"ip>', // Adjusted dom to place length menu top-left, pagination bottom
                language: {
                    search: "", // We use a custom search input
                    searchPlaceholder: "Search settings...",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    emptyTable: 'No site settings found. <a href="add_site_setting.php" class="text-primary-600 hover:text-primary-900">Add the first setting</a>.',
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                },
                 columnDefs: [
                     // Disable sorting for Site Title, Action (indices 1, 5)
                    { targets: [1, 5], orderable: false }, 
                     // Define columns that are searchable (ID and Site Title)
                    { targets: [0, 1], searchable: true },
                    // Disable search for other columns
                    { targets: [2, 3, 4, 5], searchable: false } 
                 ],
                initComplete: function() {
                    // Hide DataTables' default search and use our custom search box
                    $('.dataTables_filter').hide();
                    
                    // Connect our custom search box to DataTables search for the 'Site Title' column (index 1)
                    $('#settingsSearch').on('keyup', function() {
                        table.columns(1).search(this.value).draw(); // Search Site Title column
                    });
                }
            });
            
            // Style the "Show entries" dropdown
            $('.dataTables_length select').addClass('border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500');
        });

        // Function to handle setting deletion confirmation
        function deleteSetting(settingId, siteTitle) {
            if (confirm(`Are you sure you want to delete the site setting "${siteTitle}" (ID: ${settingId})? This action cannot be undone.`)) {
                // If confirmed, redirect to the delete script
                window.location.href = `delete_site_setting.php?id=${settingId}`; 
            }
        }
    </script>
</body>
</html> 