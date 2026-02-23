<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TouchLastSaveAction
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$this->shouldTouch($request, $response)) {
            return $response;
        }

        $user = $request->user();
        if (!$user) {
            return $response;
        }

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->saveQuietly();

        return $response;
    }

    private function shouldTouch(Request $request, Response $response): bool
    {
        if (!$request->isMethod('post') && !$request->isMethod('put') && !$request->isMethod('patch') && !$request->isMethod('delete')) {
            return false;
        }

        if ($response->getStatusCode() >= 400) {
            return false;
        }

        $routeName = $request->route()?->getName();
        if (!$routeName) {
            return true;
        }

        // Sluit auth-flow uit; focus op "save actions" binnen de applicatie.
        return !in_array($routeName, [
            'login',
            'logout',
            'password.email',
            'password.update',
            'password.store',
            'password.confirm',
            'verification.send',
        ], true);
    }
}

