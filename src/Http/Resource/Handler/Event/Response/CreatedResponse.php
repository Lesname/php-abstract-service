<?php
declare(strict_types=1);

namespace LessAbstractService\Http\Resource\Handler\Event\Response;

use LessValueObject\Composite\AbstractCompositeValueObject;
use LessValueObject\String\Format\Resource\Identifier;

/**
 * @psalm-immutable
 */
final class CreatedResponse extends AbstractCompositeValueObject
{
    public function __construct(public readonly Identifier $id)
    {}
}
