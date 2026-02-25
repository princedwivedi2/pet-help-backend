<?php

namespace App\Exceptions;

class VetDocumentException extends \RuntimeException
{
    /**
     * @var string[]
     */
    private array $missingDocuments;

    /**
     * @param string[] $missingDocuments
     */
    public function __construct(array $missingDocuments, string $message = 'Required documents are missing')
    {
        parent::__construct($message);
        $this->missingDocuments = $missingDocuments;
    }

    /**
     * @return string[]
     */
    public function getMissingDocuments(): array
    {
        return $this->missingDocuments;
    }
}
