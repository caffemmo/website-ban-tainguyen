(function () {
    'use strict';

    document.querySelectorAll('[data-client-resource-toggle]').forEach(function (toggle) {
        var panel = document.getElementById(toggle.getAttribute('aria-controls'));
        var card = toggle.closest('.client-resource-card');
        if (!panel || !card) return;

        toggle.addEventListener('click', function () {
            var isOpen = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
            panel.hidden = isOpen;
            card.classList.toggle('is-open', !isOpen);
        });
    });
}());
