{{-- Reusable PWA install button (hidden until install is available) --}}
<button type="button" class="{{ $class ?? 'account-menu__item' }}" data-pwa-install hidden role="menuitem">
    @if(!empty($showIcon))
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
    @endif
    Install App
</button>
