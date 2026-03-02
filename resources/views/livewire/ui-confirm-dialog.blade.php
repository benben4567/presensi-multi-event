<div>
    {{-- Backdrop + Modal --}}
    <div
        x-data="{ show: $wire.entangle('show') }"
        x-show="show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        @keydown.escape.window="$wire.cancel()"
        style="display: none"
    >
        <div
            x-show="show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6 w-full max-w-md"
            @click.stop
        >
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/40 flex items-center justify-center">
                    <x-tabler-alert-triangle class="w-5 h-5 text-red-600 dark:text-red-400" />
                </div>

                <div class="flex-1 min-w-0">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white mb-1">Konfirmasi</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ $message }}</p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 mt-6">
                <x-ui.button wire:click="cancel">
                    {{ $cancelLabel }}
                </x-ui.button>
                <x-ui.button variant="danger" wire:click="confirm">
                    {{ $confirmLabel }}
                </x-ui.button>
            </div>
        </div>
    </div>
</div>
