<?php
declare(strict_types=1);

namespace BsBase\Cli\Documentor;

use BsLibrary\Vo\String\Format\Ip;
use BsLibrary\Vo\String\PhoneNumber;
use BsLibrary\Vo\Enum\OrderDirection;
use BsLibrary\Vo\Number\Int\Date\Day;
use BsLibrary\Vo\Number\Int\Negative;
use BsLibrary\Vo\Number\Int\Positive;
use BsLibrary\Vo\Number\Int\Unsigned;
use BsLibrary\Vo\Number\Int\Date\Week;
use BsLibrary\Vo\Number\Int\Date\Year;
use BsLibrary\Vo\Number\Int\Date\Month;
use BsLibrary\Vo\Number\Int\Date\Seconds;
use BsLibrary\Vo\String\Format\Uri\Https;
use BsLibrary\Vo\String\Format\SearchTerm;
use BsLibrary\Vo\String\Format\Resource\Type;
use BsLibrary\Vo\String\Format\Resource\Identifier;
use BsLibrary\Documentor\Response\Document\ResponseDocument;
use BsLibrary\Vo\String\Format\AbstractRegularExpressionStringVo;
use BsLibrary\Documentor\Response\Exception\CannotDeterimineResponse;
use BsLibrary\Documentor\Route\Document\RouteDocument;
use BsLibrary\Documentor\Route\Exception\MissingAllowedMethods;
use BsLibrary\Documentor\Route\Exception\OnlyOneMethodAllowed;
use BsLibrary\Documentor\Route\MezzioRouteDocumentor;
use BsLibrary\Documentor\Type\Document;
use BsLibrary\Documentor\Type\Exception\CannotDetermineInput;
use BsLibrary\Documentor\Type\Exception\NonNamedMethodParamterType;
use BsLibrary\Documentor\Type\Exception\NonNamedType;
use BsLibrary\Documentor\Type\Exception\UnsupportedBuiltIn;
use BsLibrary\Documentor\Type\Exception\UnsupportedType;
use BsLibrary\Documentor\Type\Exception\UnsupportedVo;
use BsLibrary\Helper\JsonHelper;
use BsLibrary\Resource\Model\ResourceModel;
use BsLibrary\Vo\Composite\Activity;
use BsLibrary\Vo\Composite\Content;
use BsLibrary\Vo\Composite\Content\SafeContent;
use BsLibrary\Vo\Composite\Content\UnsafeContent;
use BsLibrary\Vo\Composite\ForeignReference;
use BsLibrary\Vo\Composite\Occurred;
use BsLibrary\Vo\Composite\Paginate;
use BsLibrary\Vo\Number\Int\Date\MilliTimestamp;
use BsLibrary\Vo\Number\Int\Date\Timestamp;
use BsLibrary\Vo\String\Format\AbstractRegexStringFormatVo;
use BsLibrary\Vo\String\Format\Date;
use BsLibrary\Vo\String\Format\EmailAddress;
use BsLibrary\Vo\String\Format\StringFormatVo;
use JsonException;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class WriteCommand extends Command
{
    private const RESPONSE_MESSAGE = [
        200 => 'Ok, see content',
        201 => 'Resource created',
        202 => 'Accepted, action will be done in due time',
        204 => 'Call successfull, nothing to output',
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

    private const DEFAULT_COMPONENTS = [
        OrderDirection::class,
        \BsLibrary\ValueObject\Enum\OrderDirection::class,
        // String type's
        Date::class,
        \BsLibrary\ValueObject\String\Format\Date::class,
        EmailAddress::class,
        \BsLibrary\ValueObject\String\Format\EmailAddress::class,
        Identifier::class,
        \BsLibrary\ValueObject\String\Format\Resource\Identifier::class,
        Https::class,
        \BsLibrary\ValueObject\String\Format\Uri\Https::class,
        Type::class,
        \BsLibrary\ValueObject\String\Format\Resource\Type::class,
        Date::class,
        \BsLibrary\ValueObject\String\Format\Date::class,
        Ip::class,
        \BsLibrary\ValueObject\String\Format\Ip::class,
        SearchTerm::class,
        \BsLibrary\ValueObject\String\Format\SearchTerm::class,
        PhoneNumber::class,
        \BsLibrary\ValueObject\String\PhoneNumber::class,
        // Int type's
        Day::class,
        \BsLibrary\ValueObject\Number\Int\Date\Day::class,
        Month::class,
        \BsLibrary\ValueObject\Number\Int\Date\Month::class,
        Timestamp::class,
        \BsLibrary\ValueObject\Number\Int\Date\Timestamp::class,
        Week::class,
        \BsLibrary\ValueObject\Number\Int\Date\Week::class,
        Year::class,
        \BsLibrary\ValueObject\Number\Int\Date\Year::class,
        Seconds::class,
        \BsLibrary\ValueObject\Number\Int\Date\Seconds::class,
        Negative::class,
        \BsLibrary\ValueObject\Number\Int\Negative::class,
        Positive::class,
        \BsLibrary\ValueObject\Number\Int\Positive::class,
        Unsigned::class,
        \BsLibrary\ValueObject\Number\Int\Unsigned::class,
        MilliTimestamp::class,
        \BsLibrary\ValueObject\Number\Int\Date\MilliTimestamp::class,
        Timestamp::class,
        \BsLibrary\ValueObject\Number\Int\Date\Timestamp::class,
        // Composite
        Content::class,
        \BsLibrary\ValueObject\Composite\Content::class,
        SafeContent::class,
        UnsafeContent::class,
        Activity::class,
        \BsLibrary\ValueObject\Composite\Activity::class,
        Occurred::class,
        \BsLibrary\ValueObject\Composite\Occurred::class,
        Paginate::class,
        \BsLibrary\ValueObject\Composite\Paginate::class,
        ForeignReference::class,
        \BsLibrary\ValueObject\Composite\ForeignReference::class,
    ];

    /**
     * @param array<array<mixed>> $routes
     * @param string $baseUri
     * @param string $name
     */
    public function __construct(
        private readonly array $routes,
        private readonly string $fileLocation,
        private readonly string $baseUri,
        private readonly string $name,
    ) {
        parent::__construct();
    }

    /**
     * @throws CannotDeterimineResponse
     * @throws CannotDetermineInput
     * @throws JsonException
     * @throws MissingAllowedMethods
     * @throws NonNamedMethodParamterType
     * @throws NonNamedType
     * @throws OnlyOneMethodAllowed
     * @throws ReflectionException
     * @throws UnsupportedBuiltIn
     * @throws UnsupportedType
     * @throws UnsupportedVo
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $document = $this->getBaseDocument();
        assert(is_array($document['paths']));

        foreach ($this->routes as $route) {
            if (isset($route['options']['document']) && $route['options']['document'] === false) {
                continue;
            }

            $routeDocument = MezzioRouteDocumentor::document($route);

            $path = $routeDocument->getPath();
            $method = strtolower($routeDocument->getMethod());

            if (!isset($document['paths'][$path])) {
                $document['paths'][$path] = [];
            }

            assert(is_array($document['paths'][$path]));

            $document['paths'][$path][$method] = $this->composePathDocument($routeDocument);

            $schemaComponents = $this->composeSchemaComponents($routeDocument->getInput());

            foreach ($routeDocument->getResponses() as $respons) {
                $output = $respons->getOutput();

                if ($output) {
                    $schemaComponents = array_merge($schemaComponents, $this->composeSchemaComponents($output));
                }
            }

            if (count($schemaComponents) > 0) {
                if (!isset($document['components'])) {
                    $document['components'] = [];
                }

                assert(is_array($document['components']));

                if (!isset($document['components']['schemas'])) {
                    $document['components']['schemas'] = [];
                }

                assert(is_array($document['components']['schemas']));

                $document['components']['schemas'] = array_merge(
                    $document['components']['schemas'],
                    $schemaComponents,
                );
            }
        }

        assert(is_array($document['info']));
        $document['info']['version'] = md5(json_encode($document, JSON_THROW_ON_ERROR));

        file_put_contents(
            $this->fileLocation,
            JsonHelper::encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        );

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
                'contact' => [
                    'name' => 'Development',
                    'email' => 'development@boekscout.nl',
                ],
            ],
            'servers' => [
                ['url' => $this->baseUri],
            ],
            'paths' => [],
            'tags' => $this->getBaseTags(),
        ];
    }

    /**
     * @return array<array<string, string>>
     */
    private function getBaseTags(): array
    {
        $tagged = [];
        $tags = [];

        foreach ($this->routes as $route) {
            assert(is_array($route['options']));

            if (isset($route['options']['document']) && $route['options']['document'] === false) {
                continue;
            }

            $resourceName = $route['options']['resourceName'];
            assert(is_string($resourceName));

            if (in_array($resourceName, $tagged, true)) {
                continue;
            }

            $tagged[] = $resourceName;
            $tags[] = ['name' => $resourceName];
        }

        return $tags;
    }

    /**
     * @return array<mixed>
     *
     * @throws ReflectionException
     */
    private function composePathDocument(RouteDocument $routeDocument): array
    {
        $description = $routeDocument->getDescription() ?? '';
        $document = [
            'tags' => [
                $routeDocument->getResourceName(),
                $routeDocument->getOptions()['type'],
            ],
        ];

        if ($routeDocument->getDeprecated()) {
            $document['deprecated'] = true;
            $description .= PHP_EOL
                . PHP_EOL
                . "![Deprecated] {$routeDocument->getDeprecated()}";
        }

        if (trim($description)) {
            $document['description'] = trim($description);
        }

        $document['requestBody'] = [
            'content' => [
                'application/json' => [
                    'schema' => $this->composeTypeDocument(
                        $routeDocument->getInput(),
                        true,
                    ),
                ],
            ],
            'required' => true,
        ];

        $document['responses'] = [];

        foreach ($routeDocument->getResponses() as $response) {
            $document['responses'][$response->getCode()] = $this->composeResponse($response);
        }

        return $document;
    }

    /**
     * Checks the document for reference that are components
     *
     * @return array<string, mixed>
     *
     * @throws ReflectionException
     */
    private function composeSchemaComponents(Document\TypeDocument $document): array
    {
        $reference = $document->getReference();

        $schemaComponents = [];

        if ($document instanceof Document\CompositeTypeDocument) {
            foreach ($document->properties as $property) {
                $schemaComponents = array_merge(
                    $schemaComponents,
                    $this->composeSchemaComponents($property->type),
                );
            }
        } elseif ($document instanceof Document\CollectionTypeDocument && $document->itemDocument) {
            $schemaComponents = array_merge(
                $schemaComponents,
                $this->composeSchemaComponents($document->itemDocument),
            );
        }

        if ($reference) {
            if (
                in_array($reference, self::DEFAULT_COMPONENTS, true)
                ||
                (str_contains($reference, '\\Model\\') && !str_contains($reference, '\\Includes\\'))
                ||
                str_contains($reference, '\\Repository\\')
                ||
                preg_match('/\\\\Parameters\\\\[a-zA-Z]+\\\\/', $reference)
            ) {
                $doc = $this->composeTypeDocument($document, false, false);

                $schemaComponents[$this->getDocumentKey($document)] = $doc;
            }
        }

        return $schemaComponents;
    }

    /**
     * @return array<mixed>
     *
     * @throws ReflectionException
     */
    private function composeTypeDocument(Document\TypeDocument $typeDocument, bool $allowReference, bool $nullable = true): array
    {
        $reference = $typeDocument->getReference();

        if ($allowReference && $reference) {
            if (
                in_array($reference, self::DEFAULT_COMPONENTS, true)
                ||
                (str_contains($reference, '\\Model\\') && !str_contains($reference, '\\Includes\\'))
                ||
                str_contains($reference, '\\Repository\\')
                ||
                preg_match('/\\\\Parameters\\\\[a-zA-Z]+\\\\/', $reference)
            ) {
                $name = $this->getDocumentKey($typeDocument);
                $document = ['$ref' => "#/components/schemas/{$name}"];

                if ($nullable && $typeDocument->isNullable()) {
                    $document = [
                        'anyOf' => [
                            $document,
                            ['type' => 'null'],
                        ],
                    ];
                }

                return $document;
            }
        }

        $document = match (true) {
            $typeDocument instanceof Document\BoolTypeDocument => $this->composeBoolDocument($typeDocument),
            $typeDocument instanceof Document\CollectionTypeDocument => $this->composeCollectionDocument($typeDocument),
            $typeDocument instanceof Document\CompositeTypeDocument => $this->composeCompositeDocument($typeDocument),
            $typeDocument instanceof Document\EnumTypeDocument => $this->composeEnumDocument($typeDocument),
            $typeDocument instanceof Document\NumberTypeDocument => $this->composeNumberDocument($typeDocument),
            $typeDocument instanceof Document\StringTypeDocument => $this->composeStringDocument($typeDocument),
            default => throw new RuntimeException(),
        };

        if ($nullable && $typeDocument->isNullable()) {
            $document['type'] = [$document['type'], 'null'];
        }

        return $document;
    }

    /**
     * @return array<mixed>
     */
    private function composeBaseTypeDocument(Document\TypeDocument $typeDocument): array
    {
        $document = [];
        $description = $typeDocument->getDescription() ?? '';

        if (trim($description)) {
            $document['description'] = trim($description);
        }

        return $document;
    }

    /**
     * @return array<mixed>
     */
    private function composeBoolDocument(Document\BoolTypeDocument $typeDocument): array
    {
        $document = $this->composeBaseTypeDocument($typeDocument);
        $document['type'] = 'boolean';

        return $document;
    }

    /**
     * @return array<mixed>
     *
     * @throws ReflectionException
     */
    private function composeCollectionDocument(Document\CollectionTypeDocument $typeDocument): array
    {
        $document = $this->composeBaseTypeDocument($typeDocument);
        $document['type'] = 'array';

        if ($typeDocument->minLength !== null) {
            $document['minItems'] = $typeDocument->minLength;
        }

        if ($typeDocument->maxLength !== null) {
            $document['maxItems'] = $typeDocument->maxLength;
        }

        if ($typeDocument->itemDocument) {
            $document['items'] = $this->composeTypeDocument($typeDocument->itemDocument, true);
        } else {
            $document['items'] = [
                'oneOf' => [
                    ['type' => 'string'],
                    ['type' => 'integer'],
                    ['type' => 'number'],
                    ['type' => 'boolean'],
                    ['type' => 'object'],
                    ['type' => 'array'],
                ],
            ];
        }

        return $document;
    }

    /**
     * @return array<mixed>
     *
     * @throws ReflectionException
     */
    private function composeCompositeDocument(Document\CompositeTypeDocument $typeDocument): array
    {
        $document = $this->composeBaseTypeDocument($typeDocument);
        $document['type'] = 'object';
        $document['additionalProperties'] = $typeDocument->allowExtraProperties;


        $required = [];
        $properties = [];

        foreach ($typeDocument->properties as $name => $property) {
            $propDocument = $this->composeTypeDocument($property->type, true);

            if ($property->required) {
                $required[] = $name;
            } elseif ($property->default !== null) {
                $propDocument['default'] = $property->default;
            }

            if ($property->deprecated) {
                $propDocument['deprecated'] = true;
            }

            $properties[$name] = $propDocument;
        }

        $document['properties'] = $properties;
        $document['required'] = $required;

        return $document;
    }

    /**
     * @return array<mixed>
     */
    private function composeEnumDocument(Document\EnumTypeDocument $typeDocument): array
    {
        $document = $this->composeBaseTypeDocument($typeDocument);
        $document['type'] = 'string';
        $document['enum'] = $typeDocument->cases;

        return $document;
    }

    /**
     * @return array<mixed>
     */
    private function composeNumberDocument(Document\NumberTypeDocument $typeDocument): array
    {
        $document = $this->composeBaseTypeDocument($typeDocument);
        $document['type'] = 'number';

        if ($typeDocument->minValue !== null) {
            $document['minimum'] = $typeDocument->minValue;
        }


        if ($typeDocument->maxValue !== null) {
            $document['maximum'] = $typeDocument->maxValue;
        }

        return $document;
    }

    /**
     * @param Document\StringTypeDocument $typeDocument
     *
     * @return array<mixed>
     * @throws ReflectionException
     */
    private function composeStringDocument(Document\StringTypeDocument $typeDocument): array
    {
        $document = $this->composeBaseTypeDocument($typeDocument);
        $document['type'] = 'string';

        if ($typeDocument->minLength) {
            $document['minLength'] = $typeDocument->minLength;
        }

        if ($typeDocument->maxLength) {
            $document['maxLength'] = $typeDocument->maxLength;
        }

        if ($typeDocument->reference && is_subclass_of($typeDocument->reference, StringFormatVo::class)) {
            if (is_subclass_of($typeDocument->reference, AbstractRegexStringFormatVo::class)) {
                $document['pattern'] = $typeDocument->reference::getRegexPattern();
            } elseif (is_subclass_of($typeDocument->reference, AbstractRegularExpressionStringVo::class)) {
                $document['pattern'] = $typeDocument->reference::getRegularExpression();
            } else {
                $document['format'] = (new ReflectionClass($typeDocument->reference))->getShortName();
            }
        }

        return $document;
    }

    /**
     * @return array<mixed>
     *
     * @throws ReflectionException
     */
    private function composeResponse(ResponseDocument $responseDocument): array
    {
        $httpCode = $responseDocument->getCode();

        $document = [
            'description' => self::RESPONSE_MESSAGE[$httpCode]
                ?? throw new RuntimeException("Code {$httpCode} not supported"),
        ];

        $output = $responseDocument->getOutput();

        if ($output) {
            $document['content']['application/json']['schema'] = $this->composeTypeDocument($output, true);
        }

        return $document;
    }

    private function getDocumentKey(Document\TypeDocument $document): string
    {
        $reference = $document->getReference();
        assert(is_string($reference));

        if (
            str_contains($reference, '\\Model\\')
            &&
            !is_subclass_of($reference, ResourceModel::class)
            &&
            preg_match(
                '/^[a-zA-Z]+\\\\(?<model>[a-zA-Z]+(\\\\[a-zA-Z]+)*)\\\\Model\\\\(?<part>[a-zA-Z]+(\\\\[a-zA-Z]+)*)$/',
                $reference,
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
            str_contains($reference, '\\Repository\\')
            &&
            preg_match(
                '/^[a-zA-Z]+\\\\(?<model>[a-zA-Z]+(\\\\[a-zA-Z]+)*)\\\\Repository\\\\[a-zA-Z]+\\\\(?<part>[a-zA-Z]+(\\\\[a-zA-Z]+)*)$/',
                $reference,
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

        if (
            str_contains($reference, '\\Parameters\\')
            &&
            preg_match(
                '/^[a-zA-Z]+\\\\(?<model>[a-zA-Z]+(\\\\[a-zA-Z]+)*)\\\\Parameters\\\\(?<for>[a-zA-Z]+)\\\\(?<part>[a-zA-Z]+(\\\\[a-zA-Z]+)*)$/',
                $reference,
                $matches,
            )
        ) {
            $model = str_replace(['\\Event\\', '\\Http\\Query', '\\Http\\Command'], '\\', $matches['model']);
            $model = str_replace('\\', '', $model);
            $part = preg_replace_callback(
                '/\\\\(.)/',
                static function (array $input) {
                    return '.' . strtolower($input[1]);
                },
                $matches['part'],
            );

            assert(is_string($part));

            $part = str_ends_with($part, 'Parameters')
                ? substr($part, 0, -10)
                : $part;

            return lcfirst($model) . '.' . lcfirst($matches['for']) . '.' . lcfirst($part);
        }

        $parts = explode('\\', $reference);

        return lcfirst(array_pop($parts));
    }
}
