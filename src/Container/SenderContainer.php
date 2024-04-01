<?php
declare(strict_types=1);

namespace LessAbstractService\Container;

use Psr\Container\ContainerInterface;
use LessValueObject\Composite\ForeignReference;
use LessAbstractService\Container\Exception\UnknownSender;

final class SenderContainer implements ContainerInterface
{
    /**
     * @param array<string, ForeignReference> $senders
     */
    public function __construct(private readonly array $senders)
    {}

    public function get(string $id): ForeignReference
    {
        if (!$this->has($id)) {
            throw new UnknownSender($id);
        }

        return $this->senders[$id];
    }

    public function has(string $id)
    {
        return array_key_exists($id, $this->senders);
    }
}
