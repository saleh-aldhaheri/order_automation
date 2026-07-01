<?php

namespace App\Integrations\Shopee\Enums;

enum ShopeeDocumentStatus: string
{
    case PROCESSING = 'processing';
    case READY = 'ready';
    case FAILED = 'failed';

    public function description(): string
    {
        return match ($this) {
            self::PROCESSING => 'The shipping document is still being generated. Keep polling get_shipping_document_result until it becomes READY.',
            self::READY => 'The shipping document is generated and can now be downloaded via download_shipping_document.',
            self::FAILED => 'The shipping document generation failed. Cannot be downloaded; check the failure reason and retry if applicable.',
        };
    }

    public function isDownloadable(): bool
    {
        return $this === self::READY;
    }

    public function shouldPoll(): bool
    {
        return $this === self::PROCESSING;
    }

    public function hasFailed(): bool
    {
        return $this === self::FAILED;
    }
}
