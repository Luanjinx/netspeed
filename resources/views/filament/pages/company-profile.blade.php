<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex items-center justify-end gap-3 pt-4">
            <x-filament::button
                href="{{ url('/admin/settings') }}"
                tag="a"
                color="gray"
                icon="heroicon-o-arrow-left">
                Kembali ke Pengaturan
            </x-filament::button>

            <x-filament::button
                type="submit"
                size="lg"
                icon="heroicon-o-check"
                color="primary">
                Simpan Profil Perusahaan &amp; Titik Peta
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
