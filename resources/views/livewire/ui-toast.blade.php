<div
    x-data="{ show: $wire.entangle('show') }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-2"
    x-init="$watch('show', val => { if (val) setTimeout(() => show = false, 4000) })"
    class="fixed bottom-5 right-5 z-50 max-w-sm w-full"
    style="display: none"
>
    @php
        $styles = match($type) {
            'success' => 'bg-green-50 border-green-200 text-green-800 dark:bg-green-900/80 dark:border-green-700 dark:text-green-200',
            'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800 dark:bg-yellow-900/80 dark:border-yellow-700 dark:text-yellow-200',
            'error'   => 'bg-red-50 border-red-200 text-red-800 dark:bg-red-900/80 dark:border-red-700 dark:text-red-200',
            default   => 'bg-blue-50 border-blue-200 text-blue-800 dark:bg-blue-900/80 dark:border-blue-700 dark:text-blue-200',
        };

        $iconComponent = match($type) {
            'success' => 'tabler-circle-check',
            'warning' => 'tabler-alert-triangle',
            'error'   => 'tabler-circle-x',
            default   => 'tabler-info-circle',
        };
    @endphp

    <div class="flex items-start gap-3 p-4 rounded-lg border shadow-lg {{ $styles }}">
        <x-dynamic-component :component="$iconComponent" class="w-5 h-5 flex-shrink-0 mt-0.5" />

        <p class="flex-1 text-sm font-medium">{{ $message }}</p>

        <button type="button" wire:click="dismiss" class="flex-shrink-0 opacity-60 hover:opacity-100 transition-opacity">
            <x-tabler-x class="w-4 h-4" />
            <span class="sr-only">Tutup</span>
        </button>
    </div>
</div>
