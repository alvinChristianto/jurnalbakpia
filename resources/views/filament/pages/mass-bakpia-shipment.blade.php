<x-filament-panels::page>
    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}

        <div class="flex justify-end">
            <x-filament::button type="submit">
                Simpan Pengiriman
            </x-filament::button>
        </div>
    </x-filament-panels::form>
</x-filament-panels::page>