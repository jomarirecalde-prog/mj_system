<div
    id="logout-confirmation-modal"
    class="logout-modal modal-backdrop"
    aria-hidden="true"
    hidden
>
    <div
        class="logout-modal__dialog modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="logout-modal-title"
        aria-describedby="logout-modal-desc"
        tabindex="-1"
    >
        <div class="logout-modal__icon" aria-hidden="true">
            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
        </div>
        <h2 class="logout-modal__title modal__title" id="logout-modal-title">Sign out?</h2>
        <p class="logout-modal__message text-muted" id="logout-modal-desc">
            Are you sure you want to sign out of your account?
        </p>
        <div class="logout-modal__actions">
            <button type="button" class="btn btn--ghost" id="logout-modal-cancel">Cancel</button>
            <button type="button" class="btn btn--danger" id="logout-modal-confirm">
                <span class="logout-modal__confirm-text">Sign Out</span>
                <span class="logout-modal__spinner spinner spinner--inline" hidden aria-hidden="true"></span>
            </button>
        </div>
    </div>
</div>
