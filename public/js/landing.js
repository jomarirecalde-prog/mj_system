(function () {
  'use strict';

  var nav = document.getElementById('lp-nav');
  var toggle = document.getElementById('lp-nav-toggle');
  var panel = document.getElementById('lp-nav-panel');
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function closeNav() {
    if (!nav || !toggle) return;
    nav.classList.remove('is-open');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', 'Open menu');
    if (panel) panel.setAttribute('hidden', '');
  }

  function openNav() {
    if (!nav || !toggle) return;
    nav.classList.add('is-open');
    toggle.setAttribute('aria-expanded', 'true');
    toggle.setAttribute('aria-label', 'Close menu');
    if (panel) panel.removeAttribute('hidden');
  }

  if (toggle && nav) {
    if (window.matchMedia('(max-width: 900px)').matches && panel) {
      panel.setAttribute('hidden', '');
    }

    toggle.addEventListener('click', function () {
      if (nav.classList.contains('is-open')) {
        closeNav();
      } else {
        openNav();
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') closeNav();
    });

    window.addEventListener('resize', function () {
      if (window.innerWidth > 900) {
        nav.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        if (panel) panel.removeAttribute('hidden');
      } else if (!nav.classList.contains('is-open') && panel) {
        panel.setAttribute('hidden', '');
      }
    });
  }

  function focusLoginEmail() {
    var email = document.getElementById('email');
    if (email) {
      window.setTimeout(function () {
        email.focus({ preventScroll: true });
      }, reduceMotion ? 0 : 350);
    }
  }

  function scrollToHash(hash, focusEmail) {
    if (!hash || hash === '#') return;
    var target = document.querySelector(hash);
    if (!target) return;

    closeNav();
    target.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });

    if (focusEmail || hash === '#login') {
      focusLoginEmail();
    }

    if (history.replaceState) {
      history.replaceState(null, '', hash);
    }
  }

  document.addEventListener('click', function (event) {
    var link = event.target.closest('a[href^="#"]');
    if (!link) return;

    var href = link.getAttribute('href');
    if (!href || href === '#') return;

    var target = document.querySelector(href);
    if (!target) return;

    event.preventDefault();
    scrollToHash(href, href === '#login' || link.hasAttribute('data-focus-login'));
  });

  // Password visibility
  var toggleBtn = document.getElementById('toggle-password');
  var passwordInput = document.getElementById('password');

  if (toggleBtn && passwordInput) {
    toggleBtn.addEventListener('click', function () {
      var revealing = passwordInput.getAttribute('type') === 'password';
      passwordInput.setAttribute('type', revealing ? 'text' : 'password');
      toggleBtn.setAttribute('aria-pressed', revealing ? 'true' : 'false');
      toggleBtn.setAttribute('aria-label', revealing ? 'Hide password' : 'Show password');

      var showIcon = toggleBtn.querySelector('.lp-toggle__show');
      var hideIcon = toggleBtn.querySelector('.lp-toggle__hide');
      if (showIcon && hideIcon) {
        showIcon.hidden = revealing;
        hideIcon.hidden = !revealing;
      }
    });
  }

  // Login submit loading / prevent double submit
  var form = document.getElementById('login-form');
  var submitBtn = document.getElementById('login-submit');

  if (form && submitBtn) {
    form.addEventListener('submit', function (event) {
      if (submitBtn.classList.contains('is-loading')) {
        event.preventDefault();
        return;
      }

      submitBtn.disabled = true;
      submitBtn.classList.add('is-loading');
      submitBtn.setAttribute('aria-busy', 'true');

      var loading = submitBtn.querySelector('.lp-submit-loading');
      if (loading) loading.removeAttribute('aria-hidden');
    });
  }

  // Validation errors / hash → stay on login
  var hasErrors = document.body.classList.contains('has-login-errors');
  var hash = window.location.hash;

  if (hasErrors || hash === '#login') {
    scrollToHash('#login', true);
  } else if (hash && document.querySelector(hash)) {
    scrollToHash(hash, false);
  }

  // Gentle reveal
  if (!reduceMotion && 'IntersectionObserver' in window) {
    var items = document.querySelectorAll('.lp-reveal');
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    items.forEach(function (item) {
      observer.observe(item);
    });
  } else {
    document.querySelectorAll('.lp-reveal').forEach(function (item) {
      item.classList.add('is-visible');
    });
  }
})();
