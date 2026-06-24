<?php

declare(strict_types=1);

namespace App\Exceptions;

class UserNotFoundException extends ApiException
{
    public function __construct(int|string $identifier)
    {
        parent::__construct("User not found for identifier: {$identifier}", 404);
    }
}
