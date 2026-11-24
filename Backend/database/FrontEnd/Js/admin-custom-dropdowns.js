(function () {
    'use strict';
    const TARGETS = ['regionFilter', 'categoryFilter', 'destinationRegion', 'destinationCategory', 'reviewRating'];
    const GREEN = '#398f80';

    function enhance(select) {
        if (!select || select.dataset.customDropdownReady === '1') return;
        select.dataset.customDropdownReady = '1';
        // Capture the original select appearance before it is moved/visually hidden.
        const originalStyle = window.getComputedStyle(select);
        const originalStyleEntries = Array.from(originalStyle, property => [property, originalStyle.getPropertyValue(property), originalStyle.getPropertyPriority(property)]);
        const wrapper = document.createElement('div');
        wrapper.className = 'admin-custom-select';
        wrapper.style.width = '100%';
        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(select);

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = (select.className ? select.className + ' ' : '') + 'admin-custom-select__trigger';
        // Keep the closed control visually identical to the original native select.
        for (const [property, value, priority] of originalStyleEntries) {
            trigger.style.setProperty(property, value, priority);
        }
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        trigger.setAttribute('aria-label', select.getAttribute('aria-label') || 'Pilih opsi');
        trigger.style.boxSizing = 'border-box';
        trigger.style.cursor = 'pointer';
        wrapper.appendChild(trigger);

        const panel = document.createElement('div');
        panel.className = 'admin-custom-select__panel';
        panel.setAttribute('role', 'listbox');
        panel.hidden = true;
        wrapper.appendChild(panel);

        function visibleOptions() {
            return Array.from(select.options).filter(option => select.id === 'reviewRating' || option.value !== '');
        }
        function selectedText() {
            return select.options[select.selectedIndex]?.text || '';
        }
        function render() {
            trigger.textContent = selectedText();
            trigger.classList.toggle('is-placeholder', !select.value && select.id !== 'reviewRating');
            panel.replaceChildren();
            visibleOptions().forEach(option => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'admin-custom-select__option';
                item.textContent = option.text;
                item.setAttribute('role', 'option');
                item.setAttribute('aria-selected', option.selected ? 'true' : 'false');
                item.disabled = option.disabled;
                item.addEventListener('click', () => {
                    select.value = option.value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    render();
                    close();
                    trigger.focus();
                });
                panel.appendChild(item);
            });
        }
        function open() {
            document.querySelectorAll('.admin-custom-select.is-open').forEach(other => {
                if (other !== wrapper) {
                    other.classList.remove('is-open');
                    const otherPanel = other.querySelector('.admin-custom-select__panel');
                    const otherTrigger = other.querySelector('.admin-custom-select__trigger');
                    if (otherPanel) otherPanel.hidden = true;
                    if (otherTrigger) otherTrigger.setAttribute('aria-expanded', 'false');
                }
            });
            render();
            wrapper.classList.add('is-open');
            panel.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');
        }
        function close() {
            wrapper.classList.remove('is-open');
            panel.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
        }
        trigger.addEventListener('click', () => wrapper.classList.contains('is-open') ? close() : open());
        select.addEventListener('change', render);
        select.addEventListener('invalid', event => { event.preventDefault(); trigger.classList.add('is-invalid'); trigger.focus(); });
        select.addEventListener('change', () => trigger.classList.remove('is-invalid'));
        document.addEventListener('click', event => { if (!wrapper.contains(event.target)) close(); });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && wrapper.classList.contains('is-open')) { close(); trigger.focus(); }
        });
        const observer = new MutationObserver(render);
        observer.observe(select, { childList: true, subtree: true, attributes: true });
        render();
    }
    function init() { TARGETS.forEach(id => enhance(document.getElementById(id))); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
    window.addEventListener('load', init);
})();
