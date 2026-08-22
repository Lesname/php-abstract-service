<?php

declare(strict_types=1);

namespace LesAbstractService\Config\Provider;

use Monolog\Logger;
use Psr\Log\LoggerInterface;
use LesAbstractService\Application;
use LesAbstractService\Clock\Clock;
use LesAbstractService\Clock\ActiveClock;
use LesAbstractService\Cli\Queue\QuitCommand;
use LesAbstractService\Cli\Cache\ClearCommand;
use LesAbstractService\Cli\Queue\ProcessCommand;
use Laminas\Stratigility\Middleware\ErrorHandler;
use LesAbstractService\Cli\Service\UpdateCommand;
use LesAbstractService\Cli\Service\CleanUpCommand;
use LesAbstractService\Cli\Queue\ReanimateCommand;
use LesAbstractService\Cli\Documentor\WriteCommand;
use LesAbstractService\Factory\Logger\MonologFactory;
use Symfony\Contracts\Translation\TranslatorInterface;
use LesAbstractService\Container\Mail\SenderContainer;
use LesAbstractService\Cli\Queue\ProcessCommandFactory;
use LesAbstractService\Container\Mail\TemplateContainer;
use LesAbstractService\Cli\Queue\CountProcessingCommand;
use LesAbstractService\Cli\Queue\CountProcessableCommand;
use LesAbstractService\Factory\Container\ReflectionFactory;
use LesAbstractService\Container\Mail\SenderContainerFactory;
use LesAbstractService\Container\Mail\TemplateContainerFactory;
use LesAbstractService\Factory\Symfony\Translator\TranslatorFactory;
use LesAbstractService\Mezzio\Listener\ErrorHandlerDelegatorFactory;
use LesAbstractService\Factory\Logger\RollbarMonologDelegatorFactory;
use LesAbstractService\Http\Resource\Handler\CreateEventRouteHandler;
use LesAbstractService\Http\Resource\Handler\UpdateEventRouteHandler;
use LesAbstractService\Http\Resource\Handler\ResultQueryRouteHandler;
use LesAbstractService\Http\Resource\Handler\ResultsQueryRouteHandler;
use LesAbstractService\Http\Resource\Handler\CreateEventRouteHandlerFactory;
use LesAbstractService\Http\Resource\ConditionConstraint\ExistsConditionConstraint;
use LesAbstractService\Http\Resource\ConditionConstraint\VersionConditionConstraint;
use LesAbstractService\Middleware\Authorization\Constraint\Account\AnyAccountAuthorizationConstraint;
use LesAbstractService\Middleware\Authorization\Constraint\Producer\AnyProducerAuthorizationConstraint;
use LesAbstractService\Middleware\Authorization\Constraint\Consumer\AnyConsumerAuthorizationConstraint;

final class BaseConfigProvider
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return [
            'translator' => [
                'translation' => [
                    'nl_NL' => [
                        __DIR__ . '/../../../../docs/translations/nl_NL.php',
                    ],
                    'en_US' => [
                        __DIR__ . '/../../../../docs/translations/en_US.php',
                    ],
                ],
            ],
            'shared_by_default' => php_sapi_name() !== 'cli',
            'dependencies' => [
                'aliases' => [
                    LoggerInterface::class => Logger::class,

                    Clock::class => ActiveClock::class,
                ],
                'delegators' => [
                    ErrorHandler::class => [
                        ErrorHandlerDelegatorFactory::class,
                    ],
                    Logger::class => [
                        RollbarMonologDelegatorFactory::class,
                    ],
                ],
                'invokables' => [
                    AnyAccountAuthorizationConstraint::class => AnyAccountAuthorizationConstraint::class,
                    AnyConsumerAuthorizationConstraint::class => AnyConsumerAuthorizationConstraint::class,
                    AnyProducerAuthorizationConstraint::class => AnyProducerAuthorizationConstraint::class,

                    ActiveClock::class => ActiveClock::class,
                ],
                'factories' => [
                    Application::class => ReflectionFactory::class,

                    TemplateContainer::class => TemplateContainerFactory::class,
                    SenderContainer::class => SenderContainerFactory::class,

                    CreateEventRouteHandler::class => CreateEventRouteHandlerFactory::class,
                    UpdateEventRouteHandler::class => ReflectionFactory::class,

                    ResultsQueryRouteHandler::class => ReflectionFactory::class,
                    ResultQueryRouteHandler::class => ReflectionFactory::class,

                    ExistsConditionConstraint::class => ReflectionFactory::class,
                    VersionConditionConstraint::class => ReflectionFactory::class,

                    ClearCommand::class => ReflectionFactory::class,

                    WriteCommand::class => ReflectionFactory::class,

                    CleanUpCommand::class => ReflectionFactory::class,
                    UpdateCommand::class => ReflectionFactory::class,

                    Logger::class => MonologFactory::class,

                    TranslatorInterface::class => TranslatorFactory::class,

                    CountProcessableCommand::class => ReflectionFactory::class,
                    CountProcessingCommand::class => ReflectionFactory::class,
                    ProcessCommand::class => ProcessCommandFactory::class,
                    QuitCommand::class => ReflectionFactory::class,
                    ReanimateCommand::class => ReflectionFactory::class,
                ],
            ],
            'laminas-cli' => [
                'commands' => [
                    'cache.clear' => ClearCommand::class,

                    'documentor.write' => WriteCommand::class,

                    'service.update' => UpdateCommand::class,
                    'service.cleanUp' => CleanUpCommand::class,

                    'queue.countProcessable' => CountProcessableCommand::class,
                    'queue.countProcessing' => CountProcessingCommand::class,
                    'queue.process' => ProcessCommand::class,
                    'queue.quit' => QuitCommand::class,
                    'queue.reanimate' => ReanimateCommand::class,
                ],
            ],
            'cors' => [
                'default' => [
                    'methods' => [
                        'post',
                        'put',
                        'patch',
                        'query',
                        'delete',
                    ],
                    'headers' => [
                        'Accept-Language',
                        'Authorization',
                        'Content-Type',
                        'If-Match',
                        'x-build',
                    ],
                    'maxAge' => 3_600,
                ],
            ],
        ];
    }
}
