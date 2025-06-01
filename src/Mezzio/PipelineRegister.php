<?php
declare(strict_types=1);

namespace LesAbstractService\Mezzio;

use LesHttp\Middleware\Route\RouterMiddleware;
use LesHttp\Middleware\Locale\LocaleMiddleware;
use LesHttp\Middleware\Route\NoRouteMiddleware;
use Laminas\Stratigility\Middleware\ErrorHandler;
use LesHttp\Middleware\Analytics\AnalyticsMiddleware;
use LesHttp\Middleware\Condition\ConditionMiddleware;
use LesHttp\Middleware\Authentication\AuthenticationMiddleware;
use LesHttp\Middleware\Authorization\AuthorizationMiddleware;
use LesHttp\Middleware\Cors\CorsMiddleware;
use LesHttp\Middleware\Throttle\ThrottleMiddleware;
use LesHttp\Middleware\TrimMiddleware;
use LesHttp\Middleware\Validation\ValidationMiddleware;
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

        $app->pipe(RouterMiddleware::class);
        $app->pipe(NoRouteMiddleware::class);

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
