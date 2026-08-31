(function () {
  'use strict';

  function setLoading(btn, loading, loadingText) {
    if (!btn) return;
    if (loading) {
      if (!btn.dataset.qsOriginalText) {
        btn.dataset.qsOriginalText = btn.textContent.trim();
      }
      btn.textContent = loadingText || 'Processing…';
    } else if (btn.dataset.qsOriginalText) {
      btn.textContent = btn.dataset.qsOriginalText;
    }
    btn.classList.toggle('is-loading', loading);
    btn.disabled = loading;
    btn.setAttribute('aria-busy', loading ? 'true' : 'false');
  }

  function initFormSubmit(root) {
    (root || document).querySelectorAll('form[data-qs-submit]').forEach(function (form) {
      if (form.dataset.qsBound) return;
      form.dataset.qsBound = '1';
      form.addEventListener('submit', function () {
        const btn = form.querySelector('[type="submit"]');
        setLoading(btn, true, btn?.dataset.qsLoadingText || 'Processing…');
      });
    });
  }

  function initDialogModals() {
    document.querySelectorAll('dialog.qs-modal').forEach(function (dialog) {
      const triggerSelector = dialog.dataset.qsTrigger;
      if (!triggerSelector) return;

      let lastTrigger = null;

      document.querySelectorAll(triggerSelector).forEach(function (trigger) {
        trigger.addEventListener('click', function () {
          lastTrigger = trigger;
          dialog.showModal();
          const focusTarget = dialog.querySelector('[data-qs-modal-focus]') || dialog.querySelector('.qs-modal__close');
          focusTarget?.focus();
        });
      });

      dialog.querySelectorAll('[data-qs-close-modal]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          dialog.close();
        });
      });

      dialog.addEventListener('close', function () {
        if (lastTrigger) {
          lastTrigger.focus();
          lastTrigger = null;
        }
      });

      dialog.addEventListener('cancel', function (e) {
        e.preventDefault();
        dialog.close();
      });
    });
  }

  function initActionMenus() {
    document.querySelectorAll('.qs-menu').forEach(function (menu) {
      const toggle = menu.querySelector('.qs-menu__toggle');
      if (!toggle) return;

      toggle.addEventListener('click', function (e) {
        e.stopPropagation();
        const isOpen = menu.classList.contains('is-open');
        document.querySelectorAll('.qs-menu.is-open').forEach(function (m) {
          if (m !== menu) m.classList.remove('is-open');
        });
        menu.classList.toggle('is-open', !isOpen);
        toggle.setAttribute('aria-expanded', !isOpen ? 'true' : 'false');
      });
    });

    document.addEventListener('click', function () {
      document.querySelectorAll('.qs-menu.is-open').forEach(function (menu) {
        menu.classList.remove('is-open');
        menu.querySelector('.qs-menu__toggle')?.setAttribute('aria-expanded', 'false');
      });
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        document.querySelectorAll('.qs-menu.is-open').forEach(function (menu) {
          menu.classList.remove('is-open');
          menu.querySelector('.qs-menu__toggle')?.setAttribute('aria-expanded', 'false');
        });
      }
    });
  }

  function initMobileFilters() {
    const toggle = document.getElementById('qs-filters-mobile-toggle');
    const drawer = document.getElementById('qs-filters-drawer');
    const backdrop = document.getElementById('qs-filters-drawer-backdrop');
    const applyBtn = document.getElementById('qs-filters-mobile-apply');
    const form = document.getElementById('qs-filters-form');

    if (!toggle || !drawer || !form) return;

    function openDrawer() {
      drawer.classList.add('is-open');
      backdrop?.classList.add('is-visible');
      document.body.style.overflow = 'hidden';
      toggle.setAttribute('aria-expanded', 'true');
    }

    function closeDrawer() {
      drawer.classList.remove('is-open');
      backdrop?.classList.remove('is-visible');
      document.body.style.overflow = '';
      toggle.setAttribute('aria-expanded', 'false');
    }

    toggle.addEventListener('click', openDrawer);
    backdrop?.addEventListener('click', closeDrawer);

    applyBtn?.addEventListener('click', function () {
      drawer.querySelectorAll('[data-qs-sync]').forEach(function (el) {
        const name = el.getAttribute('data-qs-sync');
        const target = form.querySelector('[name="' + name + '"]');
        if (target) target.value = el.value;
      });
      closeDrawer();
      form.submit();
    });
  }

  function initPasswordFields(root) {
    (root || document).addEventListener('click', async function (e) {
      const gen = e.target.closest('.js-qs-generate-password');
      if (gen) {
        e.preventDefault();
        const target = document.getElementById(gen.dataset.target);
        const generateUrl = gen.dataset.generateUrl;
        if (!target || !generateUrl) return;

        try {
          const res = await fetch(generateUrl, {
            headers: {
              Accept: 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            credentials: 'same-origin',
          });
          if (!res.ok) return;
          const data = await res.json();
          if (data.password) {
            target.value = data.password;
            target.type = 'text';
            const toggle = document.querySelector('.js-qs-toggle-password[data-target="' + gen.dataset.target + '"]');
            if (toggle) toggle.textContent = 'Hide';
          }
        } catch (err) {
          /* ignore */
        }
        return;
      }

      const toggleBtn = e.target.closest('.js-qs-toggle-password');
      if (toggleBtn) {
        e.preventDefault();
        const input = document.getElementById(toggleBtn.dataset.target);
        if (!input) return;
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        toggleBtn.textContent = show ? 'Hide' : 'Show';
        toggleBtn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        return;
      }

      const copyBtn = e.target.closest('.js-qs-copy-password');
      if (copyBtn) {
        e.preventDefault();
        const source = document.getElementById(copyBtn.dataset.target);
        const text = source?.value || source?.textContent?.trim();
        if (!text) return;
        try {
          await navigator.clipboard.writeText(text);
          const original = copyBtn.textContent;
          copyBtn.textContent = 'Copied';
          setTimeout(function () {
            copyBtn.textContent = original;
          }, 1500);
        } catch (err) {
          /* ignore */
        }
      }
    });
  }

  function initEditFromQuery(config) {
    if (!config?.editId) return;
    const btn = document.querySelector('[data-station-id="' + config.editId + '"]');
    if (btn) btn.click();
  }

  function initStationCodeUppercase(root) {
    (root || document).querySelectorAll('[data-qs-uppercase]').forEach(function (input) {
      input.addEventListener('input', function () {
        const pos = input.selectionStart;
        input.value = input.value.toUpperCase();
        input.setSelectionRange(pos, pos);
      });
    });
  }

  window.QrStations = {
    initIndex: function (config) {
      initFormSubmit(document);
      initDialogModals();
      initActionMenus();
      initMobileFilters();
      initPasswordFields(document);
      initStationCodeUppercase(document);

      document.getElementById('open-create-station')?.addEventListener('click', function () {
        document.getElementById('create-station-modal')?.showModal();
      });

      document.querySelectorAll('.js-qs-edit-station').forEach(function (btn) {
        btn.addEventListener('click', function () {
          const station = JSON.parse(btn.dataset.station);
          const editForm = document.getElementById('edit-station-form');
          const editBody = document.getElementById('edit-station-body');
          const editModal = document.getElementById('edit-station-modal');
          if (!editForm || !editBody || !editModal || !config.formTemplate) return;

          editForm.action = config.updateBaseUrl + '/' + station.id;
          editBody.innerHTML = config.formTemplate;
          Object.entries(station).forEach(function (entry) {
            const field = editBody.querySelector('[name="' + entry[0] + '"]');
            if (field) field.value = entry[1] ?? '';
          });
          const pw = editBody.querySelector('[name="password"]');
          if (pw) {
            pw.value = '';
            pw.placeholder = 'Leave blank to keep current password';
            pw.removeAttribute('required');
          }
          initFormSubmit(editBody);
          initStationCodeUppercase(editBody);
          editModal.showModal();
        });
      });

      if (config.openCreateOnError) {
        document.getElementById('create-station-modal')?.showModal();
      }

      initEditFromQuery(config);
    },

    initShow: function () {
      initFormSubmit(document);
      initPasswordFields(document);

      const triggerMap = new Map();

      document.querySelectorAll('[data-qs-confirm]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          triggerMap.set(btn.dataset.qsConfirm, btn);
          const dialog = document.getElementById('confirm-' + btn.dataset.qsConfirm);
          dialog?.showModal();
          (dialog?.querySelector('[data-qs-modal-focus]') || dialog?.querySelector('[data-qs-close-modal]'))?.focus();
        });
      });

      document.querySelectorAll('[data-qs-close-modal]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          btn.closest('dialog')?.close();
        });
      });

      document.querySelectorAll('dialog.qs-modal').forEach(function (dialog) {
        dialog.addEventListener('close', function () {
          const id = dialog.id.replace(/^confirm-/, '');
          const trigger = triggerMap.get(id);
          if (trigger) {
            trigger.focus();
            triggerMap.delete(id);
          }
        });
        dialog.addEventListener('cancel', function (e) {
          e.preventDefault();
          dialog.close();
        });
      });
    },
  };
})();
