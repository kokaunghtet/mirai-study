{{-- Floating focus pill — shows the live pomodoro session on every page
     except the timer itself. State lives in localStorage (FocusSession in
     app.js); clicking the pill returns to the timer, the speaker button
     restarts the session's ambient sound after a navigation. --}}
<div x-data="focusPill()"
     x-show="visible"
     x-cloak
     x-transition.opacity
     data-uid="{{ auth()->id() ?? 0 }}"
     class="fixed bottom-24 lg:bottom-5 right-4 z-40">
    <div class="flex items-center gap-1 pl-3.5 pr-1.5 py-1.5 rounded-full bg-surface border border-line shadow-lg">
        <a href="{{ route('timer.index') }}" class="flex items-center gap-2.5 min-w-0" title="Back to Focus timer">
            {{-- Status dot: pulsing accent while running, amber when paused --}}
            <span class="relative flex h-2.5 w-2.5 shrink-0">
                <span x-show="running" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-accent opacity-60"></span>
                <span class="relative inline-flex rounded-full h-2.5 w-2.5"
                      :class="running ? 'bg-accent' : 'bg-amber-400'"></span>
            </span>
            <span class="text-xs font-medium text-muted whitespace-nowrap" x-text="phaseLabel"></span>
            <span class="text-sm font-semibold tabular-nums text-content" x-text="displayTime"></span>
        </a>
        <button type="button"
                x-show="hasSound"
                @click="toggleSound()"
                :title="audible ? 'Mute sound' : 'Resume sound'"
                class="w-8 h-8 grid place-items-center rounded-full text-muted hover:text-content hover:bg-surface-muted transition-colors">
            {{-- Icons wrapped in spans: Lucide swaps the <i> for an svg, so
                 Alpine's x-show must live on a stable parent element. --}}
            <span x-show="audible" class="inline-flex w-4 h-4">
                <i data-lucide="volume-2" class="w-4 h-4"></i>
            </span>
            <span x-show="!audible" class="inline-flex w-4 h-4">
                <i data-lucide="volume-x" class="w-4 h-4"></i>
            </span>
        </button>
    </div>
</div>
