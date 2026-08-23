<form method="post" action="{{ route('station.login.submit') }}" class="lp-login__form" id="station-login-form" novalidate>
    @csrf

    <div class="lp-field">
        <label class="lp-label" for="station_code">Station ID</label>
        <div class="lp-control @error('station_code') lp-control--invalid @enderror">
            <span class="lp-control__icon" aria-hidden="true"><i class="fa-solid fa-tablet-screen-button"></i></span>
            <input
                type="text"
                name="station_code"
                id="station_code"
                class="lp-input"
                value="{{ old('station_code') }}"
                placeholder="STATION-001"
                required
                autocomplete="username"
                style="text-transform: uppercase"
                @error('station_code') aria-invalid="true" @enderror
            >
        </div>
        @error('station_code')
            <p class="lp-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="lp-field">
        <label class="lp-label" for="station_password">Station Password</label>
        <div class="lp-control @error('station_password') lp-control--invalid @enderror">
            <span class="lp-control__icon" aria-hidden="true"><i class="fa-solid fa-lock"></i></span>
            <input
                type="password"
                name="station_password"
                id="station_password"
                class="lp-input"
                placeholder="Enter station password"
                required
                autocomplete="current-password"
                @error('station_password') aria-invalid="true" @enderror
            >
            <button
                type="button"
                class="lp-toggle"
                id="toggle-station-password"
                aria-label="Show password"
                aria-controls="station_password"
                aria-pressed="false"
            >
                <i class="fa-regular fa-eye lp-toggle__show" aria-hidden="true"></i>
                <i class="fa-regular fa-eye-slash lp-toggle__hide" aria-hidden="true" hidden></i>
            </button>
        </div>
        @error('station_password')
            <p class="lp-error">{{ $message }}</p>
        @enderror
    </div>

    <button type="submit" class="lp-submit lp-submit--station" id="station-login-submit">
        <span class="lp-submit-idle">Sign in to Scanner</span>
        <span class="lp-submit-loading" aria-hidden="true">
            <span class="lp-spinner" aria-hidden="true"></span>
            Signing in...
        </span>
    </button>
</form>
