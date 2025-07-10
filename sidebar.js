 document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector('.side-bar');
            const hamburgerMenu = document.querySelector('.hamburger-menu');
            const pageContainer = document.querySelector('.page-container');

            if (hamburgerMenu) {
                hamburgerMenu.addEventListener('click', function() {
                    sidebar.classList.toggle('open');
                    if (sidebar.classList.contains('open')) {
                        const overlay = document.createElement('div');
                        overlay.classList.add('sidebar-overlay');
                        pageContainer.appendChild(overlay);
                        overlay.addEventListener('click', function() {
                            sidebar.classList.remove('open');
                            overlay.remove();
                        });
                    } else {
                        document.querySelector('.sidebar-overlay')?.remove();
                    }
                });
            }

            // Close sidebar if screen resizes to desktop
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) { // Adjust breakpoint to match your CSS media query
                    sidebar.classList.remove('open');
                    document.querySelector('.sidebar-overlay')?.remove();
                }
            });
        });