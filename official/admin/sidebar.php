<style>
/* Custom scrollbar */
::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
::-webkit-scrollbar-track {
    background: #f1f1f1;
}
::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}
::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Sidebar transition */
.sidebar {
    transition: width 0.3s ease;
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    overflow-y: auto;
    z-index: 40;
    width: 16rem; /* 64 in Tailwind units, matching the ml-64 */
}
</style>

<aside id="sidebar" class="sidebar bg-sidebar-background text-sidebar-foreground w-64 min-h-screen flex flex-col border-r border-sidebar-border fixed h-full z-30">
    <!-- Logo -->
    <div class="p-4 border-b border-sidebar-border flex items-center justify-between">
        <div class="flex items-center">
            <span class="ml-2 font-bold text-lg">Official Admin Portal</span>
        </div>
        <button id="sidebar-toggle-mobile" class="lg:hidden text-sidebar-foreground hover:text-white">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <!-- Sidebar Menu -->
    <nav class="flex-1 overflow-y-auto py-4">
        <ul class="space-y-1 px-3">
            <?php 
            // Determine the current page for active link styling
            $current_page = basename($_SERVER['PHP_SELF']);
            
            // User Management Link
            $is_user_page = ($current_page === 'users.php' || $current_page === 'add_user.php' || $current_page === 'edit_user.php');
            $user_class = $is_user_page ? 
                "flex items-center px-4 py-2.5 text-sm font-medium rounded-md bg-sidebar-accent text-sidebar-accent-foreground" : 
                "flex items-center px-4 py-2.5 text-sm font-medium rounded-md hover:bg-sidebar-accent hover:text-sidebar-accent-foreground";

            // Admin Management Link
            $is_admin_page = ($current_page === 'admins.php' || $current_page === 'add_admin.php' || $current_page === 'edit_admin.php');
            $admin_class = $is_admin_page ? 
                "flex items-center px-4 py-2.5 text-sm font-medium rounded-md bg-sidebar-accent text-sidebar-accent-foreground" : 
                "flex items-center px-4 py-2.5 text-sm font-medium rounded-md hover:bg-sidebar-accent hover:text-sidebar-accent-foreground";
                
            // Maintenance Mode Link
            $is_maintenance_page = ($current_page === 'maintenance.php');
            $maintenance_class = $is_maintenance_page ? 
                "flex items-center px-4 py-2.5 text-sm font-medium rounded-md bg-sidebar-accent text-sidebar-accent-foreground" : 
                "flex items-center px-4 py-2.5 text-sm font-medium rounded-md hover:bg-sidebar-accent hover:text-sidebar-accent-foreground";
            
            // API Settings Link
            $is_api_settings_page = ($current_page === 'api_settings.php' || $current_page === 'add_api_setting.php' || $current_page === 'edit_api_setting.php');
            $api_settings_class = $is_api_settings_page ? 
                "flex items-center px-4 py-2.5 text-sm font-medium rounded-md bg-sidebar-accent text-sidebar-accent-foreground" : 
                "flex items-center px-4 py-2.5 text-sm font-medium rounded-md hover:bg-sidebar-accent hover:text-sidebar-accent-foreground";
            
            // Site Settings Link
            $is_site_settings_page = ($current_page === 'site_settings.php' || $current_page === 'add_site_setting.php' || $current_page === 'edit_site_setting.php');
            $site_settings_class = $is_site_settings_page ? 
                "flex items-center px-4 py-2.5 text-sm font-medium rounded-md bg-sidebar-accent text-sidebar-accent-foreground" : 
                "flex items-center px-4 py-2.5 text-sm font-medium rounded-md hover:bg-sidebar-accent hover:text-sidebar-accent-foreground";
            ?>
            <li>
                <a href="admins.php" class="<?php echo $admin_class; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Admins
                </a>
            </li>
            <!-- <li>
                <a href="users.php" class="<?php echo $user_class; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Users
                </a>
            </li>
            <li>
                <a href="api_settings.php" class="<?php echo $api_settings_class; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7h3a5 5 0 015 5 5 5 0 01-5 5h-3m-6 0H6a5 5 0 01-5-5 5 5 0 015-5h3m1.5 6l1.5-6m0 0l1.5 6m-1.5-6h-1.5a1.5 1.5 0 00-1.5 1.5v3a1.5 1.5 0 001.5 1.5h1.5m-1.5-6l-1.5 6" />
                    </svg>
                    API Settings
                </a>
            </li> -->
            <li>
                <a href="site_settings.php" class="<?php echo $site_settings_class; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Site Settings
                </a>
            </li>
            <li>
                <a href="maintenance.php" class="<?php echo $maintenance_class; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    Maintenance Mode
                </a>
            </li>
        </ul>
    </nav>
</aside>

<script>
// Sidebar toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebarToggleMobile = document.getElementById('sidebar-toggle-mobile');
    const mobileSidebarToggle = document.getElementById('mobile-sidebar-toggle');
    const mainContent = document.querySelector('.flex-1.flex.flex-col');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('sidebar-open');
        });
    }
    
    if (sidebarToggleMobile) {
        sidebarToggleMobile.addEventListener('click', () => {
            sidebar.classList.toggle('sidebar-open');
        });
    }
    
    // Mobile sidebar toggle button
    if (mobileSidebarToggle) {
        mobileSidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('sidebar-open');
        });
    }
    
    // Add responsive behavior for sidebar
    function handleResize() {
        if (window.innerWidth >= 1024) { // lg breakpoint
            sidebar.classList.add('sidebar-open');
            if (mainContent) {
                mainContent.classList.add('ml-64');
            }
        } else {
            sidebar.classList.remove('sidebar-open');
            if (mainContent) {
                mainContent.classList.remove('ml-64');
            }
        }
    }
    
    // Initial call and event listener
    handleResize();
    window.addEventListener('resize', handleResize);
});
</script>