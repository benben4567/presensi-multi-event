@props(['message' => 'Tidak ada data.', 'colspan' => 1])

<tr>
    <td colspan="{{ $colspan }}" class="py-12 text-center">
        <div class="flex flex-col items-center gap-2 text-gray-400 dark:text-gray-500">
            <x-tabler-database-off class="w-10 h-10 opacity-50" />
            <p class="text-sm">{{ $message }}</p>
        </div>
    </td>
</tr>
