<?php
declare(strict_types=1);

namespace LesAbstractService\Container\Mail;

use Override;
use Psr\Container\ContainerInterface;
use LesValueObject\String\Exception\TooLong;
use LesValueObject\String\Exception\TooShort;
use LesValueObject\Composite\ForeignReference;
use LesValueObject\String\Format\Exception\NotFormat;
use LesAbstractService\Container\Mail\Exception\UnknownReference;

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
    #[Override]
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

    #[Override]
    public function has(string $id): bool
    {
        return array_key_exists($id, $this->references);
    }
}
