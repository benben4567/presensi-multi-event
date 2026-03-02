@props(['color' => 'gray'])

@php
    $styles = match($color) {
        'green'  => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
        'yellow' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300',
        'red'    => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
        'blue'   => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
        'purple' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300',
        default  => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    };
@endphp

<span {{ $attributes->class(["inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium $styles"]) }}>
    {{ $slot }}
</span>
