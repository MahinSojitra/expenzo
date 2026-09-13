document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-permissions-form]').forEach(form => {
        form.addEventListener('click', event => {
            const button = event.target.closest('[data-permission-action]');
            if (!button || !form.contains(button)) return;
            const action = button.dataset.permissionAction;
            const scope = action.startsWith('group-') ? button.closest('[data-permission-group]') : form;
            scope.querySelectorAll('input[name="permissions[]"]:not(:disabled)').forEach(input => {
                input.checked = action.endsWith('all');
                input.dispatchEvent(new Event('change', {bubbles: true}));
            });
        });
    });
});