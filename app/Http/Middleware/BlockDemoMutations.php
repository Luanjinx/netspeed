<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class BlockDemoMutations
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.demo')) {
            return $next($request);
        }

        // Allow safe read-only methods
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        // Allow authentication routes (Login and Logout)
        if ($request->is('*/login*') || $request->is('*/logout*') || $request->is('login*') || $request->is('logout*')) {
            return $next($request);
        }

        // Check if mutation route or livewire mutation
        if ($request->is('admin/*') || $request->is('livewire/*')) {
            // Check Livewire payload for mutations
            if ($request->is('livewire/*')) {
                $components = $request->input('components', []);
                $hasMutation = false;

                foreach ($components as $component) {
                    $calls = $component['calls'] ?? [];
                    foreach ($calls as $call) {
                        $method = strtolower($call['method'] ?? '');
                        $params = $call['params'] ?? [];
                        $actionName = is_array($params) && isset($params[0]) && is_string($params[0]) ? strtolower($params[0]) : '';

                        $isMutatingMethod = in_array($method, ['save', 'create', 'delete', 'destroy'], true) ||
                            str_contains($method, 'delete') ||
                            str_contains($method, 'create') ||
                            str_contains($method, 'save') ||
                            str_contains($method, 'reboot') ||
                            str_contains($method, 'isolir');

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
                            str_contains($actionName, 'isolir');

                        if ($isMutatingMethod || (($method === 'callmountedaction' || $method === 'callmountedtableaction') && $isMutatingAction)) {
                            $hasMutation = true;
                            break 2;
                        }
                    }
                }

                if ($hasMutation) {
                    Notification::make()
                        ->warning()
                        ->title('⚡ Mode Demo Aktif')
                        ->body('Anda sedang menjelajahi akun demonstrasi. Fitur penambahan, perubahan, dan penghapusan data dinonaktifkan (Read-Only).')
                        ->duration(5000)
                        ->send();

                    throw ValidationException::withMessages([
                        'demo' => 'Mode Demo Aktif: Anda sedang menjelajahi akun demonstrasi. Fitur penambahan, perubahan, dan penghapusan data dinonaktifkan (Read-Only).',
                    ]);
                }
            } else {
                // Direct POST/PUT/PATCH/DELETE
                if ($request->wantsJson()) {
                    return response()->json([
                        'error' => 'Mode Demo Aktif: Aksi penambahan, perubahan, dan penghapusan data dinonaktifkan.',
                    ], 403);
                }

                Notification::make()
                    ->warning()
                    ->title('Mode Demo Aktif')
                    ->body('Aksi penambahan, perubahan, dan penghapusan data dinonaktifkan pada versi demo ini.')
                    ->duration(4000)
                    ->send();

                return back();
            }
        }

        return $next($request);
    }
}
