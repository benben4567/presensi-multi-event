@props([
    'variant' => 'secondary',
    'type'    => 'button',
    'href'    => null,
    'size'    => 'md',
])

@php
    $variantStyles = match($variant) {
        'primary' => 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500 dark:bg-blue-600 dark:hover:bg-blue-700',
        'danger'  => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500 dark:bg-red-600 dark:hover:bg-red-700',
        default   => 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 focus:ring-gray-300 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700',
    };

    $sizeStyles = match($size) {
        'sm' => 'px-3 py-1.5 text-xs',
        'lg' => 'px-6 py-3 text-base',
        default => 'px-4 py-2 text-sm',
    };

    $baseStyles = "inline-flex items-center gap-1.5 font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-1 transition-colors disabled:opacity-50 disabled:cursor-not-allowed $variantStyles $sizeStyles";
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->class([$baseStyles]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class([$baseStyles]) }}>
        {{ $slot }}
    </button>
@endif
