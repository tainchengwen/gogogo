<?php

namespace App\Http;

use App\Http\Middleware\CheckLogin;
use App\Http\Middleware\CheckToken;
use App\Http\Middleware\GetUserFromToken;
use App\Http\Middleware\WxUserAuth;
use App\Http\Middleware\MpRender;
use App\Http\Middleware\ShopMpRender;
use App\Http\Middleware\ShopMpAgentRender;
use App\Http\Middleware\RefreshToken;
use App\Http\Middleware\CheckIsVisitor;
use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \App\Http\Middleware\TrustProxies::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            //'throttle:60,1',
            'bindings',
        ],

        'token' => [
            CheckToken::class
        ]
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth'              => \Illuminate\Auth\Middleware\Authenticate::class,
        'auth.basic'        => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'bindings'          => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'cache.headers'     => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can'               => \Illuminate\Auth\Middleware\Authorize::class,
        'guest'             => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'signed'            => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle'          => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'CheckLogin'        => CheckLogin::class,
        'token'             => CheckToken::class,
        'jwt.auth'          => 'Tymon\JWTAuth\Middleware\GetUserFromToken',
        'jwt.refresh'       => 'Tymon\JWTAuth\Middleware\RefreshToken',
        'jwt.api.auth'      => GetUserFromToken::class,
        'jwt.api.refresh'   => RefreshToken::class,
        'jwt.api.wx'        => WxUserAuth::class,
        'role'              => \Spatie\Permission\Middlewares\RoleMiddleware::class,
        'permission'        => \Spatie\Permission\Middlewares\PermissionMiddleware::class,
        'mpRender'          => MpRender::class,
        'shopMpRender'      => ShopMpRender::class,
        'ShopMpAgentRender' => ShopMpAgentRender::class,
        'checkIsVisitor' => CheckIsVisitor::class,
    ];
}
