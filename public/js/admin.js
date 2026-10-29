/**
 * Acciones del panel de administración
 * Fisiopilates Atlas
 *
 * Sustituye a los manejadores en línea (onclick=, onchange=), que la CSP del
 * panel no deja ejecutar. Cada acción se declara con un atributo data-*:
 *   data-confirm="texto"      → pide confirmación antes de seguir el enlace o enviar
 *   data-href="/ruta"         → navega al pulsar el elemento
 *   data-scroll-to="id"       → desplaza la página hasta el elemento con ese id
 *   data-fill="id" data-value → copia el valor en el campo con ese id
 *   data-nav-param="nombre"   → (select) recarga con ?nombre=valor al cambiar
 */

(function() {
    'use strict';

    document.addEventListener('click', function(e) {
        var el;

        el = e.target.closest('[data-confirm]');
        if (el && !window.confirm(el.getAttribute('data-confirm'))) {
            e.preventDefault();
            return;
        }

        el = e.target.closest('[data-href]');
        if (el) {
            window.location.href = el.getAttribute('data-href');
            return;
        }

        el = e.target.closest('[data-scroll-to]');
        if (el) {
            var destino = document.getElementById(el.getAttribute('data-scroll-to'));
            if (destino) destino.scrollIntoView({ behavior: 'smooth' });
            return;
        }

        el = e.target.closest('[data-fill]');
        if (el) {
            var campo = document.getElementById(el.getAttribute('data-fill'));
            if (campo) campo.value = el.getAttribute('data-value') || '';
        }
    });

    document.addEventListener('change', function(e) {
        var el = e.target.closest('select[data-nav-param]');
        if (el) {
            window.location.href = '?' + encodeURIComponent(el.getAttribute('data-nav-param')) +
                '=' + encodeURIComponent(el.value);
        }
    });
})();
