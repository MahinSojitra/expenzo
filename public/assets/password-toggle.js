(function () {
    function enhancePassword(input) {
        if (!input || input.dataset.passwordToggleReady === '1') return;

        const wrapper = document.createElement('div');
        wrapper.className = 'input-group password-toggle';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);

        input.dataset.passwordToggleReady = '1';

        const button = document.createElement('button');
        button.type = 'button';
        button.className = input.classList.contains('form-control-lg')
            ? 'btn btn-outline-secondary btn-lg password-toggle-btn'
            : 'btn btn-outline-secondary password-toggle-btn';
        button.setAttribute('aria-label', 'Show password');
        button.setAttribute('aria-pressed', 'false');
        button.textContent = 'Show';

        button.addEventListener('click', function () {
            const visible = input.type === 'text';
            input.type = visible ? 'password' : 'text';
            button.textContent = visible ? 'Show' : 'Hide';
            button.setAttribute('aria-label', visible ? 'Show password' : 'Hide password');
            button.setAttribute('aria-pressed', visible ? 'false' : 'true');
            input.focus();
        });

        wrapper.appendChild(button);
    }

    function initPasswordToggles() {
        document.querySelectorAll('input[type="password"][data-password-toggle]').forEach(enhancePassword);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPasswordToggles);
    } else {
        initPasswordToggles();
    }
})();
