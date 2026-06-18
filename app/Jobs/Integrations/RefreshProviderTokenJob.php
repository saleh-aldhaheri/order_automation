<?php

namespace App\Jobs\Integrations;

use App\Models\Provider;
use App\Services\ProviderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;

class RefreshProviderTokenJob implements ShouldQueue
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
        $provider = Provider::query()->where('id', $this->id)->first();

        if (!$provider) {
            return;
        }

        app(ProviderService::class)
            ->setProviderFromModel($provider)
            ->refresh();
    }

    public function middleware(): array
    {
        return  [new RateLimited('provider-refresh')];
    }
}
