<?php

namespace App\Services\Chat;

use App\Contracts\FirestoreWriter;
use Google\Cloud\Firestore\FirestoreClient;

/**
 * Production FirestoreWriter using Google Cloud Firestore PHP SDK.
 *
 * Path parsing: "consultations/{uuid}/messages/{id}" splits into alternating
 * collection/document segments. The path MUST have an even number of segments
 * (collection/document/collection/document/...).
 */
class GoogleFirestoreWriter implements FirestoreWriter
{
    public function __construct(private FirestoreClient $client) {}

    public function setDocument(string $path, array $data): void
    {
        $segments = array_values(array_filter(explode('/', trim($path, '/')), fn ($s) => $s !== ''));
        if (count($segments) === 0 || count($segments) % 2 !== 0) {
            throw new \InvalidArgumentException(
                "Firestore path must be 'collection/doc' or 'collection/doc/collection/doc/...': {$path}"
            );
        }

        // Walk: collection() / document() / collection() / document() ...
        $cursor = $this->client->collection($segments[0])->document($segments[1]);
        for ($i = 2; $i < count($segments); $i += 2) {
            $cursor = $cursor->collection($segments[$i])->document($segments[$i + 1]);
        }

        $cursor->set($data);
    }
}
