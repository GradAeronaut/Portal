// Menu scroll behavior and page detection
(function() {
    'use strict';

    const menu = document.querySelector('.sinbad-menu');
    const menuItems = document.querySelectorAll('.menu-items li');
    
    let lastScrollTop = 0;
    let isStartPage = false;

    // Detect current page from URL (folder-based detection)
    function detectCurrentPage() {
        const path = window.location.pathname;
        
        // Extract folder name from path
        // Examples: /start/ -> start, /shape-sinbad/ -> shape-sinbad, /about/ -> about
        const pathParts = path.split('/').filter(part => part !== '');
        
        // If we're in a folder, get the first folder name
        if (pathParts.length > 0) {
            const folderName = pathParts[0];
            // Check if it's one of our pages
            if (['start', 'shape-sinbad', 'about'].includes(folderName)) {
                return folderName;
            }
        }
        
        // Default to start if we can't determine
        return 'start';
    }

    // Set active menu item and page mode
    function initializeMenu() {
        const currentPage = detectCurrentPage();
        
        // Set active menu item
        menuItems.forEach(item => {
            const itemPage = item.getAttribute('data-page');
            if (itemPage === currentPage) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });

        // Set menu mode based on page
        // Only 'start' page gets transparent menu with scroll behavior
        // Everything else (shape-sinbad, about, and their subpages) gets static menu
        if (currentPage === 'start') {
            menu.setAttribute('data-location', 'start');
            isStartPage = true;
            enableScrollBehavior();
        } else {
            menu.setAttribute('data-location', 'static');
            isStartPage = false;
            disableScrollBehavior();
        }
    }

    // Scroll behavior for start page
    function handleScroll() {
        // Disable scroll behavior on mobile
        if (window.innerWidth <= 768) return;
        if (!isStartPage) return;

        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

        // Scrolling down
        if (scrollTop > lastScrollTop && scrollTop > 100) {
            menu.classList.add('menu-hidden');
        } 
        // Scrolling up
        else if (scrollTop < lastScrollTop) {
            menu.classList.remove('menu-hidden');
        }

        lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
    }

    // Enable scroll hide/show behavior
    function enableScrollBehavior() {
        window.addEventListener('scroll', handleScroll);
    }

    // Disable scroll behavior
    function disableScrollBehavior() {
        window.removeEventListener('scroll', handleScroll);
        menu.classList.remove('menu-hidden');
    }

    // Add click handlers for menu items
    menuItems.forEach(item => {
        item.addEventListener('click', function() {
            const page = this.getAttribute('data-page');
            window.location.href = `/${page}/`;
        });
    });

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', initializeMenu);
    
    // In case the script loads after DOM is ready
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        initializeMenu();
    }

    // Mobile menu toggle
    const burger = document.querySelector('.burger-btn');
    const mobileMenu = document.querySelector('.mobile-menu');

    if (burger && mobileMenu) {
        burger.onclick = () => {
            mobileMenu.classList.toggle('open');
            burger.classList.toggle('active');
        };

        // Close mobile menu when clicking on a link
        const mobileLinks = mobileMenu.querySelectorAll('a');
        mobileLinks.forEach(link => {
            link.addEventListener('click', () => {
                mobileMenu.classList.remove('open');
                burger.classList.remove('active');
            });
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!mobileMenu.contains(e.target) && !burger.contains(e.target)) {
                mobileMenu.classList.remove('open');
                burger.classList.remove('active');
            }
        });
    }

})();

