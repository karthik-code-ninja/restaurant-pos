<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        // Auto-detect base directory/prefix when running in a subfolder (e.g. /restro) on live hosting
        if (!app()->runningInConsole() && isset($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI'];
            $path = trim(parse_url($uri, PHP_URL_PATH) ?? '', '/');
            $segments = explode('/', $path);

            $knownRoutes = [
                'dashboard', 'pos', 'tables', 'foods', 'categories', 'combos',
                'addons', 'tax', 'inventory', 'expenses', 'day-closing', 'reports',
                'users', 'settings', 'backup', 'audit-logs', 'login', 'logout', 'print'
            ];

            $prefixSegments = [];
            foreach ($segments as $seg) {
                if (in_array($seg, $knownRoutes)) {
                    break;
                }
                $prefixSegments[] = $seg;
            }

            $subPath = !empty($prefixSegments) ? '/' . implode('/', $prefixSegments) : '';

            $appUrlPath = rtrim(parse_url(config('app.url') ?? '', PHP_URL_PATH) ?? '', '/');
            if (!empty($appUrlPath) && empty($subPath)) {
                $subPath = $appUrlPath;
            }

            $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
                      (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
                      (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
                      ? 'https' : (request()->isSecure() ? 'https' : 'http');

            $host = $_SERVER['HTTP_HOST'] ?? request()->getHost();

            if (!empty($subPath)) {
                \Illuminate\Support\Facades\URL::forceRootUrl($scheme . '://' . $host . $subPath);
            }

            if ($scheme === 'https') {
                \Illuminate\Support\Facades\URL::forceScheme('https');
            }
        }
    }
}
