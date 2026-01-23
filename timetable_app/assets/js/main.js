
console.log("Timetable Application Loaded");

// Basic interaction helper
document.addEventListener('DOMContentLoaded', function() {
    // Mobile nav toggle
    var navToggle = document.getElementById('navToggle');
    var navLinks = document.getElementById('navLinks');
    if (navToggle && navLinks) {
        navToggle.addEventListener('click', function() {
            var open = navLinks.style.display === 'block';
            navLinks.style.display = open ? 'none' : 'block';
        });

        // Close menu when resizing to desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                navLinks.style.display = '';
            }
        });
    }

    // Back button logic: show when there's a referrer or history length > 1 and not on home
    var backBtn = document.getElementById('backBtn');
    if (backBtn) {
        function shouldShowBack() {
            var path = window.location.pathname || '';
            var isHome = path === '/' || path.endsWith('/timetable_app') || path.endsWith('/timetable_app/') || path.endsWith('/index.php');
            if (isHome) return false;
            if (document.referrer && document.referrer !== '') return true;
            try { return window.history.length > 1; } catch (e) { return false; }
        }

        if (shouldShowBack()) {
            backBtn.style.display = 'inline-block';
        } else {
            backBtn.style.display = 'none';
        }

        backBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (document.referrer && document.referrer !== '') {
                window.history.back();
            } else if (window.history.length > 1) {
                window.history.back();
            } else {
                // fallback to home
                window.location.href = '/timetable_app/index.php';
            }
        });
    }
});
