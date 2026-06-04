<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocaleMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->header('Accept-Language');

        if ($locale && in_array($locale, ['vi', 'en'])) {
            App::setLocale($locale);
        } elseif ($request->user() && in_array($request->user()->locale, ['vi', 'en'])) {
            App::setLocale($request->user()->locale);
        } else {
            App::setLocale('vi');
        }

        return $next($request);
    }
}
