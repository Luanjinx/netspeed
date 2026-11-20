<?php

namespace App\Filament\Pages;

use App\Models\IspSetting;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class NetworkMap extends Page
{
    protected static ?string $slug = 'map';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Jaringan';

    protected static ?string $navigationLabel = 'Peta Jaringan & ODP';

    protected static ?string $title = 'Peta Geografis Jaringan & Pelanggan';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.network-map';

    public array $office = [];

    public function mount(): void
    {
        $this->office = IspSetting::getOfficeLocation();
    }
}
