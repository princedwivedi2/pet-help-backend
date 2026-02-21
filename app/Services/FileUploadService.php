<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    /**
     * Allowed MIME types per category.
     */
    private const MIME_RULES = [
        'avatar' => [
            'mimes'    => ['jpeg', 'jpg', 'png', 'webp'],
            'max_size' => 2048, // 2 MB
            'disk'     => 'public',
            'path'     => 'avatars',
        ],
        'vet_document' => [
            'mimes'    => ['jpeg', 'jpg', 'png', 'pdf'],
            'max_size' => 5120, // 5 MB
            'disk'     => 'local', // private storage
            'path'     => 'vet-documents',
        ],
    ];

    /**
     * Upload a file safely.
     *
     * @param UploadedFile $file
     * @param string       $category  'avatar' | 'vet_document'
     * @param string|null  $oldPath   Previous path to delete
     * @return string Stored path
     *
     * @throws \InvalidArgumentException
     */
    public function upload(UploadedFile $file, string $category, ?string $oldPath = null): string
    {
        $rules = self::MIME_RULES[$category]
            ?? throw new \InvalidArgumentException("Unknown upload category: {$category}");

        // Validate MIME type
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $rules['mimes'])) {
            throw new \InvalidArgumentException(
                "Invalid file type. Allowed: " . implode(', ', $rules['mimes'])
            );
        }

        // Validate size (in KB)
        if ($file->getSize() / 1024 > $rules['max_size']) {
            throw new \InvalidArgumentException(
                "File too large. Max size: " . ($rules['max_size'] / 1024) . "MB"
            );
        }

        // Delete old file if replacing
        if ($oldPath) {
            $this->delete($oldPath, $rules['disk']);
        }

        // Store with unique name to prevent overwrites
        $filename = Str::uuid() . '.' . $extension;

        return $file->storeAs($rules['path'], $filename, $rules['disk']);
    }

    /**
     * Delete a file from storage.
     */
    public function delete(string $path, string $disk = 'public'): bool
    {
        if (Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->delete($path);
        }

        return false;
    }

    /**
     * Get the public URL for a stored file.
     */
    public function url(string $path, string $disk = 'public'): string
    {
        return Storage::disk($disk)->url($path);
    }
}
