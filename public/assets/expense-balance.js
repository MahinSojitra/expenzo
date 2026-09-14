(function () {
    function init() {
        const form = document.querySelector('[data-expense-form]');
        if (!form) return;
        const amount = form.elements.amount;
        const account = form.elements.account_id;
        const status = form.elements.status;
        const accountError = document.getElementById('account_id-error');
        const amountError = document.getElementById('amount-error');
        const help = document.getElementById('account-balance-help');
        const serverError = accountError.textContent.trim();
        const originalAccount = form.dataset.originalAccount;
        const originalAmount = cents(form.dataset.originalAmount) || 0;
        const originalPosted = form.dataset.originalStatus === 'posted';
        const labels = new Map([...account.options].map(option => [option, option.textContent]));
        const format = new Intl.NumberFormat(undefined, {style: 'currency', currency: form.dataset.currency});
        function cents(value) {
            if (!/^\d{1,13}(?:\.\d{1,2})?$/.test(value)) return null;
            const [whole, fraction = ''] = value.split('.');
            return Number(whole) * 100 + Number(fraction.padEnd(2, '0'));
        }
        function storedCents(value) {
            return value.startsWith('-') ? -cents(value.slice(1)) : cents(value);
        }
        function error(control, target, message) {
            control.setCustomValidity(message);
            control.classList.toggle('is-invalid', Boolean(message));
            control.setAttribute('aria-invalid', String(Boolean(message)));
            target.textContent = message;
            target.classList.toggle('d-block', Boolean(message));
        }
        function refresh(preserveServerError = false) {
            const requested = cents(amount.value);
            const posted = status.value === 'posted';
            const badAmount = amount.value !== '' && (requested === null || requested <= 0);
            error(amount, amountError, badAmount ? 'Enter an amount greater than zero with at most 2 decimal places.' : '');
            for (const option of account.options) {
                if (!option.value) continue;
                const current = storedCents(option.dataset.balance);
                const credited = originalPosted && option.value === originalAccount;
                const available = current + (credited ? originalAmount : 0);
                // Reducing an existing expense can repair a legacy negative balance.
                const improvesExisting = credited && requested !== null && requested <= originalAmount;
                option.disabled = posted && !improvesExisting && (requested > 0 ? requested > available : available <= 0);
                option.textContent = labels.get(option) + (option.disabled ? ' • Insufficient balance' : '');
            }
            const selected = account.selectedOptions[0];
            let message = '';
            if (selected?.value) {
                const current = storedCents(selected.dataset.balance);
                const credited = originalPosted && selected.value === originalAccount;
                const available = current + (credited ? originalAmount : 0);
                if (selected.disabled) {
                    message = 'Insufficient balance. Available for this expense: ' + format.format(Math.max(0, available) / 100) + '. Reduce the amount or choose another account.';
                }
                help.textContent = posted
                    ? 'Available for this expense: ' + format.format(Math.max(0, available) / 100) + (credited ? ' (includes the original expense amount).' : '.')
                    : 'This status does not deduct funds. Available balance is checked when the expense is posted.';
            } else {
                const any = [...account.options].some(option => option.value && !option.disabled);
                help.textContent = !posted ? 'This status does not deduct funds. Balance is checked when posted.'
                    : !any ? 'No account has enough balance. Reduce the amount or save as a draft.'
                    : 'Accounts with insufficient balance are unavailable for this amount.';
            }
            error(account, accountError, message || (preserveServerError ? serverError : ''));
            account.dispatchEvent(new Event('select-picker:refresh'));
        }
        amount.addEventListener('input', () => refresh());
        status.addEventListener('change', () => refresh());
        account.addEventListener('change', () => refresh());
        form.addEventListener('reset', () => setTimeout(() => refresh(), 0));
        form.addEventListener('submit', event => {
            refresh();
            if (!form.checkValidity()) {
                event.preventDefault();
                form.reportValidity();
            }
        });
        refresh(true);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
