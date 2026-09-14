(function () {
    let pendingForm = null;

    function formNeedsStatusConfirmation(form) {
        const fieldName = form.dataset.confirmStatusField;
        const targetValue = form.dataset.confirmStatusValue;
        if (!fieldName || !targetValue) return formLooksLikeInactiveEdit(form);
        const field = form.elements[fieldName];
        return field && String(field.value) === targetValue;
    }

    function formLooksLikeInactiveEdit(form) {
        const status = form.elements.status;
        if (!status || String(status.value) !== 'inactive') return false;
        return /\/(users|roles)\//.test(form.action) && /\/update(?:$|[?#])/.test(form.action);
    }

    function applyInactiveEditCopy(form) {
        if (!formLooksLikeInactiveEdit(form)) return;
        if (/\/users\//.test(form.action)) {
            form.dataset.confirmTitle = form.dataset.confirmTitle || 'Deactivate User';
            form.dataset.confirmSubtitle = form.dataset.confirmSubtitle || 'The user will lose access.';
            form.dataset.confirmMessage = form.dataset.confirmMessage || 'Deactivating this user prevents them from signing in and blocks their next request. Their existing records and audit history stay saved.';
            form.dataset.confirmButton = form.dataset.confirmButton || 'Deactivate User';
        } else if (/\/roles\//.test(form.action)) {
            form.dataset.confirmTitle = form.dataset.confirmTitle || 'Deactivate Role';
            form.dataset.confirmSubtitle = form.dataset.confirmSubtitle || 'Users assigned to this role will lose access.';
            form.dataset.confirmMessage = form.dataset.confirmMessage || 'Deactivating this role blocks assigned users on their next request until they are moved to an active role or this role is reactivated. Existing records and audit history stay saved.';
            form.dataset.confirmButton = form.dataset.confirmButton || 'Deactivate Role';
        }
    }

    function submitForm(form) {
        form.dataset.confirmed = 'true';
        if (typeof form.requestSubmit === 'function') form.requestSubmit();
        else form.submit();
    }

    function shouldConfirm(form) {
        if (form.dataset.confirmed === 'true') return false;
        applyInactiveEditCopy(form);
        if (form.dataset.confirmStatusField) return formNeedsStatusConfirmation(form);
        if (formLooksLikeInactiveEdit(form)) return true;
        return !!form.dataset.confirmTitle;
    }

    function showConfirmation(form) {
        const title = form.dataset.confirmTitle || 'Confirm action';
        const subtitle = form.dataset.confirmSubtitle || 'Please review the effect before continuing.';
        const message = form.dataset.confirmMessage || 'This action may affect existing records.';
        const button = form.dataset.confirmButton || 'Continue';
        const modal = document.getElementById('appConfirmModal');
        if (!modal) {
            if (window.confirm(title + '\n\n' + message)) submitForm(form);
            return;
        }

        pendingForm = form;
        modal.querySelector('#appConfirmModalTitle').textContent = title;
        modal.querySelector('#appConfirmModalSubtitle').textContent = subtitle;
        modal.querySelector('#appConfirmModalMessage').textContent = message;
        modal.querySelector('[data-confirm-button-label]').textContent = button;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('app-confirm-open');
        if (window.feather) window.feather.replace();
    }

    function closeModal() {
        const modal = document.getElementById('appConfirmModal');
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('app-confirm-open');
        pendingForm = null;
    }

    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !shouldConfirm(form)) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        showConfirmation(form);
    }, true);

    document.addEventListener('DOMContentLoaded', function () {
        const confirmButton = document.getElementById('appConfirmModalConfirm');
        const modal = document.getElementById('appConfirmModal');
        if (!confirmButton || !modal) return;
        document.querySelectorAll('form').forEach(function (form) {
            form.addEventListener('click', function (event) {
                const button = event.target.closest('button[type="submit"], input[type="submit"]');
                if (!button || !form.contains(button) || !shouldConfirm(form)) return;
                event.preventDefault();
                event.stopImmediatePropagation();
                showConfirmation(form);
            }, true);
        });
        confirmButton.addEventListener('click', function () {
            if (!pendingForm) return;
            const form = pendingForm;
            pendingForm = null;
            closeModal();
            submitForm(form);
        });
        modal.querySelectorAll('[data-confirm-cancel]').forEach(function (button) {
            button.addEventListener('click', closeModal);
        });
        modal.addEventListener('click', function (event) {
            if (event.target === modal) closeModal();
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
        });
    });
})();
