<?php
declare(strict_types=1);

namespace LessAbstractService\Http\Resource\Handler\Command\Response;

use LessValueObject\String\Format\Resource\Type;
use LessValueObject\Composite\AbstractCompositeValueObject;
use LessValueObject\String\Format\Resource\Identifier;

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
