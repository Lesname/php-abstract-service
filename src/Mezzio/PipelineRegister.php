<?php
declare(strict_types=1);

namespace LessAbstractService\Mezzio;

use LessHttp\Middleware\Locale\LocaleMiddleware;
use Laminas\Stratigility\Middleware\ErrorHandler;
use LessHttp\Middleware\Analytics\AnalyticsMiddleware;
use LessHttp\Middleware\Condition\ConditionMiddleware;
use LessHttp\Middleware\Authentication\AuthenticationMiddleware;
use LessHttp\Middleware\Authorization\AuthorizationMiddleware;
use LessHttp\Middleware\Cors\CorsMiddleware;
use LessHttp\Middleware\Throttle\ThrottleMiddleware;
use LessHttp\Middleware\TrimMiddleware;
use LessHttp\Middleware\Validation\ValidationMiddleware;
use Mezzio\Application;
use Mezzio\Handler\NotFoundHandler;
use Mezzio\Helper\BodyParams\BodyParamsMiddleware;
use Mezzio\Router\Middleware\DispatchMiddleware;
use Mezzio\Router\Middleware\ImplicitOptionsMiddleware;
use Mezzio\Router\Middleware\MethodNotAllowedMiddleware;
use Mezzio\Router\Middleware\RouteMiddleware;

final class PipelineRegister
{
    public static function register(Application $app): Application
    {
        // On debug env let the error bubble up
        if (getenv('ENV') !== 'debug') {
            $app->pipe(ErrorHandler::class);
        }

        $app->pipe(CorsMiddleware::class);

        $app->pipe(AuthenticationMiddleware::class);
        $app->pipe(AnalyticsMiddleware::class);

        $app->pipe(ThrottleMiddleware::class);

        $app->pipe(RouteMiddleware::class);

        $app->pipe(ImplicitOptionsMiddleware::class);

        $app->pipe(MethodNotAllowedMiddleware::class);

        $app->pipe(new BodyParamsMiddleware());

        $app->pipe(TrimMiddleware::class);
        $app->pipe(LocaleMiddleware::class);
        $app->pipe(ValidationMiddleware::class);

        $app->pipe(AuthorizationMiddleware::class);
        $app->pipe(ConditionMiddleware::class);

        $app->pipe(DispatchMiddleware::class);
        $app->pipe(NotFoundHandler::class);

        return $app;
    }
}
