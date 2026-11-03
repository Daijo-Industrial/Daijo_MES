@props(['id', 'title', 'sectionKey'])

<div class="border border-gray-200 rounded-lg overflow-hidden bg-white mb-3 shadow-sm">
    <button type="button"
            @click="sections.{{ $sectionKey }} = !sections.{{ $sectionKey }}"
            class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 text-left font-medium text-gray-800 transition">
        <span class="flex items-center gap-2 text-sm font-semibold text-gray-900">
            {{ $title }}
        </span>
        <svg class="w-4 h-4 text-gray-500 transform transition-transform duration-200"
             :class="{ 'rotate-180': sections.{{ $sectionKey }} }"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>
    <div x-show="sections.{{ $sectionKey }}" x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-1">
        <div class="p-4 border-t border-gray-100">
            {{ $slot }}
        </div>
    </div>
</div>
