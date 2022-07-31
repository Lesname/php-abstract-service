<?php
declare(strict_types=1);

namespace LessAbstractService\Router\Route;

enum Type
{
    case Command;
    case Query;
}
