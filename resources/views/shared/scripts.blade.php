<!-- Core JS -->
<!-- build:js assets/vendor/js/core.js -->
<script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/popper/popper.js') }}"></script>
<script src="{{ asset('assets/vendor/js/bootstrap.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>

<script src="{{ asset('assets/vendor/js/menu.js') }}"></script>
<!-- endbuild -->

<!-- Vendors JS -->
<script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Main JS -->
<script src="{{ asset('assets/js/main.js') }}"></script>

<!-- Template Customizer -->
<script src="{{ asset('assets/vendor/js/template-customizer.js') }}"></script>

<!-- Page JS -->
<script src="{{ asset('assets/js/dashboards-analytics.js') }}"></script>

<!-- Navbar Functionality -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const styleSwitcherToggle = document.querySelector('.style-switcher-toggle');
        if (styleSwitcherToggle) {
            styleSwitcherToggle.addEventListener('click', function(e) {
                e.preventDefault();
                const currentTheme = document.documentElement.getAttribute('data-bs-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-bs-theme', newTheme);

                const icon = this.querySelector('i');
                if (icon) {
                    icon.classList.toggle('bx-sun');
                    icon.classList.toggle('bx-moon');
                }

                updateLogoForSidebarState();
                localStorage.setItem('theme', newTheme);
            });
        }

        function updateLogo(theme, isCollapsed = false) {
            const logo = document.getElementById('sidebar-logo');
            if (!logo) {
                return;
            }

            let logoPath;
            if (isCollapsed) {
                logoPath = theme === 'dark'
                    ? '/assets/media/logos/idi-logos-collapsed-darkmode.png'
                    : '/assets/media/logos/idi-logos-collapsed-lightmode.png';
            } else {
                logoPath = theme === 'dark'
                    ? '/assets/media/logos/idi-logos-darkmode.png'
                    : '/assets/media/logos/idi-logos-lightmode.png';
            }

            const fullLogoPath = window.location.origin + logoPath;
            if (logo.src !== fullLogoPath && !logo.src.endsWith(logoPath)) {
                logo.src = logoPath;
            }

            logo.onerror = function() {
                console.error('Failed to load logo:', logoPath);
            };
        }

        function isSidebarCollapsed() {
            const layoutMenu = document.getElementById('layout-menu');
            if (!layoutMenu) return false;

            const computedStyle = window.getComputedStyle(layoutMenu);
            const width = parseInt(computedStyle.width);
            if (width < 100) {
                return true;
            }

            const body = document.body;
            const html = document.documentElement;
            const possibleCollapsedClasses = [
                'layout-menu-collapsed',
                'menu-collapsed',
                'collapsed'
            ];

            for (const element of [layoutMenu, body, html]) {
                if (element) {
                    for (const className of possibleCollapsedClasses) {
                        if (element.classList.contains(className)) {
                            return true;
                        }
                    }
                }
            }

            return false;
        }

        let isSidebarHovered = false;

        function updateLogoForSidebarState() {
            const theme = document.documentElement.getAttribute('data-bs-theme') || 'light';
            const collapsed = isSidebarCollapsed();
            const shouldUseCollapsedLogo = collapsed && !isSidebarHovered;
            updateLogo(theme, shouldUseCollapsedLogo);
        }

        function setupSidebarCollapseWatcher() {
            const layoutMenu = document.getElementById('layout-menu');
            const body = document.body;
            const html = document.documentElement;

            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        setTimeout(updateLogoForSidebarState, 50);
                    }
                });
            });

            [layoutMenu, body, html].forEach(element => {
                if (element) {
                    observer.observe(element, {
                        attributes: true,
                        attributeFilter: ['class']
                    });
                }
            });

            const menuToggle = document.querySelector('.layout-menu-toggle');
            if (menuToggle) {
                menuToggle.addEventListener('click', function() {
                    setTimeout(updateLogoForSidebarState, 200);
                });
            }

            let resizeTimeout;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(updateLogoForSidebarState, 100);
            });

            setupSidebarHoverWatcher();
        }

        function setupSidebarHoverWatcher() {
            const layoutMenu = document.getElementById('layout-menu');
            if (!layoutMenu) return;

            layoutMenu.addEventListener('mouseenter', function() {
                isSidebarHovered = true;
                if (isSidebarCollapsed()) {
                    updateLogoForSidebarState();
                }
            });

            layoutMenu.addEventListener('mouseleave', function() {
                isSidebarHovered = false;
                if (isSidebarCollapsed()) {
                    updateLogoForSidebarState();
                }
            });
        }

        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
            if (!localStorage.getItem('theme')) {
                const systemTheme = e.matches ? 'dark' : 'light';
                document.documentElement.setAttribute('data-bs-theme', systemTheme);
                updateLogoForSidebarState();
            }
        });

        const fullscreenToggle = document.querySelector('[title="Fullscreen"]');
        if (fullscreenToggle) {
            fullscreenToggle.addEventListener('click', function(e) {
                e.preventDefault();
                if (!document.fullscreenElement) {
                    document.documentElement.requestFullscreen();
                } else {
                    document.exitFullscreen();
                }
            });
        }

        const gridToggle = document.querySelector('[title="Grid View"]');
        if (gridToggle) {
            gridToggle.addEventListener('click', function(e) {
                e.preventDefault();
            });
        }

        const helpToggle = document.querySelector('[title="Help"]');
        if (helpToggle) {
            helpToggle.addEventListener('click', function(e) {
                e.preventDefault();
            });
        }

        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        const dropdownNotificationsAll = document.querySelector('.dropdown-notifications-all');
        const dropdownNotificationsRead = document.querySelectorAll('.dropdown-notifications-read');
        const dropdownNotificationsArchive = document.querySelectorAll('.dropdown-notifications-archive');

        if (dropdownNotificationsAll) {
            dropdownNotificationsAll.addEventListener('click', function(e) {
                e.preventDefault();
                dropdownNotificationsRead.forEach(function(item) {
                    item.closest('.dropdown-notifications-item').classList.add('marked-as-read');
                });
                const badge = document.querySelector('.badge-notifications');
                if (badge) {
                    badge.style.display = 'none';
                }
                const newBadge = document.querySelector('.badge.bg-label-primary');
                if (newBadge) {
                    newBadge.textContent = '0 New';
                }
            });
        }

        dropdownNotificationsRead.forEach(function(item) {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                this.closest('.dropdown-notifications-item').classList.toggle('marked-as-read');
            });
        });

        dropdownNotificationsArchive.forEach(function(item) {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                this.closest('.dropdown-notifications-item').remove();
                updateNotificationCount();
            });
        });

        function updateNotificationCount() {
            const unreadNotifications = document.querySelectorAll('.dropdown-notifications-item:not(.marked-as-read)');
            const badge = document.querySelector('.badge-notifications');
            const newBadge = document.querySelector('.badge.bg-label-primary');

            if (unreadNotifications.length === 0) {
                if (badge) badge.style.display = 'none';
                if (newBadge) newBadge.textContent = '0 New';
            } else {
                if (badge) badge.style.display = 'block';
                if (newBadge) newBadge.textContent = unreadNotifications.length + ' New';
            }
        }

        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
            const themeIcon = document.querySelector('.style-switcher-toggle i');
            if (themeIcon) {
                themeIcon.classList.toggle('bx-sun', savedTheme === 'light');
                themeIcon.classList.toggle('bx-moon', savedTheme === 'dark');
            }
            updateLogoForSidebarState();
        } else {
            const defaultTheme = 'light';
            document.documentElement.setAttribute('data-bs-theme', defaultTheme);
            updateLogoForSidebarState();
        }

        setupSidebarCollapseWatcher();

        setTimeout(function() {
            const logo = document.getElementById('sidebar-logo');
            if (logo && !logo.src.includes('idi-logos')) {
                updateLogoForSidebarState();
            }
        }, 100);
    });
</script>

<!-- Sidebar Functionality -->
<script>
    'use strict';

    class SidebarActiveManager {
        constructor() {
            this.INIT_DELAY = 200;
            this.NAVIGATION_DELAY = 100;
            this.init();
        }

        init() {
            setTimeout(() => {
                this.setupEventHandlers();
                this.setActiveMenuItemFromUrl();
            }, this.INIT_DELAY);
        }

        setupEventHandlers() {
            this.setupNavigationHandlers();
            this.setupBrowserNavigationHandlers();
        }

        setupNavigationHandlers() {
            const navigationLinks = this.getNavigationLinks();
            navigationLinks.forEach(link => {
                link.addEventListener('click', (event) => {
                    setTimeout(() => {
                        this.setActiveMenuItem(event.target.closest('.menu-item'));
                    }, this.NAVIGATION_DELAY);
                });
            });
        }

        setupBrowserNavigationHandlers() {
            window.addEventListener('popstate', () => {
                if (window.sidebarActiveManager) {
                    setTimeout(() => {
                        this.setActiveMenuItemFromUrl();
                    }, this.NAVIGATION_DELAY);
                }
            });
        }

        getNavigationLinks() {
            return document.querySelectorAll(
                '.menu-link[href]:not([href="javascript:void(0);"]):not([href="javascript:void(0)"])'
            );
        }

        setActiveMenuItem(menuItem) {
            if (!menuItem) return;

            this.clearAllActiveStates();
            this.activateMenuItem(menuItem);
            this.openParentSubmenus(menuItem);
        }

        clearAllActiveStates() {
            document.querySelectorAll('.menu-item').forEach(item => {
                item.classList.remove('active');
            });
        }

        activateMenuItem(menuItem) {
            menuItem.classList.add('active');
            let parent = this.getParentMenuItem(menuItem);
            while (parent) {
                parent.classList.add('active');
                parent = this.getParentMenuItem(parent);
            }
        }

        openParentSubmenus(menuItem) {
            let parent = this.getParentMenuItem(menuItem);
            while (parent) {
                this.openSubmenuIfExists(parent);
                parent = this.getParentMenuItem(parent);
            }
        }

        openSubmenuIfExists(menuItem) {
            const submenu = menuItem.querySelector('.menu-sub');
            if (submenu) {
                menuItem.classList.add('open');
                submenu.style.display = 'block';
            }
        }

        getParentMenuItem(menuItem) {
            return menuItem.parentElement.closest('.menu-item');
        }

        setActiveMenuItemFromUrl() {
            const currentPath = window.location.pathname;
            const activeMenuItem = this.findMenuItemByUrl(currentPath);

            if (activeMenuItem) {
                this.setActiveMenuItem(activeMenuItem);
            }
        }

        findMenuItemByUrl(url) {
            const menuLinks = this.getNavigationLinks();
            let bestMatch = null;

            menuLinks.forEach(link => {
                const href = link.getAttribute('href');
                if (this.isExactMatch(href, url)) {
                    bestMatch = link.closest('.menu-item');
                } else if (this.isPartialMatch(href, url, bestMatch)) {
                    bestMatch = link.closest('.menu-item');
                }
            });

            return bestMatch;
        }

        isExactMatch(href, url) {
            return href === url;
        }

        isPartialMatch(href, url, currentBest) {
            if (!url.startsWith(href) || href === '/') {
                return false;
            }

            if (!currentBest) {
                return true;
            }

            const currentBestHref = currentBest.querySelector('.menu-link[href]')?.getAttribute('href') || '';
            return href.length > currentBestHref.length;
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        window.sidebarActiveManager = new SidebarActiveManager();
        window.sidebarSearchManager = new SidebarSearchManager();
        window.sidebarSearchVisibilityManager = new SidebarSearchVisibilityManager();
    });

    class SidebarSearchVisibilityManager {
        constructor() {
            this.searchContainer = document.querySelector('.sidebar-search-container');
            this.layoutMenu = document.getElementById('layout-menu');
            this.init();
        }

        init() {
            if (!this.searchContainer || !this.layoutMenu) return;
            this.updateSearchVisibility();
            this.setupCollapseWatcher();
        }

        setupCollapseWatcher() {
            const observer = new MutationObserver(() => {
                this.updateSearchVisibility();
            });

            if (this.layoutMenu) {
                observer.observe(this.layoutMenu, {
                    attributes: true,
                    attributeFilter: ['class']
                });
            }

            observer.observe(document.body, {
                attributes: true,
                attributeFilter: ['class']
            });

            const menuToggle = document.querySelector('.layout-menu-toggle');
            if (menuToggle) {
                menuToggle.addEventListener('click', () => {
                    setTimeout(() => {
                        this.updateSearchVisibility();
                    }, 100);
                });
            }

            window.addEventListener('resize', () => {
                setTimeout(() => {
                    this.updateSearchVisibility();
                }, 100);
            });
        }

        isSidebarCollapsed() {
            if (!this.layoutMenu) return false;

            const computedStyle = window.getComputedStyle(this.layoutMenu);
            const width = parseInt(computedStyle.width);
            if (width < 100) {
                return true;
            }

            const hasCollapsedClass =
                this.layoutMenu.classList.contains('layout-menu-collapsed') ||
                document.body.classList.contains('layout-menu-collapsed') ||
                document.documentElement.classList.contains('layout-menu-collapsed');

            return hasCollapsedClass;
        }

        updateSearchVisibility() {
            if (!this.searchContainer) return;
            const isCollapsed = this.isSidebarCollapsed();
            if (isCollapsed) {
                this.searchContainer.classList.add('force-hide');
            } else {
                this.searchContainer.classList.remove('force-hide');
            }
        }
    }

    class SidebarSearchManager {
        constructor() {
            this.searchInput = document.getElementById('sidebarSearchInput');
            this.clearButton = document.getElementById('sidebarSearchClear');
            this.menuInner = document.getElementById('menuInner');
            this.allMenuItems = [];
            this.init();
        }

        init() {
            if (!this.searchInput || !this.menuInner) return;
            this.collectMenuItems();
            this.setupEventListeners();
        }

        collectMenuItems() {
            this.allMenuItems = Array.from(this.menuInner.querySelectorAll('.menu-item'));
        }

        setupEventListeners() {
            this.searchInput.addEventListener('input', (e) => {
                this.handleSearch(e.target.value.trim());
            });

            if (this.clearButton) {
                this.clearButton.addEventListener('click', () => {
                    this.clearSearch();
                });
            }

            this.searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.clearSearch();
                }
            });
        }

        handleSearch(searchTerm) {
            const term = searchTerm.toLowerCase();
            const hasSearch = term.length > 0;

            if (this.clearButton) {
                if (hasSearch) {
                    this.clearButton.classList.remove('d-none');
                } else {
                    this.clearButton.classList.add('d-none');
                }
            }

            if (!hasSearch) {
                this.showAllMenuItems();
                this.closeAllSubmenus();
                return;
            }

            this.allMenuItems.forEach(menuItem => {
                const searchableText = this.getSearchableText(menuItem);
                const matches = searchableText.toLowerCase().includes(term);

                if (matches) {
                    this.showMenuItem(menuItem);
                    this.openParentSubmenus(menuItem);
                } else {
                    const hasMatchingChild = this.hasMatchingChild(menuItem, term);
                    if (hasMatchingChild) {
                        this.showMenuItem(menuItem);
                        this.openParentSubmenus(menuItem);
                    } else {
                        this.hideMenuItem(menuItem);
                    }
                }
            });
        }

        getSearchableText(menuItem) {
            const menuLink = menuItem.querySelector('.menu-link');
            if (!menuLink) return '';

            const texts = [];
            const i18nElement = menuLink.querySelector('[data-i18n]');
            if (i18nElement) {
                const i18nValue = i18nElement.getAttribute('data-i18n');
                if (i18nValue) {
                    texts.push(i18nValue);
                }
                const textContent = i18nElement.textContent.trim();
                if (textContent) {
                    texts.push(textContent);
                }
            }

            const textDivs = menuLink.querySelectorAll('div:not(.menu-sub)');
            textDivs.forEach(div => {
                const text = div.textContent.trim();
                if (text && !texts.includes(text)) {
                    texts.push(text);
                }
            });

            const allText = menuLink.textContent.trim();
            if (allText) {
                const submenu = menuItem.querySelector('.menu-sub');
                if (submenu) {
                    const submenuText = submenu.textContent.trim();
                    const cleanText = allText.replace(submenuText, '').trim();
                    if (cleanText && !texts.includes(cleanText)) {
                        texts.push(cleanText);
                    }
                } else if (!texts.includes(allText)) {
                    texts.push(allText);
                }
            }

            const href = menuLink.getAttribute('href');
            if (href && href !== 'javascript:void(0);' && href !== 'javascript:void(0)') {
                const urlParts = href.split('/').filter(part => part.length > 0);
                urlParts.forEach(part => {
                    if (part && !texts.includes(part)) {
                        texts.push(part);
                    }
                });
            }

            return texts.join(' ');
        }

        hasMatchingChild(menuItem, searchTerm) {
            const submenu = menuItem.querySelector('.menu-sub');
            if (!submenu) return false;

            const childItems = submenu.querySelectorAll('.menu-item');
            for (const childItem of childItems) {
                const childText = this.getSearchableText(childItem);
                if (childText.toLowerCase().includes(searchTerm)) {
                    return true;
                }

                if (this.hasMatchingChild(childItem, searchTerm)) {
                    return true;
                }
            }

            return false;
        }

        showMenuItem(menuItem) {
            menuItem.style.display = '';
            menuItem.classList.remove('menu-item-hidden');
        }

        hideMenuItem(menuItem) {
            menuItem.style.display = 'none';
            menuItem.classList.add('menu-item-hidden');
        }

        showAllMenuItems() {
            this.allMenuItems.forEach(menuItem => {
                this.showMenuItem(menuItem);
            });
        }

        closeAllSubmenus() {
            this.allMenuItems.forEach(menuItem => {
                const submenu = menuItem.querySelector('.menu-sub');
                if (submenu && !menuItem.classList.contains('active')) {
                    const isActive = menuItem.classList.contains('active') ||
                                   menuItem.querySelector('.menu-item.active') !== null;
                    if (!isActive) {
                        menuItem.classList.remove('open');
                        submenu.style.display = '';
                    }
                }
            });
        }

        openParentSubmenus(menuItem) {
            let parent = this.getParentMenuItem(menuItem);
            while (parent) {
                parent.classList.add('open');
                const submenu = parent.querySelector('.menu-sub');
                if (submenu) {
                    submenu.style.display = 'block';
                }
                parent = this.getParentMenuItem(parent);
            }
        }

        getParentMenuItem(menuItem) {
            return menuItem.parentElement.closest('.menu-item');
        }

        clearSearch() {
            if (this.searchInput) {
                this.searchInput.value = '';
                this.handleSearch('');
                this.searchInput.focus();
            }
        }
    }
</script>

<script src="{{ asset('js/apiHelper.js') }}"></script>
<script src="{{ asset('js/DataTableHelper.js') }}"></script>
