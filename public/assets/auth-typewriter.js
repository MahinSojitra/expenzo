(function () {
    const heading = document.querySelector('[data-auth-typewriter]');
    if (!heading) return;

    const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
    if (motion.matches) return;

    // Keep the complete heading available to assistive technology.
    heading.setAttribute('aria-label', heading.textContent.replace(/\s+/g, ' ').trim());
    const text = document.createElement('span');
    text.className = 'auth-type-text';
    text.setAttribute('aria-hidden', 'true');
    while (heading.firstChild) text.appendChild(heading.firstChild);
    heading.appendChild(text);

    const walker = document.createTreeWalker(text, NodeFilter.SHOW_TEXT);
    const nodes = [];
    while (walker.nextNode()) nodes.push(walker.currentNode);
    const letters = [];
    nodes.forEach(function (node) {
        const fragment = document.createDocumentFragment();
        Array.from(node.textContent).forEach(function (character) {
            const letter = document.createElement('span');
            letter.className = 'auth-type-char';
            letter.textContent = character;
            fragment.appendChild(letter);
            letters.push(letter);
        });
        node.replaceWith(fragment);
    });

    let index = 0;
    let timer;
    let cursor;
    function finish() {
        window.clearTimeout(timer);
        letters.forEach(function (letter) { letter.classList.add('is-visible'); });
        if (cursor) cursor.classList.remove('has-cursor');
    }
    function type() {
        if (motion.matches) { finish(); return; }
        if (cursor) cursor.classList.remove('has-cursor');
        if (index === letters.length) { finish(); return; }
        cursor = letters[index++];
        cursor.classList.add('is-visible', 'has-cursor');
        const delay = index === letters.length ? 1000 : (cursor.textContent === ' ' ? 110 : 55 + Math.random() * 45);
        timer = window.setTimeout(type, delay);
    }
    motion.addEventListener('change', function () { if (motion.matches) finish(); });
    timer = window.setTimeout(type, 250);
}());
