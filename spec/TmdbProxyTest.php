<?php

use App\Services\TmdbProxyResolver;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

        config(['proxy.list' => ['http://user:password@proxy.example.com:8000']]);
        $this->mock(TmdbProxyResolver::class)->shouldReceive('resolve')->andReturn('1.2.3.4');
        Http::preventStrayRequests();
    }

    public function test_api_uses_authenticated_proxy_and_preserves_query(): void
    {
        Http::fake(function (Request $request, array $options) {
            $this->assertSame('http://user:password@proxy.example.com:8000', $options['proxy']);
            $this->assertFalse($options['allow_redirects']);
            $this->assertSame(['api.themoviedb.org:443:1.2.3.4:443'], $options['curl'][CURLOPT_CONNECT_TO]);
            $this->assertTrue($options['verify']);
            $this->assertSame('https://api.themoviedb.org/3/search/movie?api_key=test&query=Hello%20world&language=ru', $request->url());

            return Http::response(['results' => [['id' => 123]]]);
        });

        $this->getJson('/api/tmdb/api/search/movie?api_key=test&query=Hello%20world&language=ru')
            ->assertOk()->assertJsonPath('results.0.id', 123);
    }

    public function test_images_preserve_bytes_and_cache_headers(): void
    {
        Http::fake(function (Request $request, array $options) {
            $this->assertSame(['image.tmdb.org:443:1.2.3.4:443'], $options['curl'][CURLOPT_CONNECT_TO]);
            $this->assertTrue($options['verify']);

            return Http::response("\xff\xd8image", 200, [
                'Content-Type' => 'image/jpeg',
                'Cache-Control' => 'public, max-age=3600',
                'ETag' => 'poster',
            ]);
        });

        $this->get('/api/tmdb/image/t/p/w500/poster.jpg')->assertOk()
            ->assertContent("\xff\xd8image")->assertHeader('Content-Type', 'image/jpeg')
            ->assertHeader('ETag', 'poster')->assertHeader('Cache-Control', 'max-age=3600, public');
    }

    public function test_proxy_authentication_failure_switches_to_the_next_proxy(): void
    {
        $proxies = ['http://first.example.com:8000', 'http://second.example.com:8000'];
        config(['proxy.list' => $proxies]);
        $attempts = [];

        Http::fake(function (Request $request, array $options) use (&$attempts) {
            $attempts[] = $options['proxy'];

            return count($attempts) === 1
                ? Http::response('', 407)
                : Http::response(['id' => 123]);
        });

        $this->getJson('/api/tmdb/api/movie/123')->assertOk()->assertJsonPath('id', 123);
        $this->assertSame($proxies, $attempts);
    }

    public function test_exhausted_proxies_do_not_fall_back_to_direct_connection(): void
    {
        $proxies = ['http://first.example.com:8000', 'http://second.example.com:8000'];
        config(['proxy.list' => $proxies]);
        $attempts = [];

        Http::fake(function (Request $request, array $options) use (&$attempts) {
            $attempts[] = $options['proxy'];

            return Http::response('', 407);
        });

        $this->getJson('/api/tmdb/api/movie/123')->assertStatus(407);
        $this->assertSame($proxies, $attempts);
    }

    public function test_invalid_proxy_scheme_does_not_send_requests(): void
    {
        config(['proxy.list' => ['ftp://proxy.example.com:8000']]);
        Http::fake();

        $this->getJson('/api/tmdb/api/movie/123')->assertStatus(503);
        Http::assertNothingSent();
    }

    public function test_missing_proxy_does_not_fall_back_to_direct_connection(): void
    {
        config(['proxy.list' => []]);
        Http::fake();

        $this->getJson('/api/tmdb/api/movie/123')->assertStatus(503);
        Http::assertNothingSent();
    }

    public function test_connection_failure_does_not_expose_credentials(): void
    {
        Http::fake(['*' => Http::failedConnection('user:password@proxy.example.com:8000')]);

        $this->getJson('/api/tmdb/api/movie/123')->assertStatus(502)->assertDontSee('password');
    }

    public function test_connection_failure_logs_transport_diagnostics_without_secrets(): void
    {
        config(['app.debug' => false]);

        $exception = new \GuzzleHttp\Exception\ConnectException(
            'Failed to connect using user:password@proxy.example.com:8000',
            new \GuzzleHttp\Psr7\Request('GET', 'https://api.themoviedb.org/3/movie/123?api_key=secret'),
            null,
            ['errno' => 56, 'http_connectcode' => 407, 'error' => 'user:password', 'url' => '?api_key=secret'],
        );

        Log::shouldReceive('warning')->once()->with('TMDB proxy connection failed', [
            'type' => 'api',
            'proxy_count' => 1,
            'errno' => 56,
            'http_connectcode' => 407,
        ]);

        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('Connection failed', 0, $exception));

        $this->getJson('/api/tmdb/api/movie/123?api_key=secret')->assertStatus(502)
            ->assertDontSee('password')->assertDontSee('secret');
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

    public function test_dns_resolution_uses_the_proxy_and_caches_only_for_the_dns_ttl(): void
    {
        Http::fake(function (Request $request, array $options) {
            $this->assertSame('http://user:password@proxy.example.com:8000', $options['proxy']);
            $this->assertTrue($options['verify']);
            $this->assertFalse($options['allow_redirects']);
            $this->assertSame('https://cloudflare-dns.com/dns-query?name=image.tmdb.org&type=A', $request->url());

            return Http::response(['Status' => 0, 'Answer' => [
                ['type' => 5, 'data' => 'tmdb-image-prod.b-cdn.net.', 'TTL' => 10],
                ['type' => 1, 'data' => '1.2.3.4', 'TTL' => 60],
            ]]);
        });

        $resolver = new TmdbProxyResolver;
        $this->assertSame('1.2.3.4', $resolver->resolve('image.tmdb.org'));
        $this->assertSame('1.2.3.4', $resolver->resolve('image.tmdb.org'));
        Http::assertSentCount(1);

        $this->travel(11)->seconds();
        $this->assertSame('1.2.3.4', $resolver->resolve('image.tmdb.org'));
        Http::assertSentCount(2);
    }

    public function test_invalid_dns_answers_do_not_send_tmdb_requests(): void
    {
        $this->app->instance(TmdbProxyResolver::class, new TmdbProxyResolver);
        Http::fake(['https://cloudflare-dns.com/*' => Http::response(['Status' => 0, 'Answer' => [
            ['type' => 1, 'data' => '127.0.0.1', 'TTL' => 30],
            ['type' => 1, 'data' => '10.0.0.1', 'TTL' => 30],
        ]])]);

        $this->getJson('/api/tmdb/api/movie/123')->assertStatus(502);
        Http::assertSentCount(1);
        $this->getJson('/api/tmdb/api/movie/123')->assertStatus(502);
        Http::assertSentCount(2);
    }

    public function test_dns_connection_failure_does_not_fall_back_to_direct_connection(): void
    {
        $this->app->instance(TmdbProxyResolver::class, new TmdbProxyResolver);
        Http::fake(['https://cloudflare-dns.com/*' => Http::failedConnection()]);

        $this->getJson('/api/tmdb/api/movie/123')->assertStatus(502);
        Http::assertNotSent(fn (Request $request) => parse_url($request->url(), PHP_URL_HOST) === 'api.themoviedb.org');
    }
}
