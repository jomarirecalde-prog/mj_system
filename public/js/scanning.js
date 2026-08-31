(function () {
  'use strict';

  function escapeHtml(str) {
    return String(str ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  /**
   * Camera manager wrapping Html5Qrcode with status reporting and controls.
   */
  function createCameraManager(options) {
    const {
      readerId,
      onScan,
      onStatusChange,
      fps = 10,
      qrbox = { width: 250, height: 250 },
      pauseAfterScanMs = 0,
    } = options;

    let scanner = null;
    let scanning = false;
    let cameras = [];
    let currentCameraId = null;
    let status = 'initializing';
    let pauseTimer = null;

    function setStatus(next, message) {
      status = next;
      onStatusChange?.(next, message);
    }

    function getReaderEl() {
      return document.getElementById(readerId);
    }

    async function stop() {
      clearTimeout(pauseTimer);
      if (!scanner) return;
      try {
        if (scanning) await scanner.stop();
        await scanner.clear();
      } catch (_) {}
      scanning = false;
    }

    async function start(cameraId) {
      if (typeof Html5Qrcode === 'undefined') {
        setStatus('unavailable', 'Scanner library unavailable.');
        return;
      }

      const readerEl = getReaderEl();
      if (!readerEl) return;

      await stop();

      if (readerEl.querySelector('.scan-viewport__placeholder')) {
        readerEl.innerHTML = '';
      }

      if (!scanner) {
        scanner = new Html5Qrcode(readerId);
      }

      setStatus('initializing', 'Starting camera…');

      try {
        await scanner.start(
          cameraId,
          { fps, qrbox },
          function onSuccess(decoded) {
            if (!decoded) return;
            onScan(decoded);
            if (pauseAfterScanMs > 0 && scanning) {
              scanner.pause(true);
              clearTimeout(pauseTimer);
              pauseTimer = setTimeout(() => {
                try {
                  scanner.resume();
                } catch (_) {}
              }, pauseAfterScanMs);
            }
          },
          function onError() {}
        );
        scanning = true;
        currentCameraId = cameraId;
        setStatus('ready', 'Camera ready — scan QR code');
      } catch (err) {
        scanning = false;
        const msg = String(err?.message || err || '');
        if (/NotAllowed|Permission/i.test(msg)) {
          setStatus('denied', 'Camera permission required');
        } else {
          setStatus('failed', 'Unable to start camera');
        }
      }
    }

    async function restart() {
      if (currentCameraId) {
        await start(currentCameraId);
      } else if (cameras.length) {
        await start(cameras[cameras.length - 1].id);
      } else {
        await init();
      }
    }

    function showPlaceholder(title, text) {
      const readerEl = getReaderEl();
      if (!readerEl) return;
      readerEl.innerHTML =
        '<div class="scan-viewport__placeholder">' +
        '<svg class="scan-viewport__placeholder-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>' +
        '<p class="scan-viewport__placeholder-title">' + escapeHtml(title) + '</p>' +
        '<p class="scan-viewport__placeholder-text">' + escapeHtml(text) + '</p>' +
        '</div>';
    }

    async function init() {
      if (typeof Html5Qrcode === 'undefined') {
        setStatus('unavailable', 'Scanner library unavailable.');
        showPlaceholder('Scanner Unavailable', 'Use manual entry or USB scanner.');
        return { cameras: [], scanner: null };
      }

      setStatus('initializing', 'Starting camera…');

      try {
        cameras = await Html5Qrcode.getCameras();
        if (!cameras.length) {
          setStatus('unavailable', 'No camera detected');
          showPlaceholder('Camera Unavailable', 'Use manual entry or USB scanner.');
          return { cameras: [], scanner: null };
        }
        const camId = cameras[cameras.length - 1].id;
        await start(camId);
        return { cameras, scanner, currentCameraId: camId };
      } catch (err) {
        const msg = String(err?.message || err || '');
        if (/NotAllowed|Permission/i.test(msg)) {
          setStatus('denied', 'Camera permission required');
          showPlaceholder('Camera Access Blocked', 'Allow camera permission in your browser settings.');
        } else {
          setStatus('unavailable', 'Camera access unavailable');
          showPlaceholder('Camera Unavailable', 'Use manual entry or USB scanner.');
        }
        return { cameras: [], scanner: null };
      }
    }

    function bindControls(config) {
      const { cameraSelectId, restartBtnId, stopBtnId } = config || {};

      const cameraSelect = cameraSelectId ? document.getElementById(cameraSelectId) : null;
      if (cameraSelect && cameras.length > 1) {
        cameraSelect.innerHTML = cameras
          .map((c, i) => '<option value="' + escapeHtml(c.id) + '">' + escapeHtml(c.label || 'Camera ' + (i + 1)) + '</option>')
          .join('');
        cameraSelect.value = currentCameraId || cameras[cameras.length - 1].id;
        cameraSelect.closest('[data-scan-toolbar]')?.removeAttribute('hidden');
        cameraSelect.addEventListener('change', () => start(cameraSelect.value));
      }

      document.getElementById(restartBtnId || '')?.addEventListener('click', () => restart());
      document.getElementById(stopBtnId || '')?.addEventListener('click', async () => {
        await stop();
        setStatus('stopped', 'Camera stopped');
      });
    }

    function registerCleanup() {
      const cleanup = () => stop();
      if (window.App?.registerPageCleanup) {
        App.registerPageCleanup(cleanup);
      }
      window.addEventListener('beforeunload', cleanup);
      window.addEventListener('pagehide', cleanup);
    }

    return {
      init,
      start,
      stop,
      restart,
      bindControls,
      registerCleanup,
      getCameras: () => cameras,
      getScanner: () => scanner,
      isScanning: () => scanning,
      getStatus: () => status,
    };
  }

  function statusLabel(status) {
    const map = {
      initializing: 'Initializing',
      ready: 'Camera Ready',
      unavailable: 'Camera Unavailable',
      denied: 'Camera Blocked',
      failed: 'Camera Failed',
      stopped: 'Camera Stopped',
      offline: 'Offline',
    };
    return map[status] || 'Camera';
  }

  function statusClass(status) {
    if (status === 'ready') return 'scan-status--ready';
    if (status === 'initializing') return 'scan-status--initializing';
    if (['unavailable', 'denied', 'failed', 'offline'].includes(status)) return 'scan-status--unavailable';
    return '';
  }

  function updateStatusEl(el, status, message) {
    if (!el) return;
    el.className = 'scan-status ' + statusClass(status);
    const dot = el.querySelector('.scan-status__dot');
    const text = el.querySelector('.scan-status__text');
    if (text) text.textContent = message || statusLabel(status);
    if (dot) dot.setAttribute('aria-hidden', 'true');
    el.setAttribute('role', 'status');
  }

  window.ScanningUI = {
    escapeHtml,
    createCameraManager,
    statusLabel,
    statusClass,
    updateStatusEl,
  };
})();
