<?php
declare(strict_types=1);

namespace LessAbstractService\Cli\Documentor\Attribute;

use Attribute;

/**
 * @deprecated use DocStringFormat
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Format
{
    public function __construct(
        public readonly string $name,
    ) {}
}
