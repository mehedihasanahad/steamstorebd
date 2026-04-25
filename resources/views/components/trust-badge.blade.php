@props(['icon', 'title', 'description'])

<div class="flex flex-col items-center text-center p-4">
    <div class="text-3xl mb-2">{{ $icon }}</div>
    <h4 class="font-semibold text-white text-sm mb-1">{{ $title }}</h4>
    <p class="text-gray-400 text-xs">{{ $description }}</p>
</div>
