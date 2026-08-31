(function () {
    'use strict';

    function initConfirmForms() {
        var modal = document.getElementById('ot-confirm-modal');
        if (!modal) return;

        var messageEl = modal.querySelector('[data-ot-confirm-message]');
        var yesBtn = modal.querySelector('[data-ot-confirm-yes]');
        var closeBtn = modal.querySelector('[data-ot-confirm-close]');
        var pendingForm = null;

        function openModal(message, form) {
            pendingForm = form;
            if (messageEl) messageEl.textContent = message;
            modal.hidden = false;
            modal.setAttribute('aria-hidden', 'false');
        }

        function closeModal() {
            pendingForm = null;
            modal.hidden = true;
            modal.setAttribute('aria-hidden', 'true');
        }

        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModal();
        });

        if (yesBtn) {
            yesBtn.addEventListener('click', function () {
                if (pendingForm) {
                    pendingForm.dataset.confirmed = '1';
                    pendingForm.requestSubmit();
                }
                closeModal();
            });
        }

        var approveForm = document.querySelector('[data-ot-confirm-approve]');
        if (approveForm) {
            approveForm.addEventListener('submit', function (e) {
                if (approveForm.dataset.confirmed === '1') {
                    approveForm.dataset.confirmed = '';
                    setSubmitting(approveForm);
                    return;
                }
                e.preventDefault();
                openModal('Approve this Official Time Request? The employee schedule will be updated for the approved period.', approveForm);
            });
        }

        var rejectForm = document.querySelector('[data-ot-confirm-reject]');
        if (rejectForm) {
            rejectForm.addEventListener('submit', function (e) {
                var remarks = rejectForm.querySelector('[name="admin_remarks"]');
                if (remarks && !remarks.value.trim()) {
                    e.preventDefault();
                    remarks.focus();
                    return;
                }
                if (rejectForm.dataset.confirmed === '1') {
                    rejectForm.dataset.confirmed = '';
                    setSubmitting(rejectForm);
                    return;
                }
                e.preventDefault();
                openModal('Reject this Official Time Request? A rejection reason is required.', rejectForm);
            });
        }
    }

    function setSubmitting(form) {
        var btn = form.querySelector('[data-ot-submit]');
        if (!btn) return;
        btn.disabled = true;
        var text = btn.querySelector('.ot-btn-text');
        var loading = btn.querySelector('.ot-btn-loading');
        if (text) text.hidden = true;
        if (loading) loading.hidden = false;
    }

    document.addEventListener('DOMContentLoaded', initConfirmForms);
})();
