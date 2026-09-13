(function () {
    function enhance(input) {
        if (!input || input.dataset.fileInputReady === '1') return;

        const wrapper = document.createElement('div');
        wrapper.className = 'file-control';
        input.before(wrapper);
        wrapper.append(input);

        input.classList.add('file-control-input');
        input.dataset.fileInputReady = '1';

        const visual = document.createElement('label');
        visual.className = 'file-control-display';
        if (input.id) visual.htmlFor = input.id;

        const button = document.createElement('span');
        button.className = 'file-control-button';
        button.innerHTML = '<i data-feather="upload" aria-hidden="true"></i><span>Choose file</span>';

        const name = document.createElement('span');
        name.className = 'file-control-name';
        name.textContent = 'No file chosen';

        visual.append(button, name);
        wrapper.append(visual);

        input.addEventListener('change', function () {
            name.textContent = input.files && input.files.length ? input.files[0].name : 'No file chosen';
        });
    }

    function init() {
        document.querySelectorAll('input[type="file"].form-control').forEach(enhance);
        if (window.feather) window.feather.replace();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
