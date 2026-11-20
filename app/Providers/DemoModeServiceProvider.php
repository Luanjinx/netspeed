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

                session()->flash('open_demo_modal', true);

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

                session()->flash('open_demo_modal', true);

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

        // 2. Intercept Livewire method calls (e.g. wire:submit="save", wire:submit="create", table actions)
        if (class_exists(Livewire::class)) {
            try {
                Livewire::listen('call', function ($root, $method, $params, $componentContext, $returnEarly): void {
                    if (! static::isDemo()) {
                        return;
                    }

                    $methodName = strtolower($method);
                    $actionName = is_array($params) && isset($params[0]) && is_string($params[0]) ? strtolower($params[0]) : '';

                    $isMutatingMethod = in_array($methodName, ['save', 'create', 'createanother', 'delete', 'destroy', 'update'], true) ||
                        str_contains($methodName, 'delete') ||
                        str_contains($methodName, 'save') ||
                        str_contains($methodName, 'create') ||
                        str_contains($methodName, 'reboot') ||
                        str_contains($methodName, 'isolir');

                    $isMutatingAction = in_array($actionName, [
                        'create',
                        'createanother',
                        'edit',
                        'save',
                        'delete',
                        'deleteany',
                        'forcedelete',
                        'restore',
                        'replicate',
                        'detach',
                        'dissociate',
                        'reboot',
                        'isolir',
                        'sync',
                        'reset',
                    ], true) ||
                        str_contains($actionName, 'delete') ||
                        str_contains($actionName, 'reboot') ||
                        str_contains($actionName, 'isolir') ||
                        str_contains($actionName, 'save');

                    if (! ($isMutatingMethod || (($methodName === 'callmountedaction' || $methodName === 'callmountedtableaction') && $isMutatingAction))) {
                        return;
                    }

                    // Set flash session for popup modal on redirected page
                    session()->flash('open_demo_modal', true);

                    Notification::make()
                        ->warning()
                        ->title('⚡ Mode Demo Aktif')
                        ->body('Anda sedang menjelajahi akun demonstrasi. Fitur penambahan, perubahan, dan penghapusan data dinonaktifkan (Read-Only).')
                        ->duration(5000)
                        ->send();

                    // Check if saving or creating on an Edit/Create page, redirect back to index
                    $className = is_object($root) ? get_class($root) : '';
                    $isEditOrCreatePage = str_contains($className, 'Edit') ||
                        str_contains($className, 'Create') ||
                        $methodName === 'save' ||
                        $methodName === 'create' ||
                        $methodName === 'createanother';

                    if ($isEditOrCreatePage && is_object($root)) {
                        $redirectUrl = null;
                        if (method_exists($root, 'getResource')) {
                            try {
                                $resource = $root->getResource();
                                if ($resource::hasPage('index')) {
                                    $redirectUrl = $resource::getUrl('index');
                                }
                            } catch (Throwable $e) {
                                // Ignore
                            }
                        }

                        if (! $redirectUrl && isset($root->previousUrl) && ! empty($root->previousUrl)) {
                            $redirectUrl = $root->previousUrl;
                        }

                        if (! $redirectUrl) {
                            $redirectUrl = url()->previous();
                        }

                        session()->flash('demo_redirect_url', $redirectUrl);

                        if ($redirectUrl && method_exists($root, 'redirect')) {
                            $root->redirect($redirectUrl, navigate: true);
                            if (is_callable($returnEarly)) {
                                $returnEarly();
                            }

                            return;
                        }
                    }

                    if (is_object($root) && method_exists($root, 'dispatch')) {
                        $root->dispatch('open-demo-modal');
                    }

                    if (is_callable($returnEarly)) {
                        $returnEarly();
                    }
                });
            } catch (Throwable $e) {
                // Suppress if mechanism is not loaded
            }
        }

        // 3. Intercept mutating Filament Actions when clicked by user (Save, Create, Delete, etc.)
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
                $action->action(function (Action $action) use ($name): void {
                    Notification::make()
                        ->warning()
                        ->title('⚡ Mode Demo Aktif')
                        ->body('Anda sedang menjelajahi akun demonstrasi. Fitur penambahan, perubahan, dan penghapusan data dinonaktifkan (Read-Only).')
                        ->duration(5000)
                        ->send();

                    session()->flash('open_demo_modal', true);

                    $livewire = $action->getLivewire();
                    if ($livewire) {
                        // Determine previous/index page redirect URL
                        $redirectUrl = null;
                        if (method_exists($livewire, 'getResource')) {
                            try {
                                $resource = $livewire->getResource();
                                if ($resource::hasPage('index')) {
                                    $redirectUrl = $resource::getUrl('index');
                                }
                            } catch (Throwable $e) {
                                // Ignore
                            }
                        }

                        if (! $redirectUrl && isset($livewire->previousUrl) && ! empty($livewire->previousUrl)) {
                            $redirectUrl = $livewire->previousUrl;
                        }

                        if (! $redirectUrl) {
                            $redirectUrl = url()->previous();
                        }

                        session()->flash('demo_redirect_url', $redirectUrl);

                        // If saving or creating on an Edit/Create page, redirect back to index
                        $className = get_class($livewire);
                        $isEditOrCreate = (
                            $name === 'save' ||
                            $name === 'create' ||
                            str_contains($className, 'Edit') ||
                            str_contains($className, 'Create')
                        );

                        if ($isEditOrCreate && $redirectUrl && method_exists($livewire, 'redirect')) {
                            $livewire->redirect($redirectUrl, navigate: true);

                            return;
                        }

                        if (method_exists($livewire, 'dispatch')) {
                            $livewire->dispatch('open-demo-modal', ['url' => $redirectUrl]);
                        }
                    }

                    $action->halt();
                });
            }
        });
    }
}
