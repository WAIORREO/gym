<?php
namespace App\Http\Middleware;
class SetGymLocale {
    public function handle($request, \Closure $next) {
        app()->setLocale($request->getPreferredLanguage(['ar','fr','en']) ?: 'ar');
        return $next($request);
    }
}
