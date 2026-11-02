(() => {
    function formatUrl(value) {
        try {
            return new URL(value, window.location.href).href
                .replace(/%7B/gi, '{')
                .replace(/%7D/gi, '}');
        } catch {
            return value;
        }
    }

    function addEndpointTitle(button, form) {
        const url = button.dataset.apiUrl ||
            button.dataset.endpoint ||
            (form && form.dataset.apiEndpoint) ||
            (form && form.dataset.endpoint) ||
            (form && form.dataset.apiUrl) ||
            (form && form.action) ||
            window.location.href;
        if (!url) return;

        const unavailable = button.dataset.apiState === 'unavailable' ||
            (form && form.dataset.apiState === 'unavailable')
        const endpointTitle = `${unavailable ? 'API belum tersedia' : 'Endpoint'}: ${formatUrl(url)}`;
        button.title = button.title
            ? `${button.title} — ${endpointTitle}`
            : endpointTitle;
    }

    const buttons = new Set(document.querySelectorAll(
        'button[type="submit"], input[type="submit"], button[data-api-url], button[data-endpoint]'
    ));

    buttons.forEach(button => {
        addEndpointTitle(button, button.closest('form'));
    });
})();
