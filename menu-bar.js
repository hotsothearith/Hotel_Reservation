document.addEventListener('DOMContentLoaded', function() {
    const phoneMenuToggle = document.getElementById('phoneMenuToggle');
    const dropdownMenu = document.getElementById('dropdownMenu');

    if (phoneMenuToggle && dropdownMenu) {
        // Toggle menu visibility on click
        phoneMenuToggle.addEventListener('click', function(event) {
            event.stopPropagation(); // Prevent click from immediately propagating to document
            dropdownMenu.classList.toggle('show');
        });

        // Close menu when clicking outside of it
        document.addEventListener('click', function(event) {
            // Check if the click occurred outside the phone-menu container
            if (!phoneMenuToggle.contains(event.target) && !dropdownMenu.contains(event.target) && dropdownMenu.classList.contains('show')) {
                dropdownMenu.classList.remove('show');
            }
        });

        // Close menu when a link inside it is clicked (optional, but good for UX)
        dropdownMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function() {
                dropdownMenu.classList.remove('show');
            });
        });
    }
});