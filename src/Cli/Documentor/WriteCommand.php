<?php

declare(strict_types=1);

namespace LesAbstractService\Cli\Documentor;

use Override;
use Throwable;
use JsonException;
use LesValueObject\String\UserAgent;
use LesValueObject\String\Format\Ip;
use LesValueObject\String\PhoneNumber;
use LesValueObject\String\Format\Date;
use LesValueObject\String\Format\Uri\Https;
use LesValueObject\String\Format\SearchTerm;
use LesValueObject\String\Format\EmailAddress;
use LesDocumentor\Route\Document\RouteDocument;
use LesDocumentor\Route\RouteDocumentor;
use LesValueObject\String\Format\Resource\Type;
use LesDocumentor\Type\Document\AnyTypeDocument;
use LesDocumentor\Type\Document\BoolTypeDocument;
use LesDocumentor\Type\Document\NullTypeDocument;
use LesDocumentor\Type\Document\UnionTypeDocument;
use LesValueObject\String\Format\Resource\Identifier;
use LesDocumentor\Type\Document\Composite\Key\AnyKey;
use LesDocumentor\Type\Document\ReferenceTypeDocument;
use LesDocumentor\Type\Document\CollectionTypeDocument;
use LesDocumentor\Type\Document\CompositeTypeDocument;
use LesDocumentor\Type\Document\EnumTypeDocument;
use LesDocumentor\Type\Document\NumberTypeDocument;
use LesDocumentor\Type\Document\StringTypeDocument;
use LesDocumentor\Type\Document\TypeDocument;
use LesResource\Model\ResourceModel;
use LesValueObject\Composite;
use LesValueObject\Enum;
use LesValueObject\Number;
use LesDocumentor\Type\Document\Composite\Key\ExactKey;
use LesDocumentor\Type\Document\Composite\Key\RegexKey;
use LesValueObject\String\Format\AbstractRegexStringFormatValueObject;
use ReflectionException;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class WriteCommand extends Command
{
    private const array RESPONSE_MESSAGE = [
        200 => 'Ok, see content',
        201 => 'Resource created',
        202 => 'Accepted, action will be done in due time',
        204 => 'Call successful, nothing to output',
        400 => 'Bad request, see body for more info',
        401 => 'Authentication failed',
        403 => 'Forbidden to do this request',
        404 => 'Resource not found',
        405 => 'HTTP method not allowed',
        409 => 'Conflict, could not process request',
        422 => 'Given body is not valid',
        429 => 'Too many requests',
        500 => 'Internal error, try later or seek contact',
    ];

    private const array SHARED_REFERENCES = [
        // Composite
        Composite\Occurred::class,
        Composite\Activity::class,
        Composite\Content::class,
        Composite\ForeignReference::class,
        Composite\Paginate::class,
        // Enum
        Enum\OrderDirection::class,
        // Number
        Number\Int\Date\Day::class,
        Number\Int\Date\MilliTimestamp::class,
        Number\Int\Date\Month::class,
        Number\Int\Date\Timestamp::class,
        Number\Int\Date\Week::class,
        Number\Int\Date\Year::class,
        Number\Int\Time\Hour::class,
        Number\Int\Time\Minute::class,
        Number\Int\Time\Second::class,
        Number\Int\Negative::class,
        Number\Int\Positive::class,
        Number\Int\Unsigned::class,
        // String
        Identifier::class,
        Type::class,
        Https::class,
        Date::class,
        EmailAddress::class,
        Ip::class,
        SearchTerm::class,
        PhoneNumber::class,
        UserAgent::class,
    ];

    /**
     * @param array<array<mixed>> $routes
     */
    public function __construct(
        private readonly RouteDocumentor $routeDocumentor,
        private readonly array $routes,
        private readonly string $fileLocation,
        private readonly string $baseUri,
        private readonly ?string $name,
    ) {
        parent::__construct($name);
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     */
    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $document = $this->getBaseDocument();
        assert(is_array($document['paths']));

        $document['paths'] = $this->composePaths();

        $schemaComponents = $this->composeSchemaComponents();
        ksort($schemaComponents);

        $document['components'] = [
            'schemas' => $schemaComponents,
        ];

        assert(is_array($document['info']));
        $document['info']['version'] = md5(json_encode($document, flags: JSON_THROW_ON_ERROR));
        $json = json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        file_put_contents($this->fileLocation, $json);

        return self::SUCCESS;
    }

    /**
     * @return array<mixed>
     */
    private function getBaseDocument(): array
    {
        return [
            'openapi' => '3.1.0',
            'info' => [
                'title' => $this->name,
            ],
            'servers' => [
                ['url' => $this->baseUri],
            ],
            'paths' => [],
            'tags' => $this->getResourceTags(),
        ];
    }

    /**
     * @return array<array{name: string}>
     */
    private function getResourceTags(): array
    {
        $tags = [];

        foreach ($this->routes as $route) {
            if (isset($route['resource']) && is_string($route['resource'])) {
                $tags[$route['resource']] = ['name' => $route['resource']];
            }
        }

        return array_values($tags);
    }

    /**
     * @return array<mixed>
     */
    private function composePaths(): array
    {
        $paths = [];

        foreach ($this->routes as $route) {
            try {
                $routeDocument = $this->routeDocumentor->document($route);

                if (!isset($paths[(string)$routeDocument->path])) {
                    $paths[(string)$routeDocument->path] = [];
                }

                $paths[(string)$routeDocument->path][$routeDocument->method->value] = $this->composePathDocument($routeDocument);
            } catch (Throwable $e) {
                $path = isset($route['path']) && is_string($route['path'])
                    ? $route['path']
                    : '??';

                throw new RuntimeException(
                    "Failed on path '{$path}'",
                    previous: $e,
                );
            }
        }

        return $paths;
    }

    /**
     * @return array<mixed>
     *
     * @throws ReflectionException
     */
    private function composeSchemaComponents(): array
    {
        $document = [];

        foreach ($this->getSchemas() as $schema) {
            $reference = $schema->getReference();

            if (is_string($reference)) {
                if (isset($document[$this->getReferenceName($reference)])) {
                    if (!isset($document[$this->getReferenceName($reference)]['$ref'])) {
                        continue;
                    }

                    if ($schema instanceof ReferenceTypeDocument) {
                        continue;
                    }
                }

                $document[$this->getReferenceName($reference)] = $this->composeTypeDocument($schema, false);
            }
        }

        return $document;
    }

    /**
     * Dont use nested calls, this prevents recursion within the routes
     *
     * @return iterable<TypeDocument>
     */
    private function getSchemas(): iterable
    {
        foreach ($this->routes as $route) {
            $routeDocument = $this->routeDocumentor->document($route);
            $routeSchemas = [];

            $docs = [$routeDocument->input];

            foreach ($routeDocument->responses as $response) {
                if ($response->output) {
                    $docs[] = $response->output;
                }
            }

            while (count($docs) > 0) {
                $doc = array_shift($docs);

                $reference = $doc->getReference();

                if ($reference !== null) {
                    if (array_key_exists($reference, $routeSchemas)) {
                        continue;
                    }

                    if ($this->isReference($doc)) {
                        $routeSchemas[$reference] = UnionTypeDocument::nullable($doc);
                    }
                }

                if ($doc instanceof CompositeTypeDocument) {
                    foreach ($doc->properties as $property) {
                        $docs[] = $property->type;
                    }
                } elseif ($doc instanceof CollectionTypeDocument) {
                    $docs[] = $doc->item;
                } elseif ($doc instanceof UnionTypeDocument) {
                    $docs = array_merge($docs, $doc->subTypes);
                }
            }

            yield from $routeSchemas;
        }
    }

    /**
     * @return array<mixed>
     *
     * @throws ReflectionException
     */
    private function composePathDocument(RouteDocument $routeDocument): array
    {
        return [
            'deprecated' => $routeDocument->deprecated !== null,
            'requestBody' => [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => $this->composeTypeDocument($routeDocument->input, true),
                    ],
                ],
            ],
            'responses' => $this->composeResponses($routeDocument),
        ];
    }

    /**
     * @return array<mixed>
     * @throws ReflectionException
     */
    private function composeTypeDocument(TypeDocument $typeDocument, bool $useRef): array
    {
        if ($typeDocument instanceof NullTypeDocument) {
            return ['type' => 'null'];
        }

        if ($useRef && $this->isReference($typeDocument)) {
            $reference = $typeDocument->getReference();
            assert(is_string($reference));

            $name = $this->getReferenceName($reference);
            $document = ['$ref' => "#/components/schemas/{$name}"];
        } else {
            $document = match ($typeDocument::class) {
                AnyTypeDocument::class => $this->composeFromAnyTypeDocument(),
                BoolTypeDocument::class => ['type' => 'boolean'],
                CollectionTypeDocument::class => $this->composeFromCollectionTypeDocument($typeDocument),
                CompositeTypeDocument::class => $this->composeFromCompositeTypeDocument($typeDocument),
                EnumTypeDocument::class => $this->composeFromEnumTypeDocument($typeDocument),
                NumberTypeDocument::class => $this->composeFromNumberTypeDocument($typeDocument),
                ReferenceTypeDocument::class => $this->composeFromReferenceTypeDocument($typeDocument),
                StringTypeDocument::class => $this->composeFromStringTypeDocument($typeDocument),
                UnionTypeDocument::class => $this->composeFromUnionTypeDocument($typeDocument),
                default => throw new RuntimeException($typeDocument::class),
            };
        }

        if ($typeDocument->getDescription() !== null) {
            $document['description'] = $typeDocument->getDescription();
        }

        return $document;
    }

    private function isReference(TypeDocument $typeDocument): bool
    {
        $class = $typeDocument->getReference();

        return $class !== null
            &&
            (
                in_array($class, self::SHARED_REFERENCES, true)
                ||
                preg_match(
                    '#^([a-z]+\\\\){2,}(Model|Repository)\\\\#i',
                    $class
                ) === 1
            );
    }

    private function getReferenceName(string $class): string
    {
        if (
            str_contains($class, '\\Model\\')
            &&
            !is_subclass_of($class, ResourceModel::class)
            &&
            preg_match(
                '/^[a-zA-Z]+\\\\(?<model>[a-zA-Z]+(\\\\[a-zA-Z]+)*)\\\\Model\\\\(?<part>[a-zA-Z]+(\\\\[a-zA-Z]+)*)$/',
                $class,
                $matches,
            )
        ) {
            $model = str_replace('\\', '', $matches['model']);
            $part = preg_replace_callback(
                '/\\\\(.)/',
                static function (array $input) {
                    return '.' . strtolower($input[1]);
                },
                $matches['part'],
            );

            assert(is_string($part));

            return lcfirst($model) . '.' . lcfirst($part);
        }

        if (
            str_contains($class, '\\Repository\\')
            &&
            preg_match(
                '/^[a-zA-Z]+\\\\(?<model>[a-zA-Z]+(\\\\[a-zA-Z]+)*)\\\\Repository\\\\[a-zA-Z]+\\\\(?<part>[a-zA-Z]+(\\\\[a-zA-Z]+)*)$/',
                $class,
                $matches,
            )
        ) {
            $model = str_replace('\\', '', $matches['model']);
            $part = preg_replace_callback(
                '/\\\\(.)/',
                static function (array $input) {
                    return '.' . strtolower($input[1]);
                },
                $matches['part'],
            );

            assert(is_string($part));

            return lcfirst($model) . '.repository.' . lcfirst($part);
        }

        $parts = explode('\\', $class);

        return lcfirst(array_pop($parts));
    }

    /**
     * @return array<mixed>
     */
    private function composeFromAnyTypeDocument(): array
    {
        return [
            'oneOf' => [
                ['type' => 'array'],
                ['type' => 'boolean'],
                ['type' => 'integer'],
                ['type' => 'null'],
                ['type' => 'number'],
                ['type' => 'object'],
                ['type' => 'string'],
            ],
        ];
    }

    /**
     * @return array<mixed>
     *
     * @throws ReflectionException
     */
    private function composeFromCollectionTypeDocument(CollectionTypeDocument $typeDocument): array
    {
        $document = [
            'type' => 'array',
            'items' => $this->composeTypeDocument($typeDocument->item, true),
        ];

        if (is_int($typeDocument->getMaxDepth())) {
            $document['x-les-maxDepth'] = $typeDocument->getMaxDepth();
        }

        if ($typeDocument->size) {
            $document['minItems'] = $typeDocument->size->minimal;
            $document['maxItems'] = $typeDocument->size->maximal;
        }

        return $document;
    }

    /**
     * @return array<mixed>
     *
     * @throws ReflectionException
     */
    private function composeFromCompositeTypeDocument(CompositeTypeDocument $typeDocument): array
    {
        $properties = $required = [];
        $compDocument = [
            'type' => 'object',
            'additionalProperties' => $typeDocument->allowExtraProperties,
            'properties' => $properties,
            'required' => $required,
        ];

        if (is_int($typeDocument->getMaxDepth())) {
            $compDocument['x-les-maxDepth'] = $typeDocument->getMaxDepth();
        }

        foreach ($typeDocument->properties as $property) {
            $propDocument = $this->composeTypeDocument($property->type, true);
            $append = [];

            if ($property->deprecated) {
                $append['deprecated'] = $property->deprecated;
            }

            if ($property->required === false) {
                $append['default'] = $property->default;
            }

            if (count($append) > 0) {
                if (isset($propDocument['$ref'])) {
                    $propDocument = ['allOf' => [$propDocument]];
                }

                $propDocument = [...$propDocument, ...$append];
            }

            if ($property->key instanceof ExactKey) {
                if ($property->required) {
                    $compDocument['required'][] = $property->key->value;
                }

                $compDocument['properties'][$property->key->value] = $propDocument;
            } elseif ($property->key instanceof RegexKey || $property->key instanceof AnyKey) {
                if (!isset($compDocument['patternProperties'])) {
                    $compDocument['patternProperties'] = [];
                }

                $pattern = $property->key instanceof RegexKey
                    ? $property->key->pattern
                    : '.*';

                $compDocument['patternProperties'][$pattern] = $propDocument;
            }
        }

        return $compDocument;
    }

    /**
     * @return array<mixed>
     */
    private function composeFromEnumTypeDocument(EnumTypeDocument $typeDocument): array
    {
        return [
            'type' => 'string',
            'enum' => $typeDocument->cases,
        ];
    }

    /**
     * @return array<mixed>
     */
    private function composeFromNumberTypeDocument(NumberTypeDocument $typeDocument): array
    {
        $document = [
            'type' => is_int($typeDocument->multipleOf)
                ? 'integer'
                : 'number',
        ];

        if ($typeDocument->multipleOf !== null) {
            $document['multipleOf'] = $typeDocument->multipleOf;
        }

        if ($typeDocument->format !== null) {
            $document['format'] = $typeDocument->format;
        }

        if ($typeDocument->range) {
            $document['minimum'] = $typeDocument->range->minimal;
            $document['maximum'] = $typeDocument->range->maximal;
        }

        return $document;
    }

    /**
     * @return array<mixed>
     */
    private function composeFromStringTypeDocument(StringTypeDocument $typeDocument): array
    {
        $reference = $typeDocument->getReference();
        $document = ['type' => 'string'];

        if ($typeDocument->length) {
            $document['minLength'] = $typeDocument->length->minimal;
            $document['maxLength'] = $typeDocument->length->maximal;
        }

        if ($typeDocument->format !== null) {
            $document['format'] = $typeDocument->format;
        }

        if ($reference !== null) {
            if (!class_exists($reference)) {
                throw new RuntimeException("Reference '{$reference}' unknown");
            }

            if (is_subclass_of($reference, AbstractRegexStringFormatValueObject::class)) {
                $document['pattern'] = $reference::getRegularExpression();
            }
        }

        return $document;
    }

    /**
     * @return array<mixed>
     */
    private function composeFromReferenceTypeDocument(ReferenceTypeDocument $typeDocument): array
    {
        $reference = $typeDocument->getReference();

        if (!is_string($reference)) {
            throw new RuntimeException("No reference provided");
        }

        return ['$ref' => '#/components/schemas/' . $this->getReferenceName($reference)];
    }

    /**
     * @return array<mixed>
     *
     * @throws ReflectionException
     */
    private function composeFromUnionTypeDocument(UnionTypeDocument $typeDocument): array
    {
        return [
            'anyOf' => array_map(
                fn(TypeDocument $subType) => $this->composeTypeDocument($subType, true),
                $typeDocument->subTypes,
            ),
        ];
    }

    /**
     * @return array<mixed>
     *
     * @throws ReflectionException
     */
    private function composeResponses(RouteDocument $routeDocument): array
    {
        /** @var array<int, array<mixed>> $responses */
        $responses = [];

        foreach ($routeDocument->responses as $response) {
            $key = $response->code->value;

            if ($response->output) {
                $responses[$key] = [
                    'description' => self::RESPONSE_MESSAGE[$key],
                    'content' => [
                        'application/json' => [
                            'schema' => $this->composeTypeDocument($response->output, true),
                        ],
                    ],
                ];
            } else {
                $responses[$key] = [
                    'description' => self::RESPONSE_MESSAGE[$key],
                ];
            }
        }

        return $responses;
    }
}
