(function () {
  'use strict';

  var iconMap = {
    'fa-align-left': 'ti-menu-2',
    'fa-alipay': 'ti-credit-card',
    'fa-angle-down': 'ti-chevron-down',
    'fa-arrow-circle-down': 'ti-circle-arrow-down',
    'fa-arrow-circle-up': 'ti-circle-arrow-up',
    'fa-arrow-down-1-9': 'ti-sort-descending-numbers',
    'fa-arrow-left': 'ti-arrow-left',
    'fa-arrow-right': 'ti-arrow-right',
    'fa-arrow-right-from-bracket': 'ti-logout',
    'fa-arrows-rotate': 'ti-refresh',
    'fa-arrow-up-9-1': 'ti-sort-ascending-numbers',
    'fa-arrow-up-right-from-square': 'ti-arrow-up-right',
    'fa-award': 'ti-award',
    'fa-bag-shopping': 'ti-shopping-bag',
    'fa-ban': 'ti-ban',
    'fa-bars': 'ti-menu-2',
    'fa-bell': 'ti-bell',
    'fa-bitcoin': 'ti-currency-bitcoin',
    'fa-bolt': 'ti-bolt',
    'fa-book-open': 'ti-book-2',
    'fa-building': 'ti-building',
    'fa-building-columns': 'ti-building-bank',
    'fa-building-shield': 'ti-building-fortress',
    'fa-calculator': 'ti-calculator',
    'fa-calendar-alt': 'ti-calendar',
    'fa-cart-arrow-down': 'ti-shopping-cart',
    'fa-cart-shopping': 'ti-shopping-cart',
    'fa-chart-line': 'ti-chart-line',
    'fa-chart-simple': 'ti-chart-bar',
    'fa-check': 'ti-check',
    'fa-check-circle': 'ti-circle-check',
    'fa-check-double': 'ti-checks',
    'fa-chevron-down': 'ti-chevron-down',
    'fa-chevron-right': 'ti-chevron-right',
    'fa-chevron-up': 'ti-chevron-up',
    'fa-circle': 'ti-circle',
    'fa-circle-check': 'ti-circle-check',
    'fa-circle-info': 'ti-info-circle',
    'fa-circle-minus': 'ti-circle-minus',
    'fa-circle-nodes': 'ti-git-fork',
    'fa-circle-notch': 'ti-loader-2',
    'fa-circle-question': 'ti-help-circle',
    'fa-clock': 'ti-clock',
    'fa-clock-rotate-left': 'ti-history',
    'fa-cloud-arrow-down': 'ti-download',
    'fa-cloud-arrow-up': 'ti-upload',
    'fa-coins': 'ti-coins',
    'fa-comment-dots': 'ti-message-circle',
    'fa-copy': 'ti-copy',
    'fa-credit-card': 'ti-credit-card',
    'fa-crown': 'ti-crown',
    'fa-cubes-stacked': 'ti-cube',
    'fa-desktop': 'ti-device-desktop',
    'fa-display': 'ti-device-desktop',
    'fa-download': 'ti-download',
    'fa-ellipsis': 'ti-dots',
    'fa-exchange-alt': 'ti-arrows-exchange',
    'fa-exclamation-triangle': 'ti-alert-triangle',
    'fa-external-link': 'ti-external-link',
    'fa-eye': 'ti-eye',
    'fa-eye-slash': 'ti-eye-off',
    'fa-facebook': 'ti-brand-facebook',
    'fa-facebook-f': 'ti-brand-facebook',
    'fa-face-smile': 'ti-mood-smile',
    'fa-file-arrow-down': 'ti-file-download',
    'fa-file-code': 'ti-file-code',
    'fa-file-csv': 'ti-file-spreadsheet',
    'fa-file-export': 'ti-file-export',
    'fa-file-invoice': 'ti-file-invoice',
    'fa-file-lines': 'ti-file-text',
    'fa-floppy-disk': 'ti-device-floppy',
    'fa-gear': 'ti-settings',
    'fa-gift': 'ti-gift',
    'fa-globe': 'ti-world',
    'fa-google': 'ti-brand-google',
    'fa-grip-vertical': 'ti-grip-vertical',
    'fa-hand-holding-dollar': 'ti-hand-coins',
    'fa-hand-point-left': 'ti-hand-click',
    'fa-hand-point-right': 'ti-hand-click',
    'fa-headset': 'ti-headset',
    'fa-heart': 'ti-heart',
    'fa-history': 'ti-history',
    'fa-hourglass-half': 'ti-hourglass',
    'fa-icons': 'ti-icons',
    'fa-info-circle': 'ti-info-circle',
    'fa-instagram': 'ti-brand-instagram',
    'fa-key': 'ti-key',
    'fa-layer-group': 'ti-layers-subtract',
    'fa-link': 'ti-link',
    'fa-linkedin': 'ti-brand-linkedin',
    'fa-list': 'ti-list',
    'fa-list-check': 'ti-list-check',
    'fa-lock': 'ti-lock',
    'fa-lock-open': 'ti-lock-open',
    'fa-long-arrow-alt-left': 'ti-arrow-left',
    'fa-long-arrow-alt-right': 'ti-arrow-right',
    'fa-magnifying-glass': 'ti-search',
    'fa-medal': 'ti-medal',
    'fa-minus': 'ti-minus',
    'fa-mobile-screen-button': 'ti-device-mobile',
    'fa-money-bill-trend-up': 'ti-trending-up',
    'fa-network-wired': 'ti-network',
    'fa-newspaper': 'ti-news',
    'fa-paper-plane': 'ti-send',
    'fa-paypal': 'ti-brand-paypal',
    'fa-percent': 'ti-percentage',
    'fa-play': 'ti-player-play',
    'fa-plug-circle-xmark': 'ti-plug-connected-x',
    'fa-plus': 'ti-plus',
    'fa-qrcode': 'ti-qrcode',
    'fa-receipt': 'ti-receipt',
    'fa-right-from-bracket': 'ti-logout',
    'fa-right-to-bracket': 'ti-login',
    'fa-rotate': 'ti-rotate',
    'fa-rotate-right': 'ti-refresh',
    'fa-save': 'ti-device-floppy',
    'fa-screwdriver-wrench': 'ti-tools',
    'fa-search': 'ti-search',
    'fa-server': 'ti-server',
    'fa-share-nodes': 'ti-share-3',
    'fa-shield-check': 'ti-shield-check',
    'fa-shield-halved': 'ti-shield',
    'fa-shield-heart': 'ti-shield-heart',
    'fa-spinner': 'ti-loader-2',
    'fa-square-x-twitter': 'ti-brand-x',
    'fa-store': 'ti-shopping-bag',
    'fa-table-cells-large': 'ti-layout-dashboard',
    'fa-table-columns': 'ti-columns',
    'fa-tag': 'ti-tag',
    'fa-tags': 'ti-tags',
    'fa-thumbs-up': 'ti-thumb-up',
    'fa-tiktok': 'ti-brand-tiktok',
    'fa-times': 'ti-x',
    'fa-tower-broadcast': 'ti-antenna-bars-5',
    'fa-trash': 'ti-trash',
    'fa-trash-alt': 'ti-trash',
    'fa-triangle-exclamation': 'ti-alert-triangle',
    'fa-trophy': 'ti-trophy',
    'fa-university': 'ti-building-bank',
    'fa-unlock-alt': 'ti-lock-open',
    'fa-user': 'ti-user',
    'fa-user-edit': 'ti-user-edit',
    'fa-user-plus': 'ti-user-plus',
    'fa-users': 'ti-users',
    'fa-user-shield': 'ti-shield-lock',
    'fa-wallet': 'ti-wallet',
    'fa-weixin': 'ti-brand-wechat',
    'fa-xmark': 'ti-x',
    'fa-x-twitter': 'ti-brand-x',
    'fa-youtube': 'ti-brand-youtube',
    'icofont-arrow-right': 'ti-arrow-right',
    'icofont-close': 'ti-x',
    'icofont-email': 'ti-mail',
    'icofont-home': 'ti-home',
    'icofont-location-pin': 'ti-map-pin',
    'icofont-logout': 'ti-logout',
    'icofont-money': 'ti-cash',
    'icofont-phone': 'ti-phone',
    'icofont-search-1': 'ti-search',
    'icofont-star': 'ti-star',
    'icofont-trash': 'ti-trash',
    'icofont-world': 'ti-world'
  };

  var familyClasses = [
    'fa-solid', 'fa-regular', 'fa-brands', 'fa-sharp', 'fas', 'far', 'fab', 'fa', 'icofont',
    'fa-spin', 'fa-pulse', 'fa-2x', 'fa-3x'
  ];

  function replaceIcon(icon) {
    if (!icon || !icon.classList || icon.classList.contains('ti')) {
      return;
    }

    var classes = Array.prototype.slice.call(icon.classList);
    var source = classes.find(function (className) {
      return Boolean(iconMap[className]);
    });

    if (!source || !iconMap[source]) {
      return;
    }

    var isSpinning = icon.classList.contains('fa-spin') || source === 'fa-spinner' || source === 'fa-circle-notch';
    familyClasses.forEach(function (className) { icon.classList.remove(className); });
    icon.classList.remove(source);
    icon.classList.add('ti', iconMap[source]);

    if (isSpinning) {
      icon.classList.add('caffemmo-icon-spin');
    }
  }

  function replaceIcons(root) {
    if (!root || root.nodeType !== 1 && root.nodeType !== 9) {
      return;
    }

    if (root.matches && root.matches('i')) {
      replaceIcon(root);
    }

    root.querySelectorAll('i').forEach(replaceIcon);
  }

  function boot() {
    replaceIcons(document);

    var observer = new MutationObserver(function (records) {
      records.forEach(function (record) {
        record.addedNodes.forEach(function (node) {
          replaceIcons(node);
        });
      });
    });

    var clientRoot = document.querySelector('.caffemmo-client');
    if (clientRoot) {
      observer.observe(clientRoot, { childList: true, subtree: true });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
  } else {
    boot();
  }
}());
