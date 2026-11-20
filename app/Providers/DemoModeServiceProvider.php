<?php

namespace App\Providers;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\ServiceProvider;

class DemoModeServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! config('app.demo')) {
            return;
        }

        // Intercept mutating Filament Actions when clicked by user (Save, Create, Delete, etc.)
        Action::configureUsing(function (Action $action): void {
            if (! config('app.demo')) {
                return;
            }

            $name = strtolower($action->getName());
            $mutatingNames = [
                'create',
                'createanother',
                'save',
                'delete',
                'deleteany',
                'forcedelete',
                'restore',
                'replicate',
                'detach',
                'dissociate',
            ];

            if (
                in_array($name, $mutatingNames, true) ||
                str_contains($name, 'delete') ||
                str_contains($name, 'save') ||
                str_contains($name, 'create') ||
                str_contains($name, 'reboot') ||
                str_contains($name, 'isolir') ||
                str_contains($name, 'sync') ||
                str_contains($name, 'reset')
            ) {
                // Do NOT disable form fields or buttons so users can freely explore and test the interface.
                // When user clicks "Save" / "Create" / "Hapus", intercept with a toast and halt execution cleanly.
                $action->action(function (Action $action): void {
                    Notification::make()
                        ->warning()
                        ->title('⚡ Mode Demo Aktif')
                        ->body('Anda sedang menjelajahi akun demonstrasi. Fitur penambahan, perubahan, dan penghapusan data dinonaktifkan (Read-Only).')
                        ->duration(5000)
                        ->send();

                    $livewire = $action->getLivewire();
                    if ($livewire && method_exists($livewire, 'dispatch')) {
                        $livewire->dispatch('open-demo-modal');
                    }

                    $action->halt();
                });
            }
        });
    }
}
