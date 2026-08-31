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
    document.body.classList.remove('lp-nav-open');
    if (panel && window.matchMedia('(max-width: 900px)').matches) {
      panel.setAttribute('hidden', '');
    }
  }

  function openNav() {
    if (!nav || !toggle) return;
    nav.classList.add('is-open');
    toggle.setAttribute('aria-expanded', 'true');
    toggle.setAttribute('aria-label', 'Close menu');
    document.body.classList.add('lp-nav-open');
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
        document.body.classList.remove('lp-nav-open');
        if (panel) panel.removeAttribute('hidden');
      } else if (!nav.classList.contains('is-open') && panel) {
        panel.setAttribute('hidden', '');
      }
    });
  }

  // Sticky nav shadow on scroll
  if (nav) {
    var scrollTicking = false;
    function updateNavScroll() {
      nav.classList.toggle('is-scrolled', window.scrollY > 8);
      scrollTicking = false;
    }
    window.addEventListener('scroll', function () {
      if (!scrollTicking) {
        scrollTicking = true;
        requestAnimationFrame(updateNavScroll);
      }
    }, { passive: true });
    updateNavScroll();
  }

  // Active section navigation indicator
  var navLinks = document.querySelectorAll('[data-nav-section]');
  var sections = [];

  navLinks.forEach(function (link) {
    var id = link.getAttribute('data-nav-section');
    var section = document.getElementById(id);
    if (section) sections.push({ id: id, el: section });
  });

  if (sections.length && navLinks.length) {
    function setActiveNav(sectionId) {
      navLinks.forEach(function (link) {
        var isActive = link.getAttribute('data-nav-section') === sectionId;
        link.classList.toggle('is-active', isActive);
        if (isActive) {
          link.setAttribute('aria-current', 'true');
        } else {
          link.removeAttribute('aria-current');
        }
      });
    }

    if ('IntersectionObserver' in window) {
      var visibleSections = {};
      var navHeight = '64px';
      if (nav) {
        var computedNavHeight = getComputedStyle(nav).getPropertyValue('--lp-nav-h').trim();
        if (computedNavHeight) navHeight = computedNavHeight;
      }
      var navObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          visibleSections[entry.target.id] = entry.isIntersecting ? entry.intersectionRatio : 0;
        });

        var bestId = null;
        var bestRatio = 0;
        sections.forEach(function (s) {
          var ratio = visibleSections[s.id] || 0;
          if (ratio > bestRatio) {
            bestRatio = ratio;
            bestId = s.id;
          }
        });

        if (bestId) setActiveNav(bestId);
      }, { rootMargin: '-' + navHeight + ' 0px -50% 0px', threshold: [0, 0.1, 0.25, 0.5] });

      sections.forEach(function (s) {
        navObserver.observe(s.el);
      });
    }
  }

  function focusLoginEmail() {
    var email = document.getElementById('email');
    if (email) {
      window.setTimeout(function () {
        email.focus({ preventScroll: true });
      }, reduceMotion ? 0 : 350);
    }
  }

  function focusStationCode() {
    var stationCode = document.getElementById('station_code');
    if (stationCode) {
      window.setTimeout(function () {
        stationCode.focus({ preventScroll: true });
      }, reduceMotion ? 0 : 350);
    }
  }

  function focusFirstInvalid() {
    var invalid = document.querySelector('.lp-control--invalid .lp-input, .lp-input[aria-invalid="true"]');
    if (invalid) {
      window.setTimeout(function () {
        invalid.focus({ preventScroll: true });
      }, reduceMotion ? 0 : 200);
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
    } else if (hash === '#qr-station') {
      focusStationCode();
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

  // Password visibility — inventory login
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

  // Station password visibility
  var stationToggleBtn = document.getElementById('toggle-station-password');
  var stationPasswordInput = document.getElementById('station_password');

  if (stationToggleBtn && stationPasswordInput) {
    stationToggleBtn.addEventListener('click', function () {
      var revealing = stationPasswordInput.getAttribute('type') === 'password';
      stationPasswordInput.setAttribute('type', revealing ? 'text' : 'password');
      stationToggleBtn.setAttribute('aria-pressed', revealing ? 'true' : 'false');
      stationToggleBtn.setAttribute('aria-label', revealing ? 'Hide password' : 'Show password');

      var showIcon = stationToggleBtn.querySelector('.lp-toggle__show');
      var hideIcon = stationToggleBtn.querySelector('.lp-toggle__hide');
      if (showIcon && hideIcon) {
        showIcon.hidden = revealing;
        hideIcon.hidden = !revealing;
      }
    });
  }

  // Station login submit loading
  var stationForm = document.getElementById('station-login-form');
  var stationSubmitBtn = document.getElementById('station-login-submit');

  if (stationForm && stationSubmitBtn) {
    stationForm.addEventListener('submit', function (event) {
      if (stationSubmitBtn.classList.contains('is-loading')) {
        event.preventDefault();
        return;
      }

      stationSubmitBtn.disabled = true;
      stationSubmitBtn.classList.add('is-loading');
      stationSubmitBtn.setAttribute('aria-busy', 'true');

      var loading = stationSubmitBtn.querySelector('.lp-submit-loading');
      if (loading) loading.removeAttribute('aria-hidden');
    });
  }

  // Validation errors / hash → stay on login or station section
  var hasErrors = document.body.classList.contains('has-login-errors');
  var hasStationErrors = document.body.classList.contains('has-station-errors');
  var hash = window.location.hash;

  if (hasStationErrors || hash === '#qr-station') {
    scrollToHash('#qr-station', false);
    if (hasStationErrors) focusFirstInvalid();
  } else if (hasErrors || hash === '#login') {
    scrollToHash('#login', true);
    if (hasErrors) focusFirstInvalid();
  } else if (hash && document.querySelector(hash)) {
    scrollToHash(hash, false);
  }

  // Gentle scroll reveal
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
