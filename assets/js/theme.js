(function() {
    let savedTheme = 'dark';
    try {
        savedTheme = localStorage.getItem('unihub-theme') || 'dark';
    } catch (e) {
        console.warn('localStorage access failed:', e);
    }
    
    // Apply theme immediately to html element to prevent FOUC (flash of unstyled content)
    document.documentElement.setAttribute('data-theme', savedTheme);

    function applyActiveThemeState(theme) {
        const btns = document.querySelectorAll('.theme-btn');
        btns.forEach(b => {
            if (b.dataset.theme === theme) {
                b.classList.add('active');
            } else {
                b.classList.remove('active');
            }
        });
    }

    // Set active state when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            applyActiveThemeState(savedTheme);
        });
    } else {
        applyActiveThemeState(savedTheme);
    }

    // Add click event delegation globally on document immediately
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.theme-btn');
        if (!btn) return;
        
        const theme = btn.dataset.theme;
        if (!theme) return;

        try {
            localStorage.setItem('unihub-theme', theme);
        } catch (err) {
            console.warn('localStorage set failed:', err);
        }

        document.documentElement.setAttribute('data-theme', theme);
        applyActiveThemeState(theme);
    });
})();
