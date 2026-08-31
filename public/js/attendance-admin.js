(function () {
  'use strict';

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function scrollToFirstError(root) {
    const scope = root || document;
    const first = scope.querySelector('.form-control.is-invalid, .form-select.is-invalid, .form-textarea.is-invalid, .is-invalid');
    if (first) {
      first.scrollIntoView({ behavior: 'smooth', block: 'center' });
      if (first.focus) first.focus({ preventScroll: true });
    }
  }

  function setLoading(btn, loading) {
    if (!btn) return;
    btn.classList.toggle('is-loading', loading);
    btn.disabled = loading;
    btn.setAttribute('aria-busy', loading ? 'true' : 'false');
  }

  function initFormSubmit(root) {
    (root || document).querySelectorAll('form[data-aa-submit]').forEach(function (form) {
      if (form.dataset.aaBound) return;
      form.dataset.aaBound = '1';
      form.addEventListener('submit', function () {
        const btn = form.querySelector('[type="submit"]');
        setLoading(btn, true);
      });
    });
    scrollToFirstError(root);
  }

  /* Filters */
  function initFilters(config) {
    const form = document.getElementById(config.formId);
    if (!form) return;

    const toggle = document.getElementById(config.toggleId);
    const clearBtn = document.getElementById(config.clearId);
    const advanced = document.getElementById(config.advancedId);
    const drawer = document.getElementById(config.drawerId);
    const backdrop = document.getElementById(config.backdropId);
    const mobileApply = document.getElementById(config.mobileApplyId);
    const MOBILE = window.matchMedia('(max-width: 768px)');

    function openDrawer() {
      drawer?.classList.add('is-open');
      backdrop?.classList.add('is-visible');
      document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
      drawer?.classList.remove('is-open');
      backdrop?.classList.remove('is-visible');
      document.body.style.overflow = '';
    }

    toggle?.addEventListener('click', function () {
      if (MOBILE.matches) {
        openDrawer();
        return;
      }
      const open = advanced?.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      toggle.classList.toggle('is-active', !!open);
    });

    backdrop?.addEventListener('click', closeDrawer);

    clearBtn?.addEventListener('click', function () {
      form.reset();
      form.querySelectorAll('input[type="search"], input[type="date"], input[type="month"], select').forEach(function (el) {
        if (el.tagName === 'SELECT') {
          el.selectedIndex = 0;
        } else {
          el.value = '';
        }
      });
      window.location.href = form.getAttribute('action') || window.location.pathname;
    });

    mobileApply?.addEventListener('click', function () {
      drawer?.querySelectorAll('[data-aa-sync]').forEach(function (el) {
        const name = el.getAttribute('data-aa-sync');
        const target = form.querySelector('[name="' + name + '"]');
        if (target) target.value = el.value;
      });
      closeDrawer();
      form.submit();
    });

    if (form.querySelector('[name="search"]')?.value || form.querySelector('[name="status"]')?.value) {
      advanced?.classList.add('is-open');
      toggle?.setAttribute('aria-expanded', 'true');
      toggle?.classList.add('is-active');
    }
  }

  /* Modal focus trap */
  function initModal(modalId, options) {
    const backdrop = document.getElementById(modalId);
    if (!backdrop) return null;

    const dialog = backdrop.querySelector('[role="dialog"]');
    let trigger = null;
    let focusables = [];

    function refreshFocusables() {
      focusables = Array.from(
        dialog.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])')
      ).filter(function (el) {
        return !el.disabled && el.offsetParent !== null;
      });
    }

    function open(data, btn) {
      trigger = btn;
      if (options.onOpen) options.onOpen(data, dialog, backdrop);
      backdrop.classList.add('is-open');
      backdrop.removeAttribute('hidden');
      backdrop.setAttribute('aria-hidden', 'false');
      refreshFocusables();
      (focusables[0] || dialog).focus();
    }

    function close() {
      backdrop.classList.remove('is-open');
      backdrop.setAttribute('hidden', '');
      backdrop.setAttribute('aria-hidden', 'true');
      if (trigger) trigger.focus();
    }

    backdrop.addEventListener('click', function (e) {
      if (e.target === backdrop) close();
    });

    dialog.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        e.preventDefault();
        close();
        return;
      }
      if (e.key !== 'Tab') return;
      refreshFocusables();
      if (!focusables.length) return;
      const first = focusables[0];
      const last = focusables[focusables.length - 1];
      if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus();
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
      }
    });

    backdrop.querySelectorAll('[data-aa-modal-close]').forEach(function (el) {
      el.addEventListener('click', close);
    });

    return { open: open, close: close, backdrop: backdrop, dialog: dialog };
  }

  /* Correction request review modal */
  function initCorrectionReview() {
    const modal = initModal('aa-review-modal', {
      onOpen: function (data, dialog) {
        const body = dialog.querySelector('[data-aa-review-body]');
        if (!body) return;

        body.innerHTML =
          '<dl class="aa-modal__section">' +
          row('Employee', escapeHtml(data.employee)) +
          row('Employee ID', escapeHtml(data.employeeId)) +
          row('Attendance Date', escapeHtml(data.date)) +
          row('Issue', escapeHtml(data.issue)) +
          row('Current Time In', escapeHtml(data.originalIn)) +
          row('Current Time Out', escapeHtml(data.originalOut)) +
          row('Requested', escapeHtml(data.requested)) +
          '</dl>' +
          '<div class="aa-modal__section"><p class="aa-modal__row"><dt style="margin:0 0 .35rem;color:var(--muted);">Employee reason</dt></p><p style="margin:0;font-size:.9rem;">' +
          escapeHtml(data.reason) +
          '</p></div>';

        const approveForm = dialog.querySelector('[data-aa-approve-form]');
        const rejectForm = dialog.querySelector('[data-aa-reject-form]');
        if (approveForm) approveForm.action = data.approveUrl;
        if (rejectForm) rejectForm.action = data.rejectUrl;

        const rejectInput = dialog.querySelector('[name="admin_remarks"][data-aa-reject-remarks]');
        if (rejectInput) {
          rejectInput.value = '';
          rejectInput.required = true;
        }
        const approveInput = dialog.querySelector('[name="admin_remarks"][data-aa-approve-remarks]');
        if (approveInput) approveInput.value = '';
      },
    });

    if (!modal) return;

    function row(label, value) {
      return (
        '<div class="aa-modal__row"><dt>' +
        label +
        '</dt><dd>' +
        value +
        '</dd></div>'
      );
    }

    document.querySelectorAll('[data-aa-review]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        try {
          const data = JSON.parse(btn.getAttribute('data-aa-review'));
          modal.open(data, btn);
        } catch (e) {
          /* ignore */
        }
      });
    });

    const confirmBackdrop = document.getElementById('aa-confirm-modal');
    if (confirmBackdrop) {
      let pendingForm = null;
      const confirmDialog = confirmBackdrop.querySelector('[role="dialog"]');
      const confirmTitle = confirmBackdrop.querySelector('[data-aa-confirm-title]');
      const confirmMessage = confirmBackdrop.querySelector('[data-aa-confirm-message]');
      const confirmBtn = confirmBackdrop.querySelector('[data-aa-confirm-yes]');

      function closeConfirm() {
        confirmBackdrop.classList.remove('is-open');
        confirmBackdrop.setAttribute('hidden', '');
      }

      function openConfirm(title, message, form) {
        pendingForm = form;
        confirmTitle.textContent = title;
        confirmMessage.textContent = message;
        confirmBackdrop.classList.remove('hidden');
        confirmBackdrop.classList.add('is-open');
        confirmBackdrop.removeAttribute('hidden');
        confirmBtn.focus();
      }

      confirmBackdrop.querySelectorAll('[data-aa-confirm-close]').forEach(function (el) {
        el.addEventListener('click', closeConfirm);
      });

      confirmBtn?.addEventListener('click', function () {
        if (pendingForm) {
          setLoading(pendingForm.querySelector('[type="submit"]'), true);
          pendingForm.submit();
        }
        closeConfirm();
      });

      modal.dialog.querySelector('[data-aa-approve-form]')?.addEventListener('submit', function (e) {
        e.preventDefault();
        const data = JSON.parse(document.querySelector('[data-aa-review].is-last-open')?.getAttribute('data-aa-review') || '{}');
        openConfirm(
          'Approve this correction request?',
          'Employee: ' + (data.employee || '') + ' · Date: ' + (data.date || '') + ' · Requested: ' + (data.requested || ''),
          e.target
        );
      });

      modal.dialog.querySelector('[data-aa-reject-form]')?.addEventListener('submit', function (e) {
        const input = e.target.querySelector('[data-aa-reject-remarks]');
        if (!input?.value.trim()) {
          e.preventDefault();
          input?.focus();
          input?.setAttribute('aria-invalid', 'true');
          return;
        }
        e.preventDefault();
        const data = JSON.parse(document.querySelector('[data-aa-review].is-last-open')?.getAttribute('data-aa-review') || '{}');
        openConfirm(
          'Reject this correction request?',
          'This will notify the employee. Reason: ' + input.value.trim(),
          e.target
        );
      });
    }

    document.querySelectorAll('[data-aa-review]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        document.querySelectorAll('[data-aa-review]').forEach(function (b) {
          b.classList.remove('is-last-open');
        });
        btn.classList.add('is-last-open');
      });
    });
  }

  /* Reason expand */
  function initReasonToggle() {
    document.querySelectorAll('[data-aa-reason-toggle]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const target = document.getElementById(btn.getAttribute('aria-controls'));
        if (!target) return;
        const expanded = btn.getAttribute('aria-expanded') === 'true';
        btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
        target.classList.toggle('aa-cell-clamp', expanded);
      });
    });
  }

  /* Export dropdown */
  function initExportDropdown() {
    document.querySelectorAll('[data-aa-export]').forEach(function (wrap) {
      const toggle = wrap.querySelector('[data-aa-export-toggle]');
      toggle?.addEventListener('click', function (e) {
        e.stopPropagation();
        const open = wrap.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
    });
    document.addEventListener('click', function () {
      document.querySelectorAll('[data-aa-export].is-open').forEach(function (el) {
        el.classList.remove('is-open');
        el.querySelector('[data-aa-export-toggle]')?.setAttribute('aria-expanded', 'false');
      });
    });
  }

  /* Shift template preview / sync */
  function initShiftTemplates() {
    const select = document.querySelector('[data-aa-shift-select]');
    const preview = document.querySelector('[data-aa-shift-preview]');
    if (!select) return;

    let shifts = {};
    try {
      shifts = JSON.parse(select.getAttribute('data-shifts') || '{}');
    } catch (e) {
      shifts = {};
    }

    function syncFields(shiftId, force) {
      const shift = shifts[shiftId];
      if (!shift) {
        if (preview) preview.hidden = true;
        return;
      }
      ['time_in', 'time_out', 'break_start', 'break_end'].forEach(function (field) {
        const input = document.querySelector('[name="' + field + '"]');
        if (!input) return;
        if (force || !input.dataset.aaTouched) {
          input.value = shift[field] || '';
        }
      });
      if (preview) {
        preview.hidden = false;
        preview.innerHTML =
          '<strong>Template:</strong> ' +
          escapeHtml(shift.name) +
          ' · ' +
          escapeHtml(shift.time_in) +
          ' → ' +
          escapeHtml(shift.time_out) +
          (shift.break_start ? ' · Break ' + escapeHtml(shift.break_start) + '–' + escapeHtml(shift.break_end) : '');
      }
    }

    select.addEventListener('change', function () {
      syncFields(select.value, true);
    });

    document.querySelectorAll('[name="time_in"], [name="time_out"], [name="break_start"], [name="break_end"]').forEach(function (input) {
      input.addEventListener('input', function () {
        input.dataset.aaTouched = '1';
      });
    });

    if (select.value) syncFields(select.value, false);
  }

  /* Schedule validation warnings */
  function initScheduleValidation() {
    const form = document.querySelector('[data-aa-schedule-form]');
    if (!form) return;

    const warnTime = form.querySelector('[data-aa-warn-time]');
    const warnBreak = form.querySelector('[data-aa-warn-break]');
    const warnDays = form.querySelector('[data-aa-warn-days]');

    function parseTime(value) {
      if (!value) return null;
      const parts = value.split(':');
      return parseInt(parts[0], 10) * 60 + parseInt(parts[1] || '0', 10);
    }

    function validate() {
      const timeIn = parseTime(form.querySelector('[name="time_in"]')?.value);
      const timeOut = parseTime(form.querySelector('[name="time_out"]')?.value);
      const breakStart = parseTime(form.querySelector('[name="break_start"]')?.value);
      const breakEnd = parseTime(form.querySelector('[name="break_end"]')?.value);

      if (warnTime) {
        const show = timeIn !== null && timeOut !== null && timeOut <= timeIn;
        warnTime.hidden = !show;
        warnTime.textContent = show
          ? 'Time Out is before or equal to Time In. Overnight shifts may be valid — verify before saving.'
          : '';
      }

      if (warnBreak) {
        const show = breakStart !== null && breakEnd !== null && breakEnd <= breakStart;
        warnBreak.hidden = !show;
        warnBreak.textContent = show ? 'Break End is before or equal to Break Start. Please verify.' : '';
      }

      if (warnDays) {
        const work = Array.from(form.querySelectorAll('[name="work_days[]"]:checked')).map(function (el) {
          return el.value;
        });
        const rest = Array.from(form.querySelectorAll('[name="rest_days[]"]:checked')).map(function (el) {
          return el.value;
        });
        const overlap = work.filter(function (d) {
          return rest.indexOf(d) !== -1;
        });
        warnDays.hidden = overlap.length === 0;
        warnDays.textContent =
          overlap.length > 0 ? 'Work days and rest days overlap on: ' + overlap.join(', ') + '.' : '';
      }
    }

    form.addEventListener('change', validate);
    form.addEventListener('input', validate);
    validate();
  }

  /* Dangerous action confirmations */
  function initDangerConfirm() {
    document.querySelectorAll('form[data-aa-confirm]').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        const message = form.getAttribute('data-aa-confirm');
        if (!message || form.dataset.aaConfirmed === '1') return;
        e.preventDefault();
        if (window.confirm(message)) {
          form.dataset.aaConfirmed = '1';
          setLoading(form.querySelector('[type="submit"]'), true);
          form.submit();
        }
      });
    });
  }

  function statusBadgeHtml(label) {
    const key = String(label || '')
      .toLowerCase()
      .replace(/\s+/g, '_');
    const map = {
      present: ['aa-dtr-badge--present', '✓'],
      late: ['aa-dtr-badge--late', '⚠'],
      absent: ['aa-dtr-badge--absent', '✕'],
      on_leave: ['aa-dtr-badge--leave', '▣'],
      official_business: ['aa-dtr-badge--leave', '▣'],
      half_day: ['aa-dtr-badge--warn', '⚠'],
      undertime: ['aa-dtr-badge--warn', '⚠'],
      incomplete: ['aa-dtr-badge--incomplete', '⚠'],
      rest_day: ['aa-dtr-badge--rest', '○'],
    };
    const cfg = map[key] || ['aa-dtr-badge--default', ''];
    const icon = cfg[1]
      ? '<span class="aa-dtr-badge__icon" aria-hidden="true">' + cfg[1] + '</span>'
      : '';
    return (
      '<span class="aa-dtr-badge ' +
      cfg[0] +
      '">' +
      icon +
      '<span class="aa-dtr-badge__text">' +
      escapeHtml(label || '—') +
      '</span></span>'
    );
  }

  function initLiveAttendance(config) {
    const liveUrl = config.url;
    const clockEl = document.getElementById(config.clockId);
    const tbody = config.tableBodyId ? document.getElementById(config.tableBodyId) : null;
    const cardsEl = config.cardsId ? document.getElementById(config.cardsId) : null;
    const countEl = config.countId ? document.getElementById(config.countId) : null;
    const updatedEl = config.updatedId ? document.getElementById(config.updatedId) : null;
    const statsRoot = config.statsRootId ? document.getElementById(config.statsRootId) : null;
    const emptyMsg = config.emptyMessage || 'No employees currently timed in.';
    const colSpan = config.colSpan || 5;

    async function refresh() {
      try {
        const res = await fetch(liveUrl, { headers: { Accept: 'application/json' } });
        const data = await res.json();
        if (!data.success) return;
        if (clockEl) clockEl.textContent = data.server_time;
        if (updatedEl) updatedEl.textContent = 'Last updated: ' + data.server_time;
        if (statsRoot && data.counts) {
          Object.entries(data.counts).forEach(function (entry) {
            document.querySelectorAll('[data-stat="' + entry[0] + '"]').forEach(function (el) {
              const key = entry[0];
              const val = entry[1];
              if (key === 'late' || key === 'incomplete') {
                el.textContent = '⚠ ' + val;
              } else if (key === 'absent') {
                el.textContent = '✕ ' + val;
              } else {
                el.textContent = val;
              }
            });
          });
        }
        const rows = data.currently_in || [];
        if (countEl) countEl.textContent = String(rows.length);
        const rowHtml = rows
          .map(function (r) {
            return (
              '<tr>' +
              '<td><div class="aa-cell-primary">' +
              escapeHtml(r.employee) +
              '</div>' +
              (r.employee_id
                ? '<div class="aa-cell-secondary">' + escapeHtml(r.employee_id) + '</div>'
                : '') +
              '</td>' +
              '<td>' +
              escapeHtml(r.department || '—') +
              '</td>' +
              '<td>' +
              escapeHtml(r.time_in) +
              '</td>' +
              '<td><span class="aa-duration">' +
              escapeHtml(r.duration) +
              '</span></td>' +
              '<td>' +
              statusBadgeHtml(r.status) +
              '</td></tr>'
            );
          })
          .join('');
        if (tbody) {
          tbody.innerHTML = rows.length
            ? rowHtml
            : '<tr><td colspan="' + colSpan + '" class="text-muted">' + escapeHtml(emptyMsg) + '</td></tr>';
        }
        if (cardsEl) {
          cardsEl.innerHTML = rows.length
            ? rows
                .map(function (r) {
                  return (
                    '<article class="aa-card-row"><div class="aa-card-row__head"><div><div class="aa-cell-primary">' +
                    escapeHtml(r.employee) +
                    '</div>' +
                    (r.employee_id
                      ? '<div class="aa-cell-secondary">' + escapeHtml(r.employee_id) + '</div>'
                      : '') +
                    '</div>' +
                    statusBadgeHtml(r.status) +
                    '</div><div class="aa-card-row__grid">' +
                    '<div><span class="aa-card-row__label">Department</span> ' +
                    escapeHtml(r.department || '—') +
                    '</div>' +
                    '<div><span class="aa-card-row__label">Time In</span> ' +
                    escapeHtml(r.time_in) +
                    '</div>' +
                    '<div><span class="aa-card-row__label">Duration</span> <span class="aa-duration">' +
                    escapeHtml(r.duration) +
                    '</span></div></div></article>'
                  );
                })
                .join('')
            : '<div class="aa-empty" style="padding:1.5rem;"><p class="aa-empty__text">' +
              escapeHtml(emptyMsg) +
              '</p></div>';
        }
      } catch (e) {
        /* ignore */
      }
    }

    refresh();
    setInterval(refresh, config.interval || 10000);
  }

  function initDtrRecords() {
    const form = document.getElementById('aa-filters-dtr');
    const tbody = document.getElementById('dtr-tbody');
    const cards = document.getElementById('dtr-cards');
    const tableWrap = document.getElementById('dtr-table-wrap');
    if (!form || !tbody) return;

    const baseUrl = form.getAttribute('action') || window.location.pathname;
    let timer = null;
    let abortCtrl = null;
    let reqId = 0;

    function params() {
      const fd = new FormData(form);
      const q = new URLSearchParams(fd);
      q.set('ajax', '1');
      return q.toString();
    }

    function setLoading(on) {
      if (tableWrap) tableWrap.classList.toggle('aa-table-loading', on);
      if (cards) cards.classList.toggle('aa-table-loading', on);
    }

    function renderRows(rows) {
      if (!rows.length) {
        tbody.innerHTML =
          '<tr><td colspan="14" class="text-muted">No DTR records found.</td></tr>';
        if (cards) cards.innerHTML = '<div class="aa-empty" style="padding:1.5rem;"><p class="aa-empty__text">No DTR records found.</p></div>';
        return;
      }
      tbody.innerHTML = rows
        .map(function (r) {
          return (
            '<tr><td>' +
            escapeHtml(r.date) +
            '</td><td>' +
            escapeHtml(r.employee_id) +
            '</td><td><span class="aa-cell-primary">' +
            escapeHtml(r.employee_name) +
            '</span></td><td>' +
            escapeHtml(r.department) +
            '</td><td>' +
            escapeHtml(r.schedule) +
            '</td><td>' +
            escapeHtml(r.time_in) +
            '</td><td>' +
            escapeHtml(r.time_out) +
            '</td><td>' +
            escapeHtml(r.total_hours) +
            '</td><td>' +
            escapeHtml(r.late) +
            '</td><td>' +
            escapeHtml(r.undertime) +
            '</td><td>' +
            escapeHtml(r.overtime) +
            '</td><td>' +
            statusBadgeHtml(r.status) +
            '</td><td>' +
            escapeHtml(r.remarks) +
            '</td><td><a class="btn btn--ghost btn--sm" href="' +
            escapeHtml(r.url) +
            '">View</a></td></tr>'
          );
        })
        .join('');

      if (cards) {
        cards.innerHTML = rows
          .map(function (r, i) {
            return (
              '<article class="aa-card-row aa-expand-card" data-aa-expand><button type="button" class="aa-expand-card__toggle" aria-expanded="false" aria-controls="dtr-card-' +
              i +
              '"><div class="aa-card-row__head"><div><div class="aa-cell-primary">' +
              escapeHtml(r.employee_name) +
              '</div><div class="aa-cell-secondary">' +
              escapeHtml(r.date) +
              ' · ' +
              escapeHtml(r.employee_id) +
              '</div></div><div style="display:flex;align-items:center;gap:.5rem;">' +
              statusBadgeHtml(r.status) +
              '<span class="aa-expand-card__chevron" aria-hidden="true">▼</span></div></div></button><div class="aa-card-row__grid"><div><span class="aa-card-row__label">Schedule</span> ' +
              escapeHtml(r.schedule) +
              '</div><div><span class="aa-card-row__label">Time In / Out</span> ' +
              escapeHtml(r.time_in) +
              ' → ' +
              escapeHtml(r.time_out) +
              '</div><div><span class="aa-card-row__label">Hours</span> ' +
              escapeHtml(r.total_hours) +
              '</div></div><div class="aa-expand-card__details" id="dtr-card-' +
              i +
              '"><div><span class="aa-card-row__label">Late</span> ' +
              escapeHtml(r.late) +
              '</div><div><span class="aa-card-row__label">Undertime</span> ' +
              escapeHtml(r.undertime) +
              '</div><div><span class="aa-card-row__label">Overtime</span> ' +
              escapeHtml(r.overtime) +
              '</div><div><span class="aa-card-row__label">Remarks</span> ' +
              escapeHtml(r.remarks) +
              '</div><a class="btn btn--primary btn--sm" style="margin-top:.5rem;" href="' +
              escapeHtml(r.url) +
              '">View Record</a></div></article>'
            );
          })
          .join('');
        initExpandCards(cards);
      }
    }

    async function liveSearch() {
      const current = ++reqId;
      if (abortCtrl) abortCtrl.abort();
      abortCtrl = new AbortController();
      setLoading(true);
      try {
        const res = await fetch(baseUrl + '?' + params(), {
          headers: { Accept: 'application/json' },
          signal: abortCtrl.signal,
        });
        const data = await res.json();
        if (current !== reqId) return;
        if (!data.success) return;
        renderRows(data.records || []);
      } catch (e) {
        if (e.name === 'AbortError') return;
      } finally {
        if (current === reqId) setLoading(false);
      }
    }

    const searchInput = document.getElementById('dtr-search');
    searchInput?.addEventListener('input', function () {
      clearTimeout(timer);
      timer = setTimeout(liveSearch, 280);
    });
    ['dtr-employee', 'dtr-dept', 'dtr-from', 'dtr-to', 'dtr-status'].forEach(function (id) {
      document.getElementById(id)?.addEventListener('change', liveSearch);
    });
  }

  function initExpandCards(root) {
    (root || document).querySelectorAll('[data-aa-expand]').forEach(function (card) {
      if (card.dataset.aaExpandBound) return;
      card.dataset.aaExpandBound = '1';
      const btn = card.querySelector('.aa-expand-card__toggle');
      btn?.addEventListener('click', function () {
        const open = card.classList.toggle('is-open');
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
    });
  }

  window.AttendanceUI = {
    escapeHtml: escapeHtml,
    statusBadgeHtml: statusBadgeHtml,
    initLiveAttendance: initLiveAttendance,
    initDtrRecords: initDtrRecords,
    initExpandCards: initExpandCards,
    setLoading: setLoading,
  };

  function init() {
    initFormSubmit();
    initCorrectionReview();
    initReasonToggle();
    initExportDropdown();
    initShiftTemplates();
    initScheduleValidation();
    initDangerConfirm();

    initFilters({
      formId: 'aa-filters-requests',
      toggleId: 'aa-filters-requests-toggle',
      clearId: 'aa-filters-requests-clear',
      advancedId: 'aa-filters-requests-advanced',
      drawerId: 'aa-filters-requests-mobile',
      backdropId: 'aa-filters-requests-backdrop',
      mobileApplyId: 'aa-filters-requests-mobile-apply',
    });

    initFilters({
      formId: 'aa-filters-corrections',
      toggleId: 'aa-filters-corrections-toggle',
      clearId: 'aa-filters-corrections-clear',
      advancedId: 'aa-filters-corrections-advanced',
      drawerId: 'aa-filters-corrections-mobile',
      backdropId: 'aa-filters-corrections-backdrop',
      mobileApplyId: 'aa-filters-corrections-mobile-apply',
    });

    initFilters({
      formId: 'aa-filters-qr',
      toggleId: 'aa-filters-qr-toggle',
      clearId: 'aa-filters-qr-clear',
      advancedId: 'aa-filters-qr-advanced',
      drawerId: 'aa-filters-qr-mobile',
      backdropId: 'aa-filters-qr-backdrop',
      mobileApplyId: 'aa-filters-qr-mobile-apply',
    });

    initFilters({
      formId: 'aa-filters-schedules',
      toggleId: 'aa-filters-schedules-toggle',
      clearId: 'aa-filters-schedules-clear',
      advancedId: 'aa-filters-schedules-advanced',
      drawerId: 'aa-filters-schedules-mobile',
      backdropId: 'aa-filters-schedules-backdrop',
      mobileApplyId: 'aa-filters-schedules-mobile-apply',
    });

    initFilters({
      formId: 'aa-filters-report',
      toggleId: 'aa-filters-report-toggle',
      clearId: 'aa-filters-report-clear',
      advancedId: 'aa-filters-report-advanced',
      drawerId: 'aa-filters-report-mobile',
      backdropId: 'aa-filters-report-backdrop',
      mobileApplyId: 'aa-filters-report-mobile-apply',
    });

    initFilters({
      formId: 'aa-filters-today',
      toggleId: 'aa-filters-today-toggle',
      clearId: 'aa-filters-today-clear',
      advancedId: 'aa-filters-today-advanced',
      drawerId: 'aa-filters-today-mobile',
      backdropId: 'aa-filters-today-backdrop',
      mobileApplyId: 'aa-filters-today-mobile-apply',
    });

    initFilters({
      formId: 'aa-filters-dtr',
      toggleId: 'aa-filters-dtr-toggle',
      clearId: 'aa-filters-dtr-clear',
      advancedId: 'aa-filters-dtr-advanced',
      drawerId: 'aa-filters-dtr-mobile',
      backdropId: 'aa-filters-dtr-backdrop',
      mobileApplyId: 'aa-filters-dtr-mobile-apply',
    });

    initFilters({
      formId: 'aa-filters-audit',
      toggleId: 'aa-filters-audit-toggle',
      clearId: 'aa-filters-audit-clear',
      advancedId: 'aa-filters-audit-advanced',
      drawerId: 'aa-filters-audit-mobile',
      backdropId: 'aa-filters-audit-backdrop',
      mobileApplyId: 'aa-filters-audit-mobile-apply',
    });

    initDtrRecords();
    initExpandCards();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
