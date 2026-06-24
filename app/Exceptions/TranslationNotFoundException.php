<?php

declare(strict_types=1);

namespace App\Exceptions;

class TranslationNotFoundException extends ApiException
{
    public function __construct(int $id)
    {
        parent::__construct("Translation not found for identifier: {$id}", 404);
    }
}
