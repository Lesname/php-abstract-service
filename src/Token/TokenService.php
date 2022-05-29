<?php
declare(strict_types=1);

namespace LessAbstractService\Token;

use LessValueObject\Composite\ForeignReference;
use LessValueObject\Number\Int\Date\Timestamp;
use LessValueObject\String\Format\Resource\Identifier;
use Psr\Http\Message\ServerRequestInterface;

interface TokenService
{
    public function tokenize(
        Identifier $id,
        ForeignReference $subject,
        Timestamp $expire,
        ?ServerRequestInterface $request = null
    ): string;
}
