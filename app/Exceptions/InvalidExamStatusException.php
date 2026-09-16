<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class InvalidExamStatusException extends Exception
{
    public function __construct(
        ?string $message = 'Exam must be in draft status.',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
