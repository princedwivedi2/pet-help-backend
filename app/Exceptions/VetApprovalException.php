<?php

namespace App\Exceptions;

class VetApprovalException extends \RuntimeException
{
    /**
     * @var string[]
     */
    private array $missingFields;

    /**
     * @param string[] $missingFields
     */
    public function __construct(array $missingFields, string $message = 'Vet profile is incomplete')
    {
        parent::__construct($message);
        $this->missingFields = $missingFields;
    }

    /**
     * @return string[]
     */
    public function getMissingFields(): array
    {
        return $this->missingFields;
    }
}
