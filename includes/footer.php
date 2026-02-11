<?php
// Check if this is a protected page that needs to close the main-content div
$public_pages = ['index.php', 'register.php', 'forgot_password.php', 'reset_password.php'];
$current_page = basename($_SERVER['PHP_SELF']);

if (isset($_SESSION['user_id']) && !in_array($current_page, $public_pages)):
?>
    </div> <!-- End of main-content -->
<?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    
    <!-- jQuery (for any additional functionality) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // Common JavaScript for all pages
        document.addEventListener('DOMContentLoaded', function() {
            // Enable Bootstrap tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Enable Bootstrap popovers
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });
            
            // Ensure dropdown toggle works
            var dropdownToggle = document.getElementById('dropdownUser');
            if (dropdownToggle) {
                dropdownToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                });
            }
            
            // Mobile sidebar toggle functionality if needed
            // This can be expanded for mobile responsiveness
            const toggleSidebarBtn = document.getElementById('toggleSidebar');
            if (toggleSidebarBtn) {
                toggleSidebarBtn.addEventListener('click', function() {
                    document.getElementById('sidebar').classList.toggle('d-none');
                });
            }
        });
    </script>
</body>
</html> 