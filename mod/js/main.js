(function () {
    'use strict';

    function bindServiceCardSpotlight() {
        var cards = document.querySelectorAll('.home-service-card');
        if (!cards.length) {
            return;
        }

        cards.forEach(function (card) {
            card.addEventListener('pointermove', function (event) {
                var rect = card.getBoundingClientRect();
                var x = ((event.clientX - rect.left) / rect.width) * 100;
                var y = ((event.clientY - rect.top) / rect.height) * 100;
                card.style.setProperty('--spot-x', x.toFixed(2) + '%');
                card.style.setProperty('--spot-y', y.toFixed(2) + '%');
            });

            card.addEventListener('pointerleave', function () {
                card.style.setProperty('--spot-x', '50%');
                card.style.setProperty('--spot-y', '0%');
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindServiceCardSpotlight);
    } else {
        bindServiceCardSpotlight();
    }
}());
