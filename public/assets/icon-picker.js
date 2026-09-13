document.addEventListener('DOMContentLoaded', () => {
    const icons = window.feather?.icons;
    if (!icons) return;
    const names = Object.keys(icons).sort();
    const label = name => name.replace(/-/g, ' ').replace(/\b\w/g, letter => letter.toUpperCase());

    document.querySelectorAll('select[data-icon-picker]').forEach(select => {
        const originalId = select.id;
        const wrapper = document.createElement('div');
        wrapper.className = 'icon-picker';
        select.before(wrapper);
        wrapper.append(select);
        // Keep the named select as the submitted value and as a fallback without JavaScript.
        names.forEach(name => {
            if (![...select.options].some(option => option.value === name)) {
                select.add(new Option(label(name), name));
            }
        });
        select.id = originalId + '-value';
        select.hidden = true;
        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.id = originalId;
        trigger.className = 'form-select icon-picker-trigger' + (select.classList.contains('is-invalid') ? ' is-invalid' : '');
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        if (select.hasAttribute('aria-describedby')) trigger.setAttribute('aria-describedby', select.getAttribute('aria-describedby'));
        if (select.hasAttribute('aria-invalid')) trigger.setAttribute('aria-invalid', 'true');

        const panel = document.createElement('div');
        panel.className = 'icon-picker-panel';
        panel.hidden = true;
        const search = document.createElement('input');
        search.type = 'search';
        search.className = 'form-control icon-picker-search';
        search.placeholder = 'Search icons…';
        search.autocomplete = 'off';
        search.setAttribute('aria-label', 'Search icons');
        search.setAttribute('role', 'combobox');
        search.setAttribute('aria-autocomplete', 'list');
        search.setAttribute('aria-expanded', 'false');
        const list = document.createElement('div');
        list.id = originalId + '-options';
        list.className = 'icon-picker-options';
        list.setAttribute('role', 'listbox');
        list.setAttribute('aria-label', 'Icons');
        trigger.setAttribute('aria-controls', list.id);
        search.setAttribute('aria-controls', list.id);
        const empty = document.createElement('p');
        empty.className = 'icon-picker-empty text-muted';
        empty.textContent = 'No icons found. Try another search.';
        empty.setAttribute('role', 'status');
        panel.append(search, list, empty);
        wrapper.append(trigger, panel);
        let active = -1;
        let results = [];

        function preview(element, name) {
            element.replaceChildren();
            if (Object.hasOwn(icons, name)) {
                const picture = document.createElement('span');
                picture.className = 'icon-picker-picture';
                // SVG comes only from the bundled Feather icon library.
                picture.innerHTML = icons[name].toSvg({'aria-hidden': 'true', width: 20, height: 20});
                element.append(picture);
            }
            const text = document.createElement('span');
            text.textContent = Object.hasOwn(icons, name) ? label(name) : (name || 'Choose an icon');
            element.append(text);
        }
        function highlight(index) {
            active = index;
            [...list.children].forEach((option, i) => option.classList.toggle('is-focused', i === index));
            const option = list.children[index];
            if (option) {
                search.setAttribute('aria-activedescendant', option.id);
                option.scrollIntoView({block: 'nearest'});
            } else search.removeAttribute('aria-activedescendant');
        }
        function render() {
            const query = search.value.trim().toLowerCase().replace(/[-_]/g, ' ');
            results = names.filter(name => label(name).toLowerCase().includes(query));
            list.replaceChildren();
            results.forEach((name, index) => {
                const option = document.createElement('div');
                option.id = originalId + '-option-' + name;
                option.className = 'icon-picker-option';
                option.setAttribute('role', 'option');
                option.setAttribute('aria-selected', String(name === select.value));
                preview(option, name);
                option.addEventListener('mousedown', event => event.preventDefault());
                option.addEventListener('click', () => choose(name));
                list.append(option);
            });
            empty.hidden = results.length > 0;
            highlight(results.length ? Math.max(0, results.indexOf(select.value)) : -1);
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
        function choose(name) {
            select.value = name;
            preview(trigger, name);
            select.dispatchEvent(new Event('change', {bubbles: true}));
            close(true);
        }
        trigger.addEventListener('click', () => panel.hidden ? open() : close());
        trigger.addEventListener('keydown', event => {
            if (['ArrowDown', 'ArrowUp'].includes(event.key)) {
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
            } else if (event.key === 'Tab') close();
        });
        document.addEventListener('click', event => {
            if (!wrapper.contains(event.target)) close();
        });
        wrapper.addEventListener('focusout', event => {
            if (!wrapper.contains(event.relatedTarget)) close();
        });
        select.form?.addEventListener('reset', () => setTimeout(() => {preview(trigger, select.value); close();}, 0));
        preview(trigger, select.value);
    });
});