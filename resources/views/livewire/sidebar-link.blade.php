<?php

use Livewire\Volt\Component;

new class extends Component {
    public string $href;
    public bool $active;
    public string $label;

    public function mount(string $href, string $label, bool $active = false)
    {
        $this->href  = $href;
        $this->label = html_entity_decode($label, ENT_QUOTES, 'UTF-8');
        $this->active = $active;
    }
}; ?>

<a href="{{ $href }}"
   class="{{ $active
        ? 'block w-full px-4 py-3 text-sm font-medium border-l-4 border-indigo-500 text-indigo-600 bg-indigo-100 rounded'
        : 'block w-full px-4 py-3 text-sm font-medium border-l-4 border-transparent text-gray-500 hover:bg-indigo-100 hover:text-gray-900 rounded'
   }}">
    {{ $label }}
</a>
