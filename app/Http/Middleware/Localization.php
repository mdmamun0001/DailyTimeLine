<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

class Localization
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check header request and determine localizaton
        $local = ($request->hasHeader('X-localization')) ? $request->header('X-localization') : 'en';
        // set laravel localization
        app()->setLocale($local);
        // continue request
        return $next($request);
    }
}
