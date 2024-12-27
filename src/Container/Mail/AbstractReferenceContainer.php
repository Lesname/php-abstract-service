<?php
declare(strict_types=1);

namespace LessAbstractService\Container\Mail;

use Psr\Container\ContainerInterface;
use LessValueObject\String\Exception\TooLong;
use LessValueObject\String\Exception\TooShort;
use LessValueObject\Composite\ForeignReference;
use LessValueObject\String\Format\Exception\NotFormat;
use LessAbstractService\Container\Mail\Exception\UnknownReference;

/**
 * @psalm-immutable
 */
abstract class AbstractReferenceContainer implements ContainerInterface
{
    /**
     * @param array<string, array{type: string, id: string}> $references
     */
    public function __construct(private readonly array $references)
    {}

    /**
     * @throws UnknownReference
     * @throws TooLong
     * @throws TooShort
     * @throws NotFormat
     */
    public function get(string $id): ForeignReference
    {
        if (!$this->has($id)) {
            throw new UnknownReference($id);
        }

        return ForeignReference::fromArray(
            [
                'type' => $this->references[$id]['type'],
                'id' => $this->references[$id]['id'],
            ],
        );
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->references);
    }
}
