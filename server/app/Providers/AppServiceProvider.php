<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AppServiceProvider extends ServiceProvider
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
        Schema::defaultStringLength(191);
        Route::aliasMiddleware('ability', CheckAbilities::class);

        Exceptions::renderable(function (AccessDeniedHttpException $e, $request) {
            if ($request->is('api/*')) {
                $message = $e->getMessage() ?? 'Access denied.';

                if (str_contains($message, 'Invalid ability provided.')) {
                    $message = 'Access denied.';
                }

                return response()->json([
                    'message' => $message,
                ], 403);
            }
        });

        Gate::policy(User::class, UserPolicy::class);
    }
}
