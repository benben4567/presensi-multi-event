@props(['disabled' => false])

<select
    {{ $disabled ? 'disabled' : '' }}
    {{ $attributes->class([
        'w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900',
        'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500',
        'disabled:bg-gray-50 disabled:text-gray-500 disabled:cursor-not-allowed',
        'dark:border-gray-600 dark:bg-gray-700 dark:text-white',
        'dark:focus:ring-blue-500 dark:focus:border-blue-500',
    ]) }}
>
    {{ $slot }}
</select>
