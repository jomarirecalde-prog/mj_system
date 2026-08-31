(function () {
    'use strict';

    function formatTime12(value) {
        if (!value) return '—';
        var parts = value.split(':');
        if (parts.length < 2) return value;
        var h = parseInt(parts[0], 10);
        var m = parts[1];
        var ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12;
        if (h === 0) h = 12;
        return h + ':' + m + ' ' + ampm;
    }

    function initOfficialTimeForm() {
        var form = document.getElementById('ot-form');
        if (!form) return;

        var timeIn = document.getElementById('requested_time_in');
        var timeOut = document.getElementById('requested_time_out');
        var breakStart = document.getElementById('requested_break_start');
        var breakEnd = document.getElementById('requested_break_end');
        var isPermanent = document.getElementById('is_permanent');
        var effectiveTo = document.getElementById('effective_to');
        var effectiveToGroup = document.getElementById('ot-effective-to-group');

        var currentIn = document.getElementById('ot-current-in');
        var currentOut = document.getElementById('ot-current-out');
        var currentBreak = document.getElementById('ot-current-break');

        function updateSummary() {
            var inVal = timeIn ? timeIn.value : '';
            var outVal = timeOut ? timeOut.value : '';
            var bsVal = breakStart ? breakStart.value : '';
            var beVal = breakEnd ? breakEnd.value : '';

            var summaryIn = document.querySelector('[data-ot-summary="in"]');
            var summaryOut = document.querySelector('[data-ot-summary="out"]');
            var summaryBs = document.querySelector('[data-ot-summary="break-start"]');
            var summaryBe = document.querySelector('[data-ot-summary="break-end"]');

            if (summaryIn) summaryIn.textContent = formatTime12(inVal);
            if (summaryOut) summaryOut.textContent = formatTime12(outVal);
            if (summaryBs) summaryBs.textContent = bsVal ? formatTime12(bsVal) : '—';
            if (summaryBe) summaryBe.textContent = beVal ? formatTime12(beVal) : '—';

            var reqRange = document.getElementById('ot-compare-requested-range');
            var reqIn = document.getElementById('ot-compare-requested-in');
            var reqOut = document.getElementById('ot-compare-requested-out');
            var reqBreak = document.getElementById('ot-compare-requested-break');
            var highlights = document.getElementById('ot-comparison-highlights');

            if (reqRange) reqRange.textContent = formatTime12(inVal) + ' → ' + formatTime12(outVal);
            if (reqIn) reqIn.textContent = formatTime12(inVal);
            if (reqOut) reqOut.textContent = formatTime12(outVal);
            if (reqBreak) {
                reqBreak.textContent = bsVal && beVal
                    ? formatTime12(bsVal) + ' – ' + formatTime12(beVal)
                    : '—';
            }

            if (highlights && currentIn && currentOut) {
                var html = '';
                var curInText = currentIn.textContent.trim();
                var curOutText = currentOut.textContent.trim();
                var curBreakText = currentBreak ? currentBreak.textContent.trim() : '—';
                var newIn = formatTime12(inVal);
                var newOut = formatTime12(outVal);
                var newBreak = bsVal && beVal ? formatTime12(bsVal) + ' – ' + formatTime12(beVal) : '—';

                if (curInText !== newIn) {
                    html += '<div class="ot-comparison__highlight ot-comparison__highlight--changed">Time In: ' + curInText + ' → ' + newIn + '</div>';
                }
                if (curOutText !== newOut) {
                    html += '<div class="ot-comparison__highlight ot-comparison__highlight--changed">Time Out: ' + curOutText + ' → ' + newOut + '</div>';
                }
                if (curBreakText !== newBreak) {
                    html += '<div class="ot-comparison__highlight ot-comparison__highlight--changed">Break: ' + curBreakText + ' → ' + newBreak + '</div>';
                }
                highlights.innerHTML = html;
            }
        }

        function togglePermanent() {
            if (!isPermanent || !effectiveTo) return;
            var permanent = isPermanent.checked;
            effectiveTo.disabled = permanent;
            effectiveTo.required = !permanent;
            if (effectiveToGroup) {
                effectiveToGroup.style.opacity = permanent ? '0.5' : '1';
            }
            if (permanent) effectiveTo.value = '';
        }

        [timeIn, timeOut, breakStart, breakEnd].forEach(function (el) {
            if (el) el.addEventListener('input', updateSummary);
            if (el) el.addEventListener('change', updateSummary);
        });

        if (isPermanent) {
            isPermanent.addEventListener('change', togglePermanent);
            togglePermanent();
        }

        updateSummary();

        form.addEventListener('submit', function (e) {
            var from = document.getElementById('effective_from');
            var to = effectiveTo;
            var permanent = isPermanent && isPermanent.checked;

            if (from && to && !permanent && to.value && from.value > to.value) {
                e.preventDefault();
                alert('Effective To cannot be before Effective From.');
                to.focus();
                return;
            }

            if (breakStart && breakEnd && breakStart.value && breakEnd.value && breakStart.value >= breakEnd.value) {
                e.preventDefault();
                alert('Break end must be after break start.');
                breakEnd.focus();
                return;
            }

            form.classList.add('is-submitting');
            var btn = document.getElementById('ot-submit-btn');
            if (btn) btn.disabled = true;
        });

        var firstError = form.querySelector('.form-error');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    function initScrollToForm() {
        document.querySelectorAll('[data-ot-scroll-form]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var form = document.getElementById('ot-request-form');
                if (form) form.scrollIntoView({ behavior: 'smooth' });
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initOfficialTimeForm();
        initScrollToForm();
    });
})();
