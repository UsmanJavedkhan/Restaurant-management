<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Vite;
use Tests\TestCase;

class RenderHttpsTest extends TestCase
{
    public function test_frontend_assets_use_https_behind_the_render_proxy(): void
    {
        config(['app.trusted_proxies' => '*']);
        $this->app->getProvider(AppServiceProvider::class)->boot();
        $this->app->make(Vite::class)->useHotFile(storage_path('framework/testing-no-vite-server.hot'));

        $response = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])
            ->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'untrusted.example'])
            ->get('http://restaurant.example/');

        $response->assertSee('https://restaurant.example/build/assets/', false)
            ->assertDontSee('http://restaurant.example/build/assets/', false)
            ->assertDontSee('untrusted.example', false);
    }

    public function test_local_http_assets_ignore_untrusted_proxy_headers(): void
    {
        config(['app.trusted_proxies' => null]);
        $this->app->getProvider(AppServiceProvider::class)->boot();
        $this->app->make(Vite::class)->useHotFile(storage_path('framework/testing-no-vite-server.hot'));

        $response = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])
            ->withHeaders(['X-Forwarded-Proto' => 'https'])->get('http://restaurant.example/');

        $response->assertSee('http://restaurant.example/build/assets/', false);
    }
}
