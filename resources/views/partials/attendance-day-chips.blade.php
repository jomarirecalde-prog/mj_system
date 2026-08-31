@php
    $fmtTime = fn ($t) => $t ? \Carbon\Carbon::parse($t)->format('h:i A') : null;
@endphp
<div class="aa-day-chips" role="group" aria-label="{{ $label ?? 'Days' }}">
    @foreach($days as $num => $dayLabel)
        <label class="aa-day-chip {{ !empty($rest) ? 'aa-day-chip--rest' : '' }}">
            <input type="checkbox" name="{{ $name }}[]" value="{{ $num }}" @checked(in_array($num, $selected ?? [], true))>
            <span class="aa-day-chip__label">{{ $dayLabel }}</span>
        </label>
    @endforeach
</div>
