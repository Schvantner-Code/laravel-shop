<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        // Try to get the language from the header (e.g., "sk", "en")
        $locale = $request->header('Accept-Language');

        // If it's a supported language, switch the app to it
        if (in_array($locale, ['en', 'sk'])) {
            app()->setLocale($locale);
        } else {
            app()->setLocale('en'); // Default fallback to English
        }

        return $next($request);
    }
}
