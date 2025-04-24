<?php
declare(strict_types=1);

namespace LesAbstractService\Permission\Model;

use LesResource\Model\ResourceModel;
use LesValueObject\String\Format\Resource\Type;
use LesValueObject\String\Format\Resource\Identifier;
use LesValueObject\Composite\AbstractCompositeValueObject;

/**
 * @psalm-immutable
 */
final class Permission extends AbstractCompositeValueObject implements ResourceModel
{
    public function __construct(
        public readonly Identifier $id,
        public readonly Type $type,
        public readonly Attributes $attributes,
    ) {}
}
