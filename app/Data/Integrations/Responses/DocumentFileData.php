<?php

namespace App\Data\Integrations\Responses;

use Spatie\LaravelData\Data;

/**
 * Vendor-neutral downloaded document file.
 *
 * Adapters return the raw bytes plus enough metadata for the application to
 * persist the file ({@see \App\Services\PackageService::storeDocument()})
 * without knowing which marketplace produced it.
 */
class DocumentFileData extends Data
{
    public function __construct(
        public string $content,
        public string $mimeType,
        public string $fileName,
    ) {}
}
