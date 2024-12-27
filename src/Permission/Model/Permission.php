<?php
declare(strict_types=1);

namespace LessAbstractService\Permission\Model;

use LessResource\Model\ResourceModel;
use LessValueObject\String\Format\Resource\Type;
use LessValueObject\String\Format\Resource\Identifier;
use LessValueObject\Composite\AbstractCompositeValueObject;

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
