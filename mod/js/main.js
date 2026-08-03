(function () {
    'use strict';

    function bindServiceCardSpotlight() {
        var cards = document.querySelectorAll('.home-service-card');
        if (!cards.length) {
            return;
        }

        cards.forEach(function (card) {
            var frameId = null;
            var cardRect = null;
            var point = { x: 50, y: 0 };

            function renderSpotlight() {
                card.style.setProperty('--spot-x', point.x.toFixed(2) + '%');
                card.style.setProperty('--spot-y', point.y.toFixed(2) + '%');
                frameId = null;
            }

            card.addEventListener('pointerenter', function () {
                cardRect = card.getBoundingClientRect();
            });

            card.addEventListener('pointermove', function (event) {
                if (event.pointerType === 'touch') {
                    return;
                }

                cardRect = cardRect || card.getBoundingClientRect();
                point.x = ((event.clientX - cardRect.left) / cardRect.width) * 100;
                point.y = ((event.clientY - cardRect.top) / cardRect.height) * 100;

                if (frameId === null) {
                    frameId = window.requestAnimationFrame(renderSpotlight);
                }
            });

            card.addEventListener('pointerleave', function () {
                if (frameId !== null) {
                    window.cancelAnimationFrame(frameId);
                    frameId = null;
                }
                cardRect = null;
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
