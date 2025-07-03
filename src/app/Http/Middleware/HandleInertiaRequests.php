<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
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
        $user = $request->user();
        
        // デバッグ用：認証情報をログに出力
        Log::info('HandleInertiaRequests - User:', [
            'user' => $user ? $user->toArray() : null,
            'authenticated' => Auth::check(),
            'session_id' => $request->session()->getId(),
        ]);

        $shared = [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ] : null,
            ],
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];

        // デバッグ用：共有データをログに出力
        Log::info('HandleInertiaRequests - Shared data:', [
            'auth' => $shared['auth'],
            'url' => $shared['url'] ?? null,
        ]);

        return $shared;
    }
}
