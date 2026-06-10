@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-line bg-surface text-content focus:border-accent focus:ring-accent rounded-md shadow-sm']) }}>
