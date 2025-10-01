<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckInstallation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip check for install routes
        if ($request->is('install') || $request->is('install/*') || $request->is('livewire/*')) {
            return $next($request);
        }

        // If not installed, redirect to install page
        if (!config('app.installed')) {
            return redirect('/install');
        }

        return $next($request);
    }
}

