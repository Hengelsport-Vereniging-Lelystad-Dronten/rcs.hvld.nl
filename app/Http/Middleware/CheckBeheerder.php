<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBeheerder
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Gebruik de nieuwe helperfunctie
        if (! $request->user() || ! $request->user()->isBeheerder()) {
            
            // Stuur terug met een 403 als de rol niet toereikend is
            abort(403, 'Alleen Beheerders en Coördinatoren hebben toegang tot dit beheersgedeelte.');
        }

        return $next($request);
    }
}
