(() => {
    function init() {
        const sidebar = document.getElementById('sidebar');
        const toggle = document.querySelector('.app-sidebar-toggle');
        if (!sidebar || !toggle) return;
        const mobile = window.matchMedia('(max-width: 767.98px)');
        let desktopCompact = false;
        try { desktopCompact = localStorage.getItem('expenzo.sidebar.compact') === '1'; } catch (_) {}
        const backdrop = document.createElement('button');
        backdrop.type = 'button';
        backdrop.className = 'sidebar-backdrop';
        backdrop.setAttribute('aria-label', 'Close navigation');
        document.body.append(backdrop);
        function setCompact(compact) {
            sidebar.classList.toggle('is-compact', compact);
            sidebar.inert = mobile.matches && compact;
            document.documentElement.classList.toggle('sidebar-compact', compact);
            toggle.setAttribute('aria-expanded', String(!compact));
            toggle.setAttribute('aria-label', compact ? 'Expand navigation' : 'Collapse navigation');
            backdrop.classList.toggle('is-visible', mobile.matches && !compact);
            sidebar.querySelectorAll('.sidebar-link').forEach(link => {
                link.title = compact ? link.getAttribute('aria-label') : '';
            });
            window.dispatchEvent(new Event('resize'));
        }
        toggle.addEventListener('click', () => {
            document.documentElement.classList.add('sidebar-animated');
            const compact = !sidebar.classList.contains('is-compact');
            if (!mobile.matches) {
                desktopCompact = compact;
                try { localStorage.setItem('expenzo.sidebar.compact', compact ? '1' : '0'); } catch (_) {}
            }
            setCompact(compact);
        });
        toggle.addEventListener('keydown', event => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                toggle.click();
            }
        });
        backdrop.addEventListener('click', () => { setCompact(true); toggle.focus(); });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && mobile.matches && !sidebar.classList.contains('is-compact')) {
                setCompact(true);
                toggle.focus();
            }
        });
        sidebar.addEventListener('transitionend', event => {
            if (event.target === sidebar) window.dispatchEvent(new Event('resize'));
        });
        mobile.addEventListener('change', () => {
            document.documentElement.classList.remove('sidebar-animated');
            setCompact(mobile.matches || desktopCompact);
        });
        setCompact(mobile.matches || desktopCompact);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();