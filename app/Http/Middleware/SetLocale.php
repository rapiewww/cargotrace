<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Supported UI locales.
     *
     * @var list<string>
     */
    public const SUPPORTED_LOCALES = ['en', 'id'];

    /**
     * Handle an incoming request and synchronize the application locale from session.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $defaultLocale = config('app.locale', 'en');
        if (! is_string($defaultLocale) || ! in_array($defaultLocale, self::SUPPORTED_LOCALES, true)) {
            $defaultLocale = 'en';
        }

        $locale = $request->hasSession()
            ? $request->session()->get('locale', $defaultLocale)
            : $defaultLocale;

        if (! is_string($locale) || ! in_array($locale, self::SUPPORTED_LOCALES, true)) {
            $locale = 'en';
        }

        App::setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
