<?php
declare(strict_types=1);
namespace App\Services;

final class FieldValidationException extends \DomainException
{
    public function __construct(public string $field, string $message)
    {
        parent::__construct($message);
    }
}
