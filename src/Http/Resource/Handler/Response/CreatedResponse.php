<?php
declare(strict_types=1);

namespace LesAbstractService\Http\Resource\Handler\Response;

use LesValueObject\String\Format\Resource\Type;
use LesValueObject\String\Format\Resource\Identifier;
use LesValueObject\Composite\AbstractCompositeValueObject;

/**
 * @psalm-immutable
 */
final class CreatedResponse extends AbstractCompositeValueObject
{
    public function __construct(
        public readonly Identifier $id,
        public readonly Type $type,
    ) {}
}
