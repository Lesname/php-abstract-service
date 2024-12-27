<?php
declare(strict_types=1);

namespace LessAbstractService\Http\Resource\Handler\Response;

use LessValueObject\String\Format\Resource\Type;
use LessValueObject\String\Format\Resource\Identifier;
use LessValueObject\Composite\AbstractCompositeValueObject;

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
