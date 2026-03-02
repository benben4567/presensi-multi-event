@props(['title' => null])

<div {{ $attributes->class(['flex items-center justify-between mb-6 gap-4']) }}>
    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">
        {{ $title ?? $slot }}
    </h2>

    @isset($actions)
        <div class="flex items-center gap-2 flex-shrink-0">
            {{ $actions }}
        </div>
    @endisset
</div>
