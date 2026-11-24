<?php

namespace App\Filament\Pages\MikrotikTools;

use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class MikrotikToolsIndex extends Page
{
    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    protected static ?string $slug = 'mikrotik-tools';

    protected static bool $shouldRegisterNavigation = true;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static string|UnitEnum|null $navigationGroup = 'Jaringan';

    protected static ?string $navigationLabel = 'MikroTik Tools';

    protected static ?string $title = 'MikroTik Script Generator & Core Tools';

    protected static ?int $navigationSort = 8;

    protected string $view = 'filament.pages.mikrotik-tools.index';

    public string $search = '';

    public string $activeCategory = 'all';

    public function setCategory(string $category): void
    {
        $this->activeCategory = $category;
    }
}
