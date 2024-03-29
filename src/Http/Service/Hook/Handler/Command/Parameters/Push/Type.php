<?php
declare(strict_types=1);

namespace LessAbstractService\Http\Service\Hook\Handler\Command\Parameters\Push;

use LessValueObject\Enum\EnumValueObject;
use LessValueObject\Enum\Helper\EnumValueHelper;

/**
 * @psalm-immutable
 *
 * @deprecated
 */
enum Type: string implements EnumValueObject
{
    use EnumValueHelper;

    case Verification = 'verification';
    case Event = 'event';
}
