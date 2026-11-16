(() => {
    'use strict';
    const apiUrl = new URL('../../Backend/api/about.php', document.currentScript.src).toString();

    const setText = (selector, value) => {
        const element = document.querySelector(selector);
        if (element && typeof value === 'string') element.textContent = value;
    };

    async function loadAboutContent() {
        try {
            const response = await fetch(apiUrl, {
                method: 'GET',
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
                cache: 'no-store'
            });
            const payload = await response.json();
            if (!response.ok || payload.status !== 'success' || !payload.data) {
                throw new Error(payload.message || 'Tidak dapat memuat informasi Tentang.');
            }

            const sections = payload.data;
            if (sections.intro) {
                setText('[data-about="intro-title"]', sections.intro.title);
                setText('[data-about="intro-content"]', sections.intro.content);
            }
            if (sections.vision) {
                setText('[data-about="vision-title"]', sections.vision.title);
                setText('[data-about="vision-content"]', sections.vision.content);
            }
            if (sections.mission) {
                setText('[data-about="mission-title"]', sections.mission.title);
                setText('[data-about="mission-content"]', sections.mission.content);
            }
        } catch (error) {
            console.error('Tentang API:', error);
            const notice = document.querySelector('[data-about-error]');
            if (notice) {
                notice.hidden = false;
                notice.textContent = 'Informasi belum dapat dimuat dari server. Silakan muat ulang halaman.';
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadAboutContent, { once: true });
    } else {
        loadAboutContent();
    }
})();
