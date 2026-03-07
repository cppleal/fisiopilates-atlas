/**
 * Sistema de gestión de consentimiento de cookies RGPD/LSSI-CE
 * Fisiopilates Atlas
 * Versión: 1.0.0
 */

(function() {
    'use strict';

    const CONFIG = {
        cookieName: 'atlas_cookie_consent',
        cookieExpiry: 365,
        version: '1.0',
        apiEndpoint: '/api/cookies/log-consent.php',
        policyUrl: '/cookies'
    };

    const CATEGORIES = {
        necessary: {
            name: 'Necesarias',
            description: 'Cookies esenciales para el funcionamiento del sitio. Incluyen cookies de sesión y seguridad.',
            required: true
        },
        analytics: {
            name: 'Analíticas',
            description: 'Nos ayudan a entender cómo se usa el sitio para mejorarlo. No identifican usuarios.',
            required: false
        },
        functional: {
            name: 'Funcionales',
            description: 'Guardan tus preferencias de navegación para una mejor experiencia.',
            required: false
        }
    };

    let state = {
        preferences: null,
        sessionToken: null,
        bannerShown: false
    };

    function init() {
        state.preferences = loadPreferences();

        if (!state.preferences) {
            showBanner();
        } else {
            applyPreferences(state.preferences);
            createFloatingButton();
        }
    }

    function loadPreferences() {
        try {
            var stored = localStorage.getItem(CONFIG.cookieName);
            if (stored) {
                var prefs = JSON.parse(stored);
                if (prefs.version === CONFIG.version) {
                    return prefs;
                }
            }
        } catch (e) {
            console.warn('Error cargando preferencias de cookies:', e);
        }
        return null;
    }

    function savePreferences(preferences, action) {
        preferences.version = CONFIG.version;
        preferences.timestamp = new Date().toISOString();

        try {
            localStorage.setItem(CONFIG.cookieName, JSON.stringify(preferences));
        } catch (e) {
            console.warn('Error guardando preferencias:', e);
        }

        sendToServer(preferences, action);
        state.preferences = preferences;
        applyPreferences(preferences);
    }

    function sendToServer(preferences, action) {
        var data = {
            action: action,
            necessary: true,
            analytics: preferences.analytics || false,
            functional: preferences.functional || false,
            session_token: getSessionToken(),
            page_url: window.location.href
        };

        fetch(CONFIG.apiEndpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
            credentials: 'same-origin'
        }).catch(function(error) {
            console.warn('Error enviando preferencias de cookies:', error);
        });
    }

    function getSessionToken() {
        if (!state.sessionToken) {
            state.sessionToken = localStorage.getItem('atlas_session_token');
            if (!state.sessionToken) {
                state.sessionToken = generateToken();
                localStorage.setItem('atlas_session_token', state.sessionToken);
            }
        }
        return state.sessionToken;
    }

    function generateToken() {
        var array = new Uint8Array(32);
        crypto.getRandomValues(array);
        return Array.from(array, function(b) { return b.toString(16).padStart(2, '0'); }).join('');
    }

    function applyPreferences(preferences) {
        var event = new CustomEvent('cookieConsentUpdated', { detail: preferences });
        document.dispatchEvent(event);
    }

    function showBanner() {
        if (state.bannerShown) return;
        state.bannerShown = true;

        var banner = document.createElement('div');
        banner.id = 'cookie-consent-banner';
        banner.className = 'cookie-banner';
        banner.innerHTML = createBannerHTML();
        document.body.appendChild(banner);

        setupBannerEventListeners(banner);

        requestAnimationFrame(function() {
            banner.classList.add('visible');
        });
    }

    function createBannerHTML() {
        return '<div class="cookie-banner-content">' +
            '<div class="cookie-banner-header">' +
                '<div class="cookie-icon">&#x1F36A;</div>' +
                '<h3>Tu privacidad importa</h3>' +
            '</div>' +
            '<div class="cookie-banner-text">' +
                '<p>Utilizamos cookies para mejorar tu experiencia. Puedes personalizar tus preferencias o aceptar todas.</p>' +
                '<a href="' + CONFIG.policyUrl + '" class="cookie-policy-link">Leer pol\u00edtica de cookies</a>' +
            '</div>' +
            '<div class="cookie-banner-actions">' +
                '<button class="cookie-btn cookie-btn-preferences" data-action="preferences">Personalizar</button>' +
                '<button class="cookie-btn cookie-btn-reject" data-action="reject">Solo necesarias</button>' +
                '<button class="cookie-btn cookie-btn-accept" data-action="accept">Aceptar todas</button>' +
            '</div>' +
        '</div>';
    }

    function setupBannerEventListeners(banner) {
        banner.addEventListener('click', function(e) {
            var action = e.target.dataset.action;
            if (!action) return;

            switch (action) {
                case 'accept':
                    acceptAll();
                    hideBanner();
                    break;
                case 'reject':
                    rejectAll();
                    hideBanner();
                    break;
                case 'preferences':
                    showPreferencesModal();
                    break;
            }
        });
    }

    function hideBanner() {
        var banner = document.getElementById('cookie-consent-banner');
        if (banner) {
            banner.classList.remove('visible');
            setTimeout(function() {
                banner.remove();
                createFloatingButton();
            }, 300);
        }
        state.bannerShown = false;
    }

    function acceptAll() {
        savePreferences({
            necessary: true,
            analytics: true,
            functional: true
        }, 'accept_all');
    }

    function rejectAll() {
        savePreferences({
            necessary: true,
            analytics: false,
            functional: false
        }, 'reject_all');
    }

    function showPreferencesModal() {
        var modal = document.createElement('div');
        modal.id = 'cookie-preferences-modal';
        modal.className = 'cookie-modal';
        modal.innerHTML = createModalHTML();
        document.body.appendChild(modal);

        setupModalEventListeners(modal);

        requestAnimationFrame(function() {
            modal.classList.add('visible');
        });
    }

    function createModalHTML() {
        var currentPrefs = state.preferences || {};

        var categoriesHTML = '';
        for (var key in CATEGORIES) {
            var cat = CATEGORIES[key];
            var isChecked = cat.required || currentPrefs[key];
            var isDisabled = cat.required;

            categoriesHTML += '<div class="cookie-category">' +
                '<div class="cookie-category-header">' +
                    '<label class="cookie-toggle">' +
                        '<input type="checkbox" name="' + key + '"' +
                            (isChecked ? ' checked' : '') +
                            (isDisabled ? ' disabled' : '') + '>' +
                        '<span class="cookie-toggle-slider"></span>' +
                    '</label>' +
                    '<span class="cookie-category-name">' + cat.name +
                        (cat.required ? ' <span class="required-badge">Obligatorias</span>' : '') +
                    '</span>' +
                '</div>' +
                '<p class="cookie-category-desc">' + cat.description + '</p>' +
            '</div>';
        }

        return '<div class="cookie-modal-overlay"></div>' +
            '<div class="cookie-modal-content">' +
                '<div class="cookie-modal-header">' +
                    '<h3>Configurar preferencias de cookies</h3>' +
                    '<button class="cookie-modal-close" data-action="close">&times;</button>' +
                '</div>' +
                '<div class="cookie-modal-body">' +
                    '<p class="cookie-modal-intro">Selecciona qu\u00e9 tipos de cookies deseas aceptar. Las cookies necesarias no se pueden desactivar ya que son esenciales para el funcionamiento del sitio.</p>' +
                    '<div class="cookie-categories">' + categoriesHTML + '</div>' +
                '</div>' +
                '<div class="cookie-modal-footer">' +
                    '<a href="' + CONFIG.policyUrl + '" class="cookie-policy-link">Leer pol\u00edtica de cookies</a>' +
                    '<div class="cookie-modal-actions">' +
                        '<button class="cookie-btn cookie-btn-reject" data-action="reject-modal">Rechazar opcionales</button>' +
                        '<button class="cookie-btn cookie-btn-accept" data-action="save">Guardar preferencias</button>' +
                    '</div>' +
                '</div>' +
            '</div>';
    }

    function setupModalEventListeners(modal) {
        modal.addEventListener('click', function(e) {
            var action = e.target.dataset.action;
            if (!action) {
                if (e.target.classList.contains('cookie-modal-overlay')) {
                    hideModal();
                }
                return;
            }

            switch (action) {
                case 'close':
                    hideModal();
                    break;
                case 'save':
                    saveFromModal(modal);
                    break;
                case 'reject-modal':
                    rejectAll();
                    hideModal();
                    hideBanner();
                    break;
            }
        });

        document.addEventListener('keydown', function handler(e) {
            if (e.key === 'Escape') {
                hideModal();
                document.removeEventListener('keydown', handler);
            }
        });
    }

    function saveFromModal(modal) {
        var preferences = { necessary: true };

        var checkboxes = modal.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(function(cb) {
            if (cb.name !== 'necessary') {
                preferences[cb.name] = cb.checked;
            }
        });

        savePreferences(preferences, 'save_preferences');
        hideModal();
        hideBanner();
    }

    function hideModal() {
        var modal = document.getElementById('cookie-preferences-modal');
        if (modal) {
            modal.classList.remove('visible');
            setTimeout(function() {
                modal.remove();
            }, 300);
        }
    }

    function createFloatingButton() {
        if (document.getElementById('cookie-floating-btn')) return;

        var btn = document.createElement('button');
        btn.id = 'cookie-floating-btn';
        btn.className = 'cookie-floating-btn';
        btn.innerHTML = '&#x1F36A;';
        btn.title = 'Configurar cookies';
        btn.setAttribute('aria-label', 'Configurar preferencias de cookies');

        btn.addEventListener('click', function() {
            showPreferencesModal();
        });

        document.body.appendChild(btn);
    }

    // API pública
    window.ATLAS_CookieConsent = {
        init: init,
        acceptAll: acceptAll,
        rejectAll: rejectAll,
        showPreferences: showPreferencesModal,
        getPreferences: function() { return state.preferences; },
        isCategoryAllowed: function(category) {
            if (category === 'necessary') return true;
            return state.preferences ? (state.preferences[category] || false) : false;
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
