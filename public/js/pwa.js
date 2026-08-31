(function () {
  'use strict';

  var SW_URL = '/service-worker.js';
  var SW_SCOPE = '/';
  var UPDATE_TOAST_ID = 'pwa-update-toast';

  var deferredInstallPrompt = null;
  var swRegistration = null;
  var waitingWorker = null;

  function isSecureContext() {
    return window.isSecureContext === true;
  }

  function isStandalone() {
    return (
      window.matchMedia('(display-mode: standalone)').matches ||
      window.matchMedia('(display-mode: fullscreen)').matches ||
      window.matchMedia('(display-mode: minimal-ui)').matches ||
      window.navigator.standalone === true
    );
  }

  function isInstalled() {
    return isStandalone();
  }

  function getInstallButtons() {
    return document.querySelectorAll('[data-pwa-install]');
  }

  function getStatusElements() {
    return document.querySelectorAll('[data-pwa-status]');
  }

  function updateInstallVisibility() {
    var canInstall = !!deferredInstallPrompt && !isInstalled();
    getInstallButtons().forEach(function (btn) {
      btn.hidden = !canInstall;
      btn.disabled = !canInstall;
    });

    updateStatusUI();
  }

  function updateStatusUI() {
    var online = navigator.onLine;
    var installed = isInstalled();
    var updateAvailable = !!waitingWorker;

    getStatusElements().forEach(function (el) {
      var state = 'browser';
      if (!online) {
        state = 'offline';
      } else if (updateAvailable) {
        state = 'update';
      } else if (installed) {
        state = 'installed';
      } else if (deferredInstallPrompt) {
        state = 'installable';
      }

      el.dataset.pwaState = state;

      var label = el.querySelector('[data-pwa-status-label]');
      if (!label) return;

      switch (state) {
        case 'offline':
          label.textContent = 'Offline Mode';
          break;
        case 'update':
          label.textContent = 'Update Available';
          break;
        case 'installed':
          label.textContent = 'Application Installed';
          break;
        case 'installable':
          label.textContent = 'Install App';
          break;
        default:
          label.textContent = 'Browser Version';
      }
    });

    document.querySelectorAll('[data-pwa-installed-only]').forEach(function (el) {
      el.hidden = !installed;
    });
    document.querySelectorAll('[data-pwa-browser-only]').forEach(function (el) {
      el.hidden = installed;
    });
  }

  function markInstalledAppMode() {
    if (isInstalled()) {
      document.documentElement.classList.add('is-installed-app');
    }
  }

  function isManifestReachable() {
    return fetch('/site.webmanifest', { credentials: 'same-origin', redirect: 'manual' })
      .then(function (response) {
        if (response.type === 'opaqueredirect' || response.status === 0) return false;
        if (response.status === 401 || response.status === 403) return false;
        return response.ok;
      })
      .catch(function () {
        return false;
      });
  }

  function unregisterServiceWorkers() {
    if (!('serviceWorker' in navigator)) return Promise.resolve();

    return navigator.serviceWorker.getRegistrations().then(function (registrations) {
      return Promise.all(registrations.map(function (registration) {
        return registration.unregister();
      }));
    });
  }

  function registerServiceWorker() {
    if (!('serviceWorker' in navigator) || !isSecureContext()) return;

    isManifestReachable().then(function (reachable) {
      if (!reachable) {
        unregisterServiceWorkers();
        return;
      }

      navigator.serviceWorker
      .register(SW_URL, { scope: SW_SCOPE })
      .then(function (registration) {
        swRegistration = registration;

        if (registration.waiting) {
          waitingWorker = registration.waiting;
          showUpdatePrompt();
        }

        registration.addEventListener('updatefound', function () {
          var newWorker = registration.installing;
          if (!newWorker) return;

          newWorker.addEventListener('statechange', function () {
            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
              waitingWorker = newWorker;
              showUpdatePrompt();
            }
          });
        });
      })
      .catch(function () {
        // Silent fail — PWA is progressive enhancement
      });
    });

    navigator.serviceWorker.addEventListener('controllerchange', function () {
      if (window.__pwaReloading) return;
      window.__pwaReloading = true;
      window.location.reload();
    });
  }

  function showUpdatePrompt() {
    updateStatusUI();

    if (document.getElementById(UPDATE_TOAST_ID)) return;

    var toast = document.createElement('div');
    toast.id = UPDATE_TOAST_ID;
    toast.className = 'pwa-update-banner';
    toast.setAttribute('role', 'alert');
    toast.innerHTML =
      '<div class="pwa-update-banner__content">' +
      '<p class="pwa-update-banner__text">A new version of the application is available.</p>' +
      '<div class="pwa-update-banner__actions">' +
      '<button type="button" class="btn btn--ghost btn--sm" data-pwa-update-later>Later</button>' +
      '<button type="button" class="btn btn--primary btn--sm" data-pwa-update-now>Update Now</button>' +
      '</div></div>';

    document.body.appendChild(toast);

    toast.querySelector('[data-pwa-update-later]').addEventListener('click', function () {
      toast.remove();
    });

    toast.querySelector('[data-pwa-update-now]').addEventListener('click', function () {
      applyUpdate();
    });
  }

  function applyUpdate() {
    if (!waitingWorker) return;

    waitingWorker.postMessage({ type: 'SKIP_WAITING' });
    document.getElementById(UPDATE_TOAST_ID)?.remove();
  }

  function promptInstall() {
    if (!deferredInstallPrompt) return;

    deferredInstallPrompt.prompt();

    deferredInstallPrompt.userChoice.then(function (choice) {
      if (choice.outcome === 'accepted') {
        deferredInstallPrompt = null;
        updateInstallVisibility();
      }
    });
  }

  function clearSensitiveClientState() {
    try {
      sessionStorage.removeItem('pwa-install-dismissed');
    } catch (e) {
      /* ignore */
    }

    if (swRegistration && swRegistration.active) {
      swRegistration.active.postMessage({ type: 'CLEAR_RUNTIME_CACHE' });
    }
  }

  function bindInstallButtons() {
    getInstallButtons().forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        promptInstall();
      });
    });
  }

  function bindLogoutCleanup() {
    document.querySelectorAll('form.logout-form').forEach(function (form) {
      form.addEventListener('submit', function () {
        if (form.dataset.logoutConfirmed === '1') {
          clearSensitiveClientState();
        }
      });
    });
  }

  function bindConnectionStatus() {
    window.addEventListener('online', updateStatusUI);
    window.addEventListener('offline', updateStatusUI);
  }

  function initBeforeInstallPrompt() {
    window.addEventListener('beforeinstallprompt', function (e) {
      e.preventDefault();
      deferredInstallPrompt = e;
      updateInstallVisibility();
    });

    window.addEventListener('appinstalled', function () {
      deferredInstallPrompt = null;
      markInstalledAppMode();
      updateInstallVisibility();
    });
  }

  function init() {
    markInstalledAppMode();
    registerServiceWorker();
    bindInstallButtons();
    bindLogoutCleanup();
    bindConnectionStatus();
    initBeforeInstallPrompt();
    updateInstallVisibility();
  }

  window.PWA = {
    isStandalone: isStandalone,
    isInstalled: isInstalled,
    promptInstall: promptInstall,
    applyUpdate: applyUpdate,
    clearSensitiveClientState: clearSensitiveClientState,
    updateStatusUI: updateStatusUI,
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
