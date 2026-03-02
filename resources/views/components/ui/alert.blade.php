@props(['type' => 'info', 'dismissible' => false, 'title' => null])

@php
    $styles = match($type) {
        'success' => 'bg-green-50 border-green-200 text-green-800 dark:bg-green-900/30 dark:border-green-800 dark:text-green-300',
        'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800 dark:bg-yellow-900/30 dark:border-yellow-800 dark:text-yellow-300',
        'error'   => 'bg-red-50 border-red-200 text-red-800 dark:bg-red-900/30 dark:border-red-800 dark:text-red-300',
        default   => 'bg-blue-50 border-blue-200 text-blue-800 dark:bg-blue-900/30 dark:border-blue-800 dark:text-blue-300',
    };

    $iconComponent = match($type) {
        'success' => 'tabler-circle-check',
        'warning' => 'tabler-alert-triangle',
        'error'   => 'tabler-circle-x',
        default   => 'tabler-info-circle',
    };
@endphp

<div
    {{ $attributes->class(["flex items-start gap-3 p-4 rounded-lg border $styles"]) }}
    role="alert"
    @if($dismissible) x-data="{ show: true }" x-show="show" @endif
>
    <x-dynamic-component :component="$iconComponent" class="w-5 h-5 flex-shrink-0 mt-0.5" />

    <div class="flex-1 min-w-0">
        @if($title)
            <p class="font-semibold mb-0.5">{{ $title }}</p>
        @endif
        <div class="text-sm">{{ $slot }}</div>
    </div>

    @if($dismissible)
        <button type="button" @click="show = false" class="flex-shrink-0 opacity-60 hover:opacity-100 transition-opacity">
            <x-tabler-x class="w-4 h-4" />
            <span class="sr-only">Tutup</span>
        </button>
    @endif
</div>
