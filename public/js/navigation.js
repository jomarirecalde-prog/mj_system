(function () {
  'use strict';

  const NAV_HEADER = 'X-App-Navigation';
  const TRANSITION_MS = 200;
  const SLOW_THRESHOLD_MS = 600;
  const NO_CACHE_PREFIXES = [
    '/scan',
    '/attendance/scanner',
    '/pos',
    '/login',
    '/logout',
    '/station',
    '/employee',
  ];

  let isNavigating = false;
  let currentAbortController = null;
  const pageCache = new Map();
  const scrollCache = new Map();
  const cleanupHandlers = new Set();

  const prefersReducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function normalizePath(pathname) {
    if (!pathname) return '/';
    const normalized = pathname.replace(/\/+$/, '');
    return normalized || '/';
  }

  function pathMatches(pathname, pattern) {
    const path = normalizePath(pathname);
    const raw = pattern.trim();
    if (!raw) return false;

    if (raw.endsWith('*')) {
      const prefix = normalizePath(raw.slice(0, -1));
      if (prefix === '/') return true;
      return path === prefix || path.startsWith(prefix + '/');
    }

    const target = normalizePath(raw);
    if (target === '/') return path === '/';
    return path === target || path.startsWith(target + '/');
  }

  function isSameOrigin(url) {
    try {
      return new URL(url, window.location.origin).origin === window.location.origin;
    } catch (_) {
      return false;
    }
  }

  function shouldInterceptLink(link, event) {
    if (!link || !link.href) return false;
    if (link.hasAttribute('download')) return false;
    if (link.target && link.target !== '_self') return false;
    if (link.dataset.noNavigate === 'true') return false;
    if (!isSameOrigin(link.href)) return false;
    if (event && (event.defaultPrevented || event.button !== 0)) return false;
    if (event && (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey)) return false;
    return true;
  }

  function shouldCache(url) {
    const path = normalizePath(new URL(url, window.location.origin).pathname);
    return !NO_CACHE_PREFIXES.some((prefix) => path === prefix || path.startsWith(prefix + '/'));
  }

  function getPageContent() {
    return document.getElementById('page-content');
  }

  function getProgressBar() {
    return document.getElementById('nav-progress');
  }

  function showNavigationProgress() {
    const bar = getProgressBar();
    if (!bar) return;
    const inner = bar.querySelector('.nav-progress__bar');
    bar.classList.add('is-active');
    bar.setAttribute('aria-hidden', 'false');
    if (inner) {
      inner.style.width = '0%';
      requestAnimationFrame(() => {
        inner.style.width = '30%';
      });
    }
  }

  function advanceNavigationProgress(percent) {
    const inner = getProgressBar()?.querySelector('.nav-progress__bar');
    if (inner) inner.style.width = percent + '%';
  }

  function hideNavigationProgress() {
    const bar = getProgressBar();
    if (!bar) return;
    const inner = bar.querySelector('.nav-progress__bar');
    if (inner) inner.style.width = '100%';
    setTimeout(() => {
      bar.classList.remove('is-active');
      bar.setAttribute('aria-hidden', 'true');
      if (inner) inner.style.width = '0%';
    }, TRANSITION_MS);
  }

  function registerPageCleanup(fn) {
    if (typeof fn === 'function') cleanupHandlers.add(fn);
  }

  function cleanupCurrentPage() {
    document.dispatchEvent(new CustomEvent('app:before-navigate', { cancelable: true }));

    cleanupHandlers.forEach((fn) => {
      try {
        fn();
      } catch (_) {
        /* ignore */
      }
    });
    cleanupHandlers.clear();

    document.querySelectorAll('video, audio').forEach((media) => {
      const stream = media.srcObject;
      if (stream && typeof stream.getTracks === 'function') {
        stream.getTracks().forEach((track) => track.stop());
      }
      media.srcObject = null;
    });
  }

  function updateActiveNavigation(url) {
    const pathname = normalizePath(new URL(url, window.location.origin).pathname);
    const sidebar = document.getElementById('app-sidebar');
    if (!sidebar) return;

    sidebar.querySelectorAll('.sidebar__link').forEach((link) => link.classList.remove('is-active'));
    sidebar.querySelectorAll('[data-nav-group]').forEach((group) => group.classList.remove('is-route-active'));

    let bestLink = null;
    let bestScore = -1;

    sidebar.querySelectorAll('.sidebar__link').forEach((link) => {
      const hrefPath = normalizePath(new URL(link.href, window.location.origin).pathname);
      const patterns = (link.dataset.navPaths || hrefPath).split(',').map((p) => p.trim());

      patterns.forEach((pattern) => {
        if (pathMatches(pathname, pattern)) {
          const score = pattern.replace(/\*$/, '').length;
          if (score > bestScore) {
            bestScore = score;
            bestLink = link;
          }
        }
      });
    });

    if (!bestLink) return;

    bestLink.classList.add('is-active');

    let ancestor = bestLink.parentElement;
    while (ancestor && ancestor !== sidebar) {
      if (ancestor.matches('[data-nav-group]')) {
        ancestor.classList.add('is-route-active');
        ancestor.classList.add('is-expanded');
        const trigger = ancestor.querySelector(':scope > [data-nav-trigger]');
        trigger?.setAttribute('aria-expanded', 'true');
      }
      ancestor = ancestor.parentElement;
    }

    const nav = sidebar.querySelector('.sidebar__nav');
    if (nav) {
      const linkRect = bestLink.getBoundingClientRect();
      const navRect = nav.getBoundingClientRect();
      if (linkRect.top < navRect.top || linkRect.bottom > navRect.bottom) {
        bestLink.scrollIntoView({ block: 'nearest', behavior: prefersReducedMotion() ? 'auto' : 'smooth' });
      }
    }
  }

  function updateDocumentTitle(doc) {
    const meta = doc.querySelector('meta[name="page-title"]');
    if (meta?.content) {
      document.title = meta.content;
    }
  }

  function injectStyles(doc) {
    doc.querySelectorAll('link[rel="stylesheet"]').forEach((link) => {
      const href = link.getAttribute('href');
      if (!href || document.querySelector('link[rel="stylesheet"][href="' + href + '"]')) return;
      document.head.appendChild(link.cloneNode(true));
    });
    doc.querySelectorAll('style').forEach((style) => {
      if (style.textContent.trim()) {
        document.head.appendChild(style.cloneNode(true));
      }
    });
  }

  function executeScripts(doc) {
    const scripts = Array.from(doc.querySelectorAll('script'));

    return scripts.reduce((chain, oldScript) => {
      return chain.then(() => {
        return new Promise((resolve) => {
          const script = document.createElement('script');

          Array.from(oldScript.attributes).forEach((attr) => {
            script.setAttribute(attr.name, attr.value);
          });

          if (oldScript.src) {
            const src = oldScript.getAttribute('src');
            const existing = src && document.querySelector('script[src="' + src + '"]');
            if (existing) {
              resolve();
              return;
            }
            script.onload = () => resolve();
            script.onerror = () => resolve();
            script.src = src;
            document.body.appendChild(script);
          } else {
            script.textContent = oldScript.textContent;
            document.body.appendChild(script);
            resolve();
          }
        });
      });
    }, Promise.resolve());
  }

  function initFlashAlerts(container) {
    container.querySelectorAll('.alert').forEach((alert) => {
      const text = alert.textContent.trim();
      if (!text || !window.App?.toast) return;
      let type = 'info';
      if (alert.classList.contains('alert--success')) type = 'success';
      else if (alert.classList.contains('alert--error')) type = 'error';
      else if (alert.classList.contains('alert--warn')) type = 'warn';
      window.App.toast(text, type);
      alert.remove();
    });

    container.querySelectorAll('[data-toast]').forEach((el) => {
      window.App?.toast(el.textContent.trim(), el.dataset.toastType || 'info');
      el.remove();
    });
  }

  function initializeNewPage(container, url) {
    window.App?.initPageContent?.(container);
    initFlashAlerts(container);

    document.dispatchEvent(
      new CustomEvent('app:navigated', {
        detail: { container, url },
      })
    );

    if (typeof window.initializePageModules === 'function') {
      window.initializePageModules(container);
    }
  }

  async function replacePageContent(html, url, options = {}) {
    const container = getPageContent();
    if (!container) {
      window.location.href = url;
      return;
    }

    const doc = new DOMParser().parseFromString(html, 'text/html');
    const newMain = doc.querySelector('#page-content') || doc.querySelector('main.page-content');

    if (!newMain) {
      window.location.href = url;
      return;
    }

    updateDocumentTitle(doc);
    injectStyles(doc);

    const reducedMotion = prefersReducedMotion();
    if (!reducedMotion) {
      container.classList.add('is-navigating-out');
      await new Promise((r) => setTimeout(r, TRANSITION_MS / 2));
    }

    container.innerHTML = newMain.innerHTML;
    container.classList.remove('is-navigating-out');

    if (!reducedMotion) {
      container.classList.add('is-navigating-in');
      setTimeout(() => container.classList.remove('is-navigating-in'), TRANSITION_MS);
    }

    await executeScripts(doc, document.body);
    initializeNewPage(container, url);

    if (options.scroll !== false) {
      if (typeof options.scroll === 'number') {
        window.scrollTo({ top: options.scroll, behavior: reducedMotion ? 'auto' : 'instant' });
      } else {
        window.scrollTo({ top: 0, behavior: reducedMotion ? 'auto' : 'instant' });
      }
    }
  }

  function isAuthRedirect(responseUrl) {
    const path = normalizePath(new URL(responseUrl, window.location.origin).pathname);
    return path === '/login' || path.startsWith('/employee/login') || path.startsWith('/station/login');
  }

  async function fetchPage(url) {
    if (currentAbortController) {
      currentAbortController.abort();
    }
    currentAbortController = new AbortController();

    const response = await fetch(url, {
      method: 'GET',
      headers: {
        Accept: 'text/html',
        'X-Requested-With': 'XMLHttpRequest',
        [NAV_HEADER]: 'true',
      },
      credentials: 'same-origin',
      signal: currentAbortController.signal,
    });

    if (response.redirected && isAuthRedirect(response.url)) {
      window.location.href = response.url;
      return null;
    }

    if (response.status === 401 || response.status === 403) {
      window.location.href = url;
      return null;
    }

    if (response.status === 419) {
      window.location.href = url;
      return null;
    }

    if (!response.ok) {
      throw new Error('Navigation failed with status ' + response.status);
    }

    const html = await response.text();

    if (!html.includes('id="page-content"')) {
      window.location.href = response.url || url;
      return null;
    }

    if (isAuthRedirect(response.url)) {
      window.location.href = response.url;
      return null;
    }

    return html;
  }

  async function navigateTo(url, options = {}) {
    const absoluteUrl = new URL(url, window.location.origin).href;
    const currentUrl = window.location.href;

    if (absoluteUrl === currentUrl && !options.force) return;

    if (isNavigating && !options.force) return;
    isNavigating = true;

    const fromPopstate = options.fromPopstate === true;
    if (!fromPopstate) {
      scrollCache.set(currentUrl, window.scrollY);
    }

    cleanupCurrentPage();
    showNavigationProgress();

    let slowTimer = null;
    const container = getPageContent();
    if (container) {
      slowTimer = setTimeout(() => {
        container.classList.add('is-nav-loading');
      }, SLOW_THRESHOLD_MS);
    }

    try {
      let html = null;

      if (!options.skipCache && shouldCache(absoluteUrl) && pageCache.has(absoluteUrl)) {
        html = pageCache.get(absoluteUrl);
        advanceNavigationProgress(70);
      } else {
        advanceNavigationProgress(45);
        html = await fetchPage(absoluteUrl);
        advanceNavigationProgress(85);
        if (html && shouldCache(absoluteUrl)) {
          pageCache.set(absoluteUrl, html);
        }
      }

      if (!html) return;

      if (!fromPopstate && !options.replaceState) {
        history.pushState({ appNavigation: true, url: absoluteUrl }, '', absoluteUrl);
      } else if (options.replaceState) {
        history.replaceState({ appNavigation: true, url: absoluteUrl }, '', absoluteUrl);
      }

      await replacePageContent(html, absoluteUrl, {
        scroll: fromPopstate ? scrollCache.get(absoluteUrl) ?? 0 : 0,
      });

      updateActiveNavigation(absoluteUrl);
    } catch (err) {
      if (err.name === 'AbortError') return;
      console.error('App navigation failed:', err);
      window.location.href = url;
    } finally {
      clearTimeout(slowTimer);
      container?.classList.remove('is-nav-loading');
      hideNavigationProgress();
      isNavigating = false;
      currentAbortController = null;
    }
  }

  function handleLinkClick(event) {
    const link = event.target.closest('#app-sidebar .sidebar__link');
    if (!link || !shouldInterceptLink(link, event)) return;

    event.preventDefault();

    const sidebar = document.getElementById('app-sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    if (sidebar?.classList.contains('is-open')) {
      sidebar.classList.remove('is-open');
      backdrop?.classList.remove('is-visible');
      document.body.classList.remove('sidebar-mobile-open');
      const toggle = document.getElementById('sidebar-toggle');
      toggle?.setAttribute('aria-expanded', 'false');
    }

    navigateTo(link.href);
  }

  function handlePopState(event) {
    const url = event.state?.url || window.location.href;
    navigateTo(url, { fromPopstate: true, force: true, skipCache: false });
  }

  function init() {
    const container = getPageContent();
    if (!container) return;

    history.replaceState({ appNavigation: true, url: window.location.href }, '', window.location.href);

    document.addEventListener('click', handleLinkClick);
    window.addEventListener('popstate', handlePopState);

    updateActiveNavigation(window.location.href);
  }

  window.AppNavigation = {
    init,
    navigateTo,
    fetchPage,
    updateActiveNavigation,
    cleanupCurrentPage,
    registerPageCleanup,
    showNavigationProgress,
    hideNavigationProgress,
  };

  window.App = window.App || {};
  window.App.registerPageCleanup = registerPageCleanup;
  window.App.navigateTo = navigateTo;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
