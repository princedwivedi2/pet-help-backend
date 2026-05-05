<?php

namespace App\Contracts;

/**
 * Thin write-only abstraction over Firestore.
 *
 * Sized to what the chat broadcaster actually needs — `setDocument` is
 * idempotent ("write this doc at this path"). Keeps tests free of mocking
 * the FirestoreClient → CollectionReference → DocumentReference fluent chain.
 *
 * Production impl: GoogleFirestoreWriter (wraps FirestoreClient).
 * Tests: mock this interface directly.
 */
interface FirestoreWriter
{
    /**
     * Write a document. Path is a slash-separated string of alternating
     * collection/document IDs, e.g. "consultations/{uuid}/messages/{id}".
     *
     * Implementations are responsible for failure handling — caller treats this
     * as fire-and-forget (errors logged, never thrown to the request path).
     */
    public function setDocument(string $path, array $data): void;
}
