/* ─────────────────────────────────────────────────────────
   UniHub Mobile Sidebar — Drawer Pattern
   Handles open/close, overlay, body scroll lock, nav close
   ───────────────────────────────────────────────────────── */
(function () {
    'use strict';

    const sidebar  = document.getElementById('sidebar');
    const overlay  = document.getElementById('sidebar-overlay');
    const menuBtn  = document.getElementById('menu-toggle');
    const lockBtn  = document.getElementById('sidebar-lock-btn');
    const main     = document.querySelector('.main');

    /* ── Desktop sidebar lock ──────────────────────────── */
    const isLocked = localStorage.getItem('unihub-sidebar-locked') === 'true';
    if (isLocked && sidebar && window.innerWidth > 768) {
        sidebar.classList.add('sidebar-locked');
    }

    if (lockBtn && sidebar) {
        lockBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            const nowLocked = sidebar.classList.toggle('sidebar-locked');
            localStorage.setItem('unihub-sidebar-locked', nowLocked);
        });
    }

    /* ── Mobile drawer helpers ─────────────────────────── */
    function isMobile() {
        return window.innerWidth <= 768;
    }

    function openMobileSidebar() {
        if (!sidebar || !overlay) return;
        sidebar.classList.add('open');
        overlay.classList.add('show');
        document.body.classList.add('sidebar-open');
        document.body.style.overflow = 'hidden';
        sidebar.setAttribute('aria-hidden', 'false');
    }

    function closeMobileSidebar() {
        if (!sidebar || !overlay) return;
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
        document.body.classList.remove('sidebar-open');
        document.body.style.overflow = '';
        sidebar.setAttribute('aria-hidden', 'true');
    }

    function toggleMobileSidebar() {
        if (sidebar.classList.contains('open')) {
            closeMobileSidebar();
        } else {
            openMobileSidebar();
        }
    }

    /* ── Hamburger button ──────────────────────────────── */
    if (menuBtn) {
        menuBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (isMobile()) toggleMobileSidebar();
        });
    }

    /* ── Overlay click → close ─────────────────────────── */
    if (overlay) {
        overlay.addEventListener('click', closeMobileSidebar);
        overlay.addEventListener('touchend', function (e) {
            e.preventDefault();
            closeMobileSidebar();
        }, { passive: false });
    }

    /* ── Nav item click on mobile → close sidebar ──────── */
    if (sidebar) {
        sidebar.addEventListener('click', function (e) {
            if (!isMobile()) return;
            const navItem = e.target.closest('.nav-item, .logout-btn');
            if (navItem && !navItem.classList.contains('nav-has-submenu') && navItem.id !== 'admin-menu-btn') {
                closeMobileSidebar();
            }
        });
    }

    /* ── Escape key → close ────────────────────────────── */
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && isMobile()) {
            closeMobileSidebar();
        }
    });

    /* ── Swipe left on sidebar to close ───────────────── */
    if (sidebar) {
        let touchStartX = 0;
        let touchStartY = 0;

        sidebar.addEventListener('touchstart', function (e) {
            touchStartX = e.touches[0].clientX;
            touchStartY = e.touches[0].clientY;
        }, { passive: true });

        sidebar.addEventListener('touchend', function (e) {
            if (!isMobile()) return;
            const dx = e.changedTouches[0].clientX - touchStartX;
            const dy = Math.abs(e.changedTouches[0].clientY - touchStartY);
            // Swipe left at least 60px and more horizontal than vertical
            if (dx < -60 && dy < 80) {
                closeMobileSidebar();
            }
        }, { passive: true });
    }

    /* ── Swipe right anywhere to open sidebar ─────────── */
    let edgeStartX = 0;
    document.addEventListener('touchstart', function (e) {
        edgeStartX = e.touches[0].clientX;
    }, { passive: true });

    document.addEventListener('touchend', function (e) {
        if (!isMobile()) return;
        if (sidebar && sidebar.classList.contains('open')) return;
        const dx = e.changedTouches[0].clientX - edgeStartX;
        const dy = Math.abs(e.changedTouches[0].clientY - e.touches?.[0]?.clientY || 0);
        // Swipe right starting from left edge (0–30px)
        if (edgeStartX < 30 && dx > 60) {
            openMobileSidebar();
        }
    }, { passive: true });

    /* ── Resize: clean up mobile state on desktop ─────── */
    let resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            if (!isMobile() && sidebar) {
                closeMobileSidebar();
            }
        }, 150);
    });
})();
