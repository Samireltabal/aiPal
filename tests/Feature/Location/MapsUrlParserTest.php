<?php

declare(strict_types=1);

namespace Tests\Feature\Location;

use App\Services\Location\MapsUrlParser;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MapsUrlParserTest extends TestCase
{
    private MapsUrlParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new MapsUrlParser;
    }

    public function test_parses_google_maps_at_coordinates(): void
    {
        $result = $this->parser->parseUrl('https://www.google.com/maps/@24.7136,46.6753,15z');

        $this->assertNotNull($result);
        $this->assertEqualsWithDelta(24.7136, $result['latitude'], 0.0001);
        $this->assertEqualsWithDelta(46.6753, $result['longitude'], 0.0001);
    }

    public function test_parses_google_maps_query_param(): void
    {
        $result = $this->parser->parseUrl('https://maps.google.com/?q=48.8566,2.3522');

        $this->assertNotNull($result);
        $this->assertEqualsWithDelta(48.8566, $result['latitude'], 0.0001);
        $this->assertEqualsWithDelta(2.3522, $result['longitude'], 0.0001);
    }

    public function test_parses_apple_maps_ll_param(): void
    {
        $result = $this->parser->parseUrl('https://maps.apple.com/?ll=40.7128,-74.0060');

        $this->assertNotNull($result);
        $this->assertEqualsWithDelta(40.7128, $result['latitude'], 0.0001);
        $this->assertEqualsWithDelta(-74.0060, $result['longitude'], 0.0001);
    }

    public function test_parses_url_from_free_text_message(): void
    {
        $result = $this->parser->parseFromText("Hey I'm here now: https://maps.google.com/?q=51.5074,-0.1278 come meet me");

        $this->assertNotNull($result);
        $this->assertEqualsWithDelta(51.5074, $result['latitude'], 0.0001);
    }

    public function test_rejects_non_maps_host(): void
    {
        $result = $this->parser->parseUrl('https://evil.com/maps/?q=1.0,1.0');

        $this->assertNull($result);
    }

    public function test_rejects_relative_or_invalid_url(): void
    {
        $this->assertNull($this->parser->parseUrl('not a url'));
        $this->assertNull($this->parser->parseUrl('/relative/path'));
    }

    public function test_rejects_short_link_that_redirects_to_localhost(): void
    {
        Http::fake([
            'goo.gl/*' => Http::response('', 302, ['Location' => 'http://localhost:6379/evil']),
        ]);

        $result = $this->parser->parseUrl('https://goo.gl/maps/evil-abc');

        $this->assertNull($result);
    }

    public function test_rejects_short_link_that_redirects_to_link_local_metadata(): void
    {
        Http::fake([
            'goo.gl/*' => Http::response('', 302, ['Location' => 'http://169.254.169.254/latest/meta-data']),
        ]);

        $result = $this->parser->parseUrl('https://goo.gl/maps/metadata');

        $this->assertNull($result);
    }

    public function test_rejects_short_link_that_redirects_to_evil_host(): void
    {
        Http::fake([
            'goo.gl/*' => Http::response('', 302, ['Location' => 'https://evil.com/stuff']),
        ]);

        $result = $this->parser->parseUrl('https://goo.gl/maps/evil');

        $this->assertNull($result);
    }

    public function test_short_link_resolution_caps_at_three_redirects(): void
    {
        Http::fake([
            'goo.gl/*' => Http::sequence()
                ->push('', 302, ['Location' => 'https://goo.gl/maps/hop2'])
                ->push('', 302, ['Location' => 'https://goo.gl/maps/hop3'])
                ->push('', 302, ['Location' => 'https://goo.gl/maps/hop4'])
                ->push('', 302, ['Location' => 'https://goo.gl/maps/hop5']),
        ]);

        $result = $this->parser->parseUrl('https://goo.gl/maps/hop1');

        $this->assertNull($result);
    }
}
