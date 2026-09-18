<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

class TmdbProxyTest extends TestCase
{
    public function createApplication()
    {
        $app = require __DIR__.'/../cub/bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        config(['lampa.tmdb_http_proxy' => 'user:password@85.137.94.165:8000']);
        Http::preventStrayRequests();
    }

    public function test_api_uses_authenticated_proxy_and_preserves_query(): void
    {
        Http::fake(function (Request $request, array $options) {
            $this->assertSame('http://user:password@85.137.94.165:8000', $options['proxy']);
            $this->assertFalse($options['allow_redirects']);
            $this->assertSame('https://api.themoviedb.org/3/search/movie?api_key=test&query=Hello%20world&language=ru', $request->url());

            return Http::response(['results' => [['id' => 123]]]);
        });

        $this->getJson('/api/tmdb/api/search/movie?api_key=test&query=Hello%20world&language=ru')
            ->assertOk()->assertJsonPath('results.0.id', 123);
    }

    public function test_images_preserve_bytes_and_cache_headers(): void
    {
        config(['lampa.tmdb_http_proxy' => 'http://user:password@85.137.94.165:8000']);
        Http::fake(['https://image.tmdb.org/t/p/w500/poster.jpg' => Http::response("\xff\xd8image", 200, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=3600',
            'ETag' => 'poster',
        ])]);

        $this->get('/api/tmdb/image/t/p/w500/poster.jpg')->assertOk()
            ->assertContent("\xff\xd8image")->assertHeader('Content-Type', 'image/jpeg')
            ->assertHeader('ETag', 'poster')->assertHeader('Cache-Control', 'max-age=3600, public');
    }

    public function test_missing_proxy_does_not_fall_back_to_direct_connection(): void
    {
        config(['lampa.tmdb_http_proxy' => '']);
        Http::fake();

        $this->getJson('/api/tmdb/api/movie/123')->assertStatus(503);
        Http::assertNothingSent();
    }

    public function test_connection_failure_does_not_expose_credentials(): void
    {
        Http::fake(['*' => Http::failedConnection('user:password@85.137.94.165:8000')]);

        $this->getJson('/api/tmdb/api/movie/123')->assertStatus(502)->assertDontSee('password');
    }

    public function test_upstream_error_status_is_preserved(): void
    {
        Http::fake(['*' => Http::response(['status_code' => 7], 401)]);

        $this->getJson('/api/tmdb/api/movie/123')->assertUnauthorized()->assertJsonPath('status_code', 7);
    }

    public function test_redirects_are_not_forwarded(): void
    {
        Http::fake(['*' => Http::response('', 302, ['Location' => 'http://127.0.0.1/private'])]);

        $this->getJson('/api/tmdb/api/movie/123')->assertStatus(502)->assertHeaderMissing('Location');
    }

    public function test_invalid_paths_and_types_do_not_send_requests(): void
    {
        Http::fake();

        $this->getJson('/api/tmdb/api/https://example.com')->assertStatus(400);
        $this->getJson('/api/tmdb/api/movie/%2e%2e/account')->assertStatus(400);
        $this->getJson('/api/tmdb/other/movie/123')->assertNotFound();
        Http::assertNothingSent();
    }
}
