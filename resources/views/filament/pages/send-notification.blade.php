<x-filament-panels::page>

    <form wire:submit.prevent="saveRules" class="space-y-6">
        <div class="fi-fo-component-grid grid grid-cols-1 gap-6">
            {{ $this->getSchema('form') }}
        </div>

        <div class="flex gap-3 pt-6 border-t">
            {{ $this->getAction('saveRules') }}
            {{ $this->getAction('send') }}
            {{ $this->getAction('preview') }}
        </div>
    </form>

    {{-- 🔥 FILAMENT TABLE --}}
    <div class="mt-10">
        {{ $this->table }}
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>