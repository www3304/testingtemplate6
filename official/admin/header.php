<style>
/* Custom scrollbar - Shared with sidebar */
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

/* Dropdown animation */
.dropdown-content {
    transform-origin: top right;
    transition: transform 0.2s, opacity 0.2s;
    transform: scale(0.95);
    opacity: 0;
}
.dropdown-content.active {
    transform: scale(1);
    opacity: 1;
}
</style>

<header class="bg-white border-b border-gray-200 sticky top-0 z-20">
    <div class="flex items-center justify-between px-4 py-3">
        <!-- Left side: Toggle button and breadcrumb -->
        <div class="flex items-center">
            <button id="sidebar-toggle" class="text-gray-500 hover:text-gray-700 lg:hidden">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        <!-- Right side: User dropdown -->
        <div class="flex items-center space-x-4">
            <div class="relative">
                <button id="user-menu-button" class="flex items-center space-x-3 focus:outline-none">
                    <div class="h-9 w-9 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 font-medium border border-primary-200">
                        <?php echo isset($_SESSION['username']) ? substr($_SESSION['username'], 0, 2) : 'SA'; ?>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <!-- Dropdown menu -->
                <div id="user-dropdown" class="dropdown-content absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity-5 focus:outline-none hidden">
                    <div class="px-4 py-2 border-b border-gray-100">
                        <p class="text-sm font-medium text-gray-900"><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin'; ?></p>
                    </div>
                    <!-- <a href="edit_password.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        Update Password
                    </a> -->
                    <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        Log Out
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
// User dropdown functionality
document.addEventListener('DOMContentLoaded', function() {
    const userMenuButton = document.getElementById('user-menu-button');
    const userDropdown = document.getElementById('user-dropdown');
    
    if (userMenuButton && userDropdown) {
        userMenuButton.addEventListener('click', () => {
            userDropdown.classList.toggle('hidden');
            userDropdown.classList.toggle('active');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', (event) => {
            if (!userMenuButton.contains(event.target) && !userDropdown.contains(event.target)) {
                userDropdown.classList.add('hidden');
                userDropdown.classList.remove('active');
            }
        });
    }
});
</script>