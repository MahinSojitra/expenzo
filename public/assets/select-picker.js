(function () {
    const featherIcons = () => window.feather?.icons || {};
    const prettify = value => (value || '').replace(/[-_]/g, ' ').replace(/\b\w/g, letter => letter.toUpperCase());
    const accountTypeIcons = {
        cash: 'dollar-sign',
        bank: 'briefcase',
        card: 'credit-card',
        'credit card': 'credit-card',
        'debit card': 'credit-card',
        upi: 'smartphone',
        wallet: 'pocket'
    };

    function accountIcon(value) {
        return accountTypeIcons[String(value || '').trim().toLowerCase()] || 'credit-card';
    }

    function iconFor(select, option) {
        const explicit = option.dataset.icon;
        if (explicit) return explicit;

        const name = (select.name || select.id || '').toLowerCase();
        const text = option.textContent.trim().toLowerCase();
        const value = String(option.value || '').toLowerCase();
        if (name === 'currency') return 'currency-' + value.toUpperCase();
        const haystack = `${name} ${text} ${value}`;

        if (option.dataset.accountType) return accountIcon(option.dataset.accountType);
        if (name === 'type' && option.closest('form')?.action?.toLowerCase().includes('accounts')) return accountIcon(text || value);
        if (text.startsWith('all users')) return 'users';
        if (text.startsWith('all categories')) return 'layers';
        if (text.startsWith('all accounts')) return 'briefcase';
        if (text.startsWith('all statuses')) return 'list';
        if (haystack.includes('role')) return 'shield';
        if (haystack.includes('category')) return option.value ? 'tag' : 'layers';
        if (haystack.includes('account')) return option.value ? 'credit-card' : 'briefcase';
        if (haystack.includes('owner') || haystack.includes('user')) return option.value ? 'user' : 'users';
        if (haystack.includes('status') || ['active', 'inactive', 'posted', 'draft'].includes(value)) {
            if (value === 'active' || value === 'posted') return 'check-circle';
            if (value === 'inactive' || value === 'draft') return 'pause-circle';
            return 'list';
        }
        if (haystack.includes('scope') || ['global', 'personal'].includes(value)) {
            if (value === 'global') return 'globe';
            if (value === 'personal') return 'user';
            return 'layers';
        }

        return option.value ? 'chevron-right' : 'list';
    }

    function optionLabel(option) {
        return option.textContent.trim() || prettify(option.value) || 'Choose option';
    }

    function renderIcon(container, icon) {
        const icons = featherIcons();
        container.replaceChildren();
        const currencySymbols = {'currency-INR':'\u20B9', 'currency-USD':'$', 'currency-EUR':'\u20AC', 'currency-GBP':'\u00A3'};
        if (currencySymbols[icon]) {
            const symbol = document.createElement('span');
            symbol.className = 'select-picker-currency';
            symbol.setAttribute('aria-hidden', 'true');
            symbol.textContent = currencySymbols[icon];
            container.append(symbol);
            return;
        }
        if (icons[icon]) {
            container.innerHTML = icons[icon].toSvg({'aria-hidden': 'true', width: 18, height: 18});
        }
    }

    function enhanceSelect(select) {
        if (!select || select.dataset.selectPickerReady === '1' || select.multiple || select.dataset.iconPicker !== undefined) return;

        const originalId = select.id || `${select.name || 'select'}-${Math.random().toString(36).slice(2)}`;
        if (!select.id) select.id = originalId;

        const wrapper = document.createElement('div');
        wrapper.className = 'select-picker';
        select.before(wrapper);
        wrapper.append(select);

        select.id = `${originalId}-value`;
        select.hidden = true;
        select.dataset.selectPickerReady = '1';

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.id = originalId;
        trigger.className = `form-select select-picker-trigger${select.classList.contains('is-invalid') ? ' is-invalid' : ''}`;
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        if (select.required) trigger.setAttribute('aria-required', 'true');
        if (select.hasAttribute('aria-describedby')) trigger.setAttribute('aria-describedby', select.getAttribute('aria-describedby'));
        if (select.hasAttribute('aria-invalid')) trigger.setAttribute('aria-invalid', 'true');

        const panel = document.createElement('div');
        panel.className = 'select-picker-panel';
        panel.hidden = true;

        const search = document.createElement('input');
        search.type = 'search';
        search.className = 'form-control select-picker-search';
        search.placeholder = 'Search options...';
        search.autocomplete = 'off';
        search.setAttribute('aria-label', 'Search options');
        search.setAttribute('role', 'combobox');
        search.setAttribute('aria-autocomplete', 'list');
        search.setAttribute('aria-expanded', 'false');

        const list = document.createElement('div');
        list.id = `${originalId}-options`;
        list.className = 'select-picker-options';
        list.setAttribute('role', 'listbox');
        list.setAttribute('aria-label', 'Options');
        trigger.setAttribute('aria-controls', list.id);
        search.setAttribute('aria-controls', list.id);

        const empty = document.createElement('p');
        empty.className = 'select-picker-empty text-muted';
        empty.textContent = 'No options found.';
        empty.setAttribute('role', 'status');

        panel.append(search, list, empty);
        wrapper.append(trigger, panel);

        let active = -1;
        let results = [];

        function options() {
            return [...select.options].filter(option => !option.disabled);
        }

        function preview(container, option) {
            container.replaceChildren();
            const icon = document.createElement('span');
            icon.className = 'select-picker-picture';
            renderIcon(icon, iconFor(select, option));
            const text = document.createElement('span');
            text.textContent = optionLabel(option);
            container.append(icon, text);
        }

        function selectedOption() {
            return select.selectedOptions[0] || select.options[0] || new Option('Choose option', '');
        }

        function highlight(index) {
            active = index;
            [...list.children].forEach((option, i) => option.classList.toggle('is-focused', i === index));
            const option = list.children[index];
            if (option) {
                search.setAttribute('aria-activedescendant', option.id);
                option.scrollIntoView({block: 'nearest'});
            } else {
                search.removeAttribute('aria-activedescendant');
            }
        }

        function render() {
            const query = search.value.trim().toLowerCase();
            results = options().filter(option => optionLabel(option).toLowerCase().includes(query));
            list.replaceChildren();
            results.forEach((option, index) => {
                const item = document.createElement('div');
                item.id = `${originalId}-option-${index}`;
                item.className = 'select-picker-option';
                item.setAttribute('role', 'option');
                item.setAttribute('aria-selected', String(option.value === select.value));
                preview(item, option);
                item.addEventListener('mousedown', event => event.preventDefault());
                item.addEventListener('click', () => choose(option));
                list.append(item);
            });
            empty.hidden = results.length > 0;
            highlight(results.length ? Math.max(0, results.findIndex(option => option.value === select.value)) : -1);
        }

        function close(restoreFocus = false) {
            panel.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
            search.setAttribute('aria-expanded', 'false');
            search.removeAttribute('aria-activedescendant');
            if (restoreFocus) trigger.focus();
        }

        function open() {
            panel.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');
            search.setAttribute('aria-expanded', 'true');
            search.value = '';
            render();
            search.focus({preventScroll: true});
        }

        function choose(option) {
            select.value = option.value;
            preview(trigger, option);
            select.dispatchEvent(new Event('change', {bubbles: true}));
            close(true);
        }

        trigger.addEventListener('click', () => panel.hidden ? open() : close());
        trigger.addEventListener('keydown', event => {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                open();
            }
        });
        search.addEventListener('input', render);
        search.addEventListener('keydown', event => {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                if (results.length) highlight((active + (event.key === 'ArrowDown' ? 1 : -1) + results.length) % results.length);
            } else if (event.key === 'Enter') {
                event.preventDefault();
                if (results[active]) choose(results[active]);
            } else if (event.key === 'Escape') {
                event.preventDefault();
                close(true);
            } else if (event.key === 'Tab') {
                close();
            }
        });
        document.addEventListener('click', event => {
            if (!wrapper.contains(event.target)) close();
        });
        wrapper.addEventListener('focusout', event => {
            if (!wrapper.contains(event.relatedTarget)) close();
        });
        select.addEventListener('change', () => preview(trigger, selectedOption()));
        select.form?.addEventListener('reset', () => setTimeout(() => { preview(trigger, selectedOption()); close(); }, 0));
        preview(trigger, selectedOption());
    }

    function init() {
        document.querySelectorAll('select.form-select:not([data-icon-picker])').forEach(enhanceSelect);
        if (window.feather) window.feather.replace();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
