/**
 * ==========================================================================
 * NSS Tamil Nadu Government Polytechnic College Madurai-11
 * Dashboard JavaScript File
 * ==========================================================================
 */

document.addEventListener('DOMContentLoaded', () => {

    /* ==========================================================================
       1. Sidebar Toggle (Mobile)
       ========================================================================== */
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay'); // You need to add <div class="sidebar-overlay"></div> in HTML

    if (menuToggle && sidebar && overlay) {
        menuToggle.addEventListener('click', () => {
            sidebar.classList.add('show');
            overlay.classList.add('show');
        });

        overlay.addEventListener('click', () => {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    }

    /* Sidebar Toggle Button for Desktop/Tablet */
    const sidebarToggleBtn = document.getElementById('sidebarToggle');
    const mainSidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.dashboard-main');
    const footerElem = document.querySelector('.footer.dashboard-footer');

    if (sidebarToggleBtn && mainSidebar) {
        sidebarToggleBtn.addEventListener('click', () => {
            mainSidebar.classList.toggle('collapsed');
            if (mainContent) mainContent.classList.toggle('expanded');
            if (footerElem) footerElem.classList.toggle('expanded');
        });
    }

    /* ==========================================================================
       2. Initialize Icons
       ========================================================================== */
    if (window.lucide) {
        lucide.createIcons();
    }

    /* ==========================================================================
       3. Dashboard Stats Animation
       ========================================================================== */
    if (typeof anime !== 'undefined') {
        anime({
            targets: '.stat-card',
            translateY: [20, 0],
            opacity: [0, 1],
            delay: anime.stagger(100),
            easing: 'easeOutQuad',
            duration: 800
        });
    }

    /* ==========================================================================
       4. Chart.js Initializations
       ========================================================================== */
    
    // Global Chart Settings for Dark Theme
    if (typeof Chart !== 'undefined') {
        Chart.defaults.color = '#7aaa8a';
        Chart.defaults.borderColor = 'rgba(26, 107, 60, 0.2)';
        Chart.defaults.font.family = "'Inter', sans-serif";

        // A. Volunteer by Department (Bar Chart)
        const deptChartCtx = document.getElementById('deptChart');
        if (deptChartCtx) {
            new Chart(deptChartCtx, {
                type: 'bar',
                data: {
                    labels: ['Civil', 'Mech', 'EEE', 'Comp Eng', 'Robotics', 'Web Des'],
                    datasets: [{
                        label: 'Volunteers',
                        data: [45, 60, 35, 50, 20, 15],
                        backgroundColor: '#1a6b3c',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y', // Horizontal bar
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }

        // B. Events by Category (Doughnut Chart)
        const categoryChartCtx = document.getElementById('categoryChart');
        if (categoryChartCtx) {
            new Chart(categoryChartCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Camp', 'Awareness', 'Cleaning', 'Plantation'],
                    datasets: [{
                        data: [12, 19, 8, 15],
                        backgroundColor: [
                            '#f4a11d', // Accent Gold
                            '#1a6b3c', // Primary Green
                            '#2a8a4e', // Light Green
                            '#0d3d22'  // Dark Green
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }

        // C. Monthly Activity (Line Chart)
        const activityChartCtx = document.getElementById('activityChart');
        if (activityChartCtx) {
            new Chart(activityChartCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Hours Logged',
                        data: [120, 190, 150, 220, 180, 250],
                        borderColor: '#f4a11d',
                        backgroundColor: 'rgba(244, 161, 29, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }
    }

    /* ==========================================================================
       5. Data Table Live Search
       ========================================================================== */
    const searchInput = document.getElementById('tableSearch');
    const tableRows = document.querySelectorAll('.data-panel tbody tr');

    if (searchInput && tableRows.length > 0) {
        searchInput.addEventListener('keyup', function(e) {
            const term = e.target.value.toLowerCase();
            
            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(term)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    /* ==========================================================================
       6. Confirm Dialogs for Delete Actions
       ========================================================================== */
    const deleteBtns = document.querySelectorAll('.btn-delete');
    if (deleteBtns.length > 0) {
        deleteBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                if (!confirm("Are you sure you want to delete this record? This action cannot be undone.")) {
                    e.preventDefault();
                }
            });
        });
    }

    /* ==========================================================================
       7. Image Preview for File Inputs
       ========================================================================== */
    const imageInput = document.getElementById('imageUpload');
    const imagePreview = document.getElementById('imagePreview');

    if (imageInput && imagePreview) {
        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                imagePreview.src = '#';
                imagePreview.style.display = 'none';
            }
        });
    }

    /* ==========================================================================
       8. Notification Dropdown Toggle
       ========================================================================== */
    const bellIcon = document.querySelector('.notification-bell');
    // Assuming a dropdown div exists next to it
    const notifDropdown = document.querySelector('.notification-dropdown');

    if (bellIcon && notifDropdown) {
        bellIcon.addEventListener('click', (e) => {
            e.stopPropagation();
            notifDropdown.classList.toggle('show');
        });

        document.addEventListener('click', (e) => {
            if (!notifDropdown.contains(e.target)) {
                notifDropdown.classList.remove('show');
            }
        });
    }

    /* ==========================================================================
       9. Auto-Refresh Announcements (Simulation)
       ========================================================================== */
    // Example: poll every 60 seconds to fetch new notifications
    setInterval(() => {
        // In a real app, this would be an AJAX/fetch call.
        console.log("Checking for updates...");
        // fetch('/api/notifications')
        // .then(res => res.json())
        // .then(data => updateUI(data));
    }, 60000);

});
