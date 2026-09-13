(function () {
    function showToasts() {
        document.querySelectorAll('.app-toast').forEach(function (element) {
            if (window.bootstrap && window.bootstrap.Toast) {
                window.bootstrap.Toast.getOrCreateInstance(element, { autohide: true, delay: 5000 }).show();
            } else {
                element.classList.add('show');
                window.setTimeout(function () {
                    element.classList.remove('show');
                    element.remove();
                }, 5000);
            }
        });

        if (window.feather) {
            window.feather.replace();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', showToasts);
    } else {
        showToasts();
    }
})();
