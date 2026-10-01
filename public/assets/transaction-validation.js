(function () {
    function init() {
        const config = document.getElementById('transaction-validation-data');
        if (!config) return;
        const balances = JSON.parse(config.textContent);
        const owners = JSON.parse(document.getElementById('transaction-account-owners')?.textContent || '{}');
        const form = document.querySelector('input[name="submission_token"]').form;
        const names = ['type','transaction_date','account_id','destination_account_id','amount','direction','description'];
        const controls = Object.fromEntries(names.map(name => [name, form.elements[name]]));
        const {type, account_id: account, destination_account_id: destination, amount, direction} = controls;
        const targets = {};
        const touched = new Set();
        const server = {};
        const labels = new Map([...account.options, ...destination.options].map(option => [option, option.textContent]));
        for (const name of names) {
            const control = controls[name];
            let target = document.getElementById(name + '-error');
            server[name] = target?.textContent.trim() || '';
            if (!target) {
                target = document.createElement('div');
                target.id = name + '-error';
                target.className = 'invalid-feedback';
                control.closest('.mb-3').append(target);
            }
            target.setAttribute('aria-live', 'polite');
            control.setAttribute('aria-describedby', target.id);
            targets[name] = target;
        }
        function cents(value) {
            if (!/^-?\d{1,13}(?:\.\d{1,2})?$/.test(value)) return null;
            const negative = value.startsWith('-');
            const [whole, fraction = ''] = value.replace('-', '').split('.');
            return (Number(whole) * 100 + Number(fraction.padEnd(2, '0'))) * (negative ? -1 : 1);
        }
        const max = 999999999999999;
        function refresh(preserveServer = false) {
            const transfer = type.value === 'transfer';
            const adjustment = type.value === 'adjustment';
            const debit = transfer || (adjustment && direction.value === 'decrease');
            destination.disabled = !transfer;
            destination.required = transfer;
            destination.closest('.mb-3').hidden = !transfer;
            direction.disabled = !adjustment;
            direction.required = adjustment;
            direction.closest('.mb-3').hidden = !adjustment;
            const requested = cents(amount.value);
            const validAmount = requested !== null && requested > 0;
            for (const option of account.options) {
                if (!option.value) continue;
                const current = cents(String(balances[option.value]));
                const insufficient = debit && (validAmount ? requested > current : current <= 0);
                const overflow = !debit && validAmount && current + requested > max;
                option.disabled = insufficient || overflow;
                option.textContent = labels.get(option) + (insufficient ? ' — Insufficient balance' : overflow ? ' — Balance limit exceeded' : '');
            }
            for (const option of destination.options) {
                if (!option.value) continue;
                const same = transfer && option.value === account.value;
                const overflow = transfer && validAmount && cents(String(balances[option.value])) + requested > max;
                const otherOwner = transfer && account.value && owners[option.value] !== owners[account.value];
                option.disabled = same || overflow || Boolean(otherOwner);
                option.textContent = labels.get(option) + (same ? ' — Source account' : otherOwner ? ' — Different account owner' : overflow ? ' — Balance limit exceeded' : '');
            }
            if (transfer && destination.value && (destination.value === account.value || owners[destination.value] !== owners[account.value])) {
                destination.value = '';
                touched.add('destination_account_id');
            }
            const errors = {};
            for (const name of names) {
                const control = controls[name];
                if (control.disabled) continue;
                if ((touched.has(name) || submitted) && !control.value.trim() && control.required) errors[name] = 'This field is required.';
            }
            if (amount.validity.badInput || (amount.value !== '' && (!validAmount || requested > max))) errors.amount = 'Enter an amount greater than zero with at most 2 decimal places.';
            if (account.value && account.selectedOptions[0]?.disabled) errors.account_id = debit ? 'Insufficient balance. Reduce the amount or choose another account.' : 'This change would exceed the account balance limit.';
            if (transfer && destination.value && destination.selectedOptions[0]?.disabled) errors.destination_account_id = 'Choose a different account with sufficient balance capacity.';
            const date = controls.transaction_date;
            if (date.value && (date.validity.badInput || date.value < '1000-01-01' || date.value > date.max)) errors.transaction_date = 'Enter a valid date that is not in the future.';
            if (date.validity.badInput) errors.transaction_date = 'Enter a valid date.';
            if ([...controls.description.value.trim()].length > 255) errors.description = 'Use 255 characters or fewer.';
            for (const name of names) {
                const control = controls[name];
                const message = control.disabled ? '' : errors[name] || (preserveServer ? server[name] : '') || '';
                control.setCustomValidity(message);
                control.classList.toggle('is-invalid', Boolean(message));
                control.setAttribute('aria-invalid', String(Boolean(message)));
                targets[name].textContent = message;
                targets[name].classList.toggle('d-block', Boolean(message));
                if (control.tagName === 'SELECT') control.dispatchEvent(new Event('select-picker:refresh'));
            }
        }
        let submitted = false;
        form.noValidate = true;
        for (const name of names) {
            for (const event of ['input','change','blur']) controls[name].addEventListener(event, () => {
                touched.add(name);
                refresh();
            });
        }
        form.addEventListener('submit', event => {
            submitted = true;
            refresh();
            if (!form.checkValidity()) {
                event.preventDefault();
                form.reportValidity();
            }
        });
        form.addEventListener('reset', () => setTimeout(() => { touched.clear(); submitted = false; refresh(); }, 0));
        refresh(true);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
