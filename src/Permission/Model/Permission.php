<?php

declare(strict_types=1);

namespace LesAbstractService\Permission\Model;

use LesResource\Model\ResourceModel;
use LesResource\Model\AbstractResourceModel;
use LesValueObject\String\Format\Resource\Type;
use LesValueObject\String\Format\Resource\Identifier;

/**
 * @psalm-immutable
 */
final class Permission extends AbstractResourceModel implements ResourceModel
{
    public function __construct(
        Identifier $id,
        Type $type,
        public readonly Attributes $attributes,
    ) {
        parent::__construct($id, $type);
    }
}
