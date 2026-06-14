@props([
    'name' => 'code',
    'length' => 6,
    'autosubmit' => true,
    'autofocus' => true,
])

{{-- Six separate auto-advancing digit cells that feed one hidden field ({{ $name }}).
     Driven by the `otpInput` Alpine component in resources/js/app.js: type to advance,
     Backspace to go back, arrows to navigate, paste a full code to fill every cell. --}}
<div x-data="otpInput({ length: {{ (int) $length }}, autosubmit: {{ $autosubmit ? 'true' : 'false' }}, autofocus: {{ $autofocus ? 'true' : 'false' }} })"
     {{ $attributes->class(['flex items-center justify-center gap-2 sm:gap-3', 'otp-shake' => $errors->has($name)]) }}>

    <input type="hidden" name="{{ $name }}" data-otp-value :value="code">

    @for ($i = 0; $i < (int) $length; $i++)
        <input type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="1"
               data-otp-box
               :value="digits[{{ $i }}]"
               @input="onInput({{ $i }}, $event)"
               @keydown="onKeydown({{ $i }}, $event)"
               @paste.prevent="onPaste($event)"
               @focus="$event.target.select()"
               aria-label="Digit {{ $i + 1 }}"
               class="h-12 w-11 rounded-xl border border-line bg-surface text-center text-xl font-semibold text-content transition focus:scale-105 focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20 sm:h-14 sm:w-12">
    @endfor
</div>
