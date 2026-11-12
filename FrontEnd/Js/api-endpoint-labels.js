(() => {
    'use strict';
    const style = document.createElement('style');
    style.textContent = `
        .api-endpoint-tooltip{position:fixed;z-index:99999;display:none;max-width:min(460px,calc(100vw - 24px));padding:9px 12px;border:1px solid rgba(255,255,255,.16);border-radius:8px;background:#173a32;color:#fff;box-shadow:0 8px 24px rgba(0,0,0,.2);font:500 12px/1.5 Inter,Arial,sans-serif;overflow-wrap:anywhere;white-space:pre-line;pointer-events:none}
        .api-endpoint-tooltip.is-visible{display:block}
    `;
    document.head.appendChild(style);
    const tooltip = document.createElement('div');
    tooltip.className = 'api-endpoint-tooltip';
    tooltip.id = 'apiEndpointTooltip';
    tooltip.setAttribute('role', 'tooltip');
    document.body.appendChild(tooltip);
    let active = null;

    function details(control) {
        const form = control.closest('form');
        const url = control.dataset.apiUrl || control.dataset.endpoint ||
            (form && (form.dataset.apiEndpoint || form.dataset.endpoint || form.dataset.apiUrl || form.action));
        const unavailable = control.dataset.apiState === 'unavailable' || (form && form.dataset.apiState === 'unavailable');
        const method = (control.dataset.apiMethod || (form && form.method) || 'GET').toUpperCase();
        const description = control.dataset.apiDescription || (form && form.dataset.apiDescription) || '';
        const endpoint = url
            ? `${unavailable ? 'API belum tersedia' : 'Endpoint API'}\n${method} ${new URL(url, location.href).href}`
            : 'Aksi antarmuka\nTidak memanggil API server';
        return description ? `${endpoint}\n${description}` : endpoint;
    }
    function show(control) {
        if (!control || !control.matches('button,input[type="submit"]')) return;
        active = control;
        try { tooltip.textContent = details(control); }
        catch (error) { tooltip.textContent = 'Endpoint tidak dapat dibaca.'; }
        control.setAttribute('aria-describedby', tooltip.id);
        tooltip.classList.add('is-visible');
        const rect = control.getBoundingClientRect();
        const tipRect = tooltip.getBoundingClientRect();
        let left = rect.left + (rect.width - tipRect.width) / 2;
        left = Math.max(12, Math.min(left, innerWidth - tipRect.width - 12));
        let top = rect.top - tipRect.height - 9;
        if (top < 8) top = Math.min(innerHeight - tipRect.height - 8, rect.bottom + 9);
        tooltip.style.left = `${left}px`;
        tooltip.style.top = `${Math.max(8, top)}px`;
    }
    function hide(control) {
        if (control && active !== control) return;
        tooltip.classList.remove('is-visible');
        active = null;
    }
    document.addEventListener('mouseover', event => {
        const control = event.target.closest && event.target.closest('button,input[type="submit"]');
        if (control && !control.contains(event.relatedTarget)) show(control);
    });
    document.addEventListener('mouseout', event => {
        const control = event.target.closest && event.target.closest('button,input[type="submit"]');
        if (control && !control.contains(event.relatedTarget)) hide(control);
    });
    document.addEventListener('focusin', event => show(event.target.closest && event.target.closest('button,input[type="submit"]')));
    document.addEventListener('focusout', event => hide(event.target.closest && event.target.closest('button,input[type="submit"]')));
    document.addEventListener('click', event => hide(event.target.closest && event.target.closest('button,input[type="submit"]')));
})();
