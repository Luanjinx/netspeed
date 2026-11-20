<?php

namespace App\Providers;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Throwable;

class DemoModeServiceProvider extends ServiceProvider
{
    /**
     * Check if demo mode is currently enabled (checks both config and raw env for resilience).
     */
    public static function isDemo(): bool
    {
        return (bool) config('app.demo', false) ||
            filter_var(env('DEMO', env('APP_DEMO', env('DEMO_MODE', false))), FILTER_VALIDATE_BOOLEAN);
    }

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
        if (! static::isDemo()) {
            return;
        }

        // 1. Eloquent Model Protection: Abort database mutations on ISP data models
        Model::saving(function (Model $model) {
            if (static::isDemo() && ! app()->runningInConsole()) {
                // Allow login & session remember_token updates on User model
                if ($model instanceof User && ! $model->isDirty(['name', 'email', 'password'])) {
                    return true;
                }

                Notification::make()
                    ->warning()
                    ->title('⚡ Mode Demo Aktif')
                    ->body('Anda sedang menjelajahi akun demonstrasi. Fitur penambahan, perubahan, dan penghapusan data dinonaktifkan (Read-Only).')
                    ->duration(6000)
                    ->send();

                if (class_exists(Livewire::class)) {
                    try {
                        Livewire::dispatch('open-demo-modal');
                    } catch (Throwable $e) {
                        // Suppress
                    }
                }

                return false; // Cancels save / update in Eloquent
            }
        });

        Model::deleting(function (Model $model) {
            if (static::isDemo() && ! app()->runningInConsole()) {
                Notification::make()
                    ->warning()
                    ->title('⚡ Mode Demo Aktif')
                    ->body('Anda sedang menjelajahi akun demonstrasi. Fitur penambahan, perubahan, dan penghapusan data dinonaktifkan (Read-Only).')
                    ->duration(6000)
                    ->send();

                if (class_exists(Livewire::class)) {
                    try {
                        Livewire::dispatch('open-demo-modal');
                    } catch (Throwable $e) {
                        // Suppress
                    }
                }

                return false; // Cancels delete in Eloquent
            }
        });

        // 2. Intercept mutating Filament Actions when clicked by user (Save, Create, Delete, etc.)
        Action::configureUsing(function (Action $action): void {
            if (! static::isDemo()) {
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
