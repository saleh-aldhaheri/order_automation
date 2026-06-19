<?php

namespace App\Jobs\Integrations;

use App\Models\Shop;
use App\Services\ShopService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;

class RefreshShopTokenJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private int $id
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $shop = Shop::query()->where('id', $this->id)->first();

        if (!$shop) {
            return;
        }

        app(ShopService::class)
            ->setShopFromModel($shop)
            ->refresh();
    }

    public function middleware(): array
    {
        return  [new RateLimited('shop-refresh')];
    }
}
