<?php

namespace App\Providers;

use App\Services\Ai\AiDocumentService;
use App\Services\Ai\Contracts\AiDocumentServiceInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AiDocumentServiceInterface::class, AiDocumentService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
