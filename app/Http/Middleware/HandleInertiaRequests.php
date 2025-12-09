<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * Middleware: HandleInertiaRequests
     *
     * Extends Inertia middleware om gedeelde data (zoals authenticated user) beschikbaar
     * te maken voor de frontend en om aanvullende headers of props in te stellen.
     */
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
            ],
            'app_env' => config('app.env'), 
        ];
    }
}
