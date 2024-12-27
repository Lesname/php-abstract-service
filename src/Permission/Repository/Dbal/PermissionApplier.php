<?php
declare(strict_types=1);

namespace LessAbstractService\Permission\Repository\Dbal;

use LessResource\Repository\Dbal\Applier\AbstractResourceApplier;

final class PermissionApplier extends AbstractResourceApplier
{
    public function __construct(private readonly string $serviceName)
    {}

    protected function getFields(): array
    {
        return [
            'id' => 'p.id',
            'type' => "'{$this->serviceName}.permission'",
            'attributes' => [
                'identity' => [
                    'type' => 'p.identity_type',
                    'id' => 'p.identity_id',
                ],
                'flags' => [
                    'grant' => 'p.flags_grant',
                    'read' => 'p.flags_read',
                    'create' => 'p.flags_create',
                    'update' => 'p.flags_update',
                ],
            ],
        ];
    }

    public function getTableName(): string
    {
        return 'permission';
    }

    public function getTableAlias(): string
    {
        return 'p';
    }
}
