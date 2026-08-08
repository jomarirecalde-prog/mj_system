<?php

namespace App\Providers;

use App\Database\Connectors\NeonPostgresConnector;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Connection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($storagePath = env('APP_STORAGE_PATH')) {
            $this->app->useStoragePath($storagePath);
        }

        Connection::resolverFor('pgsql', function ($connection, $database, $prefix, $config) {
            $connector = new NeonPostgresConnector;
            $pdo = $connector->connect($config);

            return new PostgresConnection($pdo, $database, $prefix, $config);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        ResetPassword::createUrlUsing(function (object $user, string $token) {
            if (method_exists($user, 'isEmployee') && $user->isEmployee()) {
                return url(route('employee.password.reset', [
                    'token' => $token,
                    'email' => $user->getEmailForPasswordReset(),
                ], false));
            }

            return url('/login');
        });
    }
}
