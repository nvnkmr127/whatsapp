<?php

namespace Tests\Feature;

use App\Models\ShortLink;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ShortLinkRedirectWarningTest extends TestCase
{
    use RefreshDatabase;

    protected Team $team;

    protected function setUp(): void
    {
        parent::setUp();
        $this->team = Team::factory()->create();
    }

    /**
     * Test that trusted domains redirect immediately (302).
     */
    public function test_trusted_domains_redirect_immediately()
    {
        config(['app.url' => 'https://flow.watxio.com']);

        $trustedUrls = [
            'https://wa.me/1234567890?text=Hello',
            'https://whatsapp.com/channel/abc',
            'https://watxio.com/pricing',
            'https://flow.watxio.com/dashboard',
            'https://facebook.com/meta',
            'https://instagram.com/direct',
            '/local-relative-path',
        ];

        foreach ($trustedUrls as $url) {
            $link = ShortLink::create([
                'team_id' => $this->team->id,
                'destination_url' => $url,
                'short_code' => ShortLink::generateUniqueCode(),
            ]);

            $response = $this->get(route('short-link.redirect', ['code' => $link->short_code]));

            $response->assertStatus(302);
            $response->assertRedirect($url);
        }
    }

    /**
     * Test that untrusted domains display the security warning page (200).
     */
    public function test_untrusted_domains_display_security_warning()
    {
        config(['app.url' => 'https://flow.watxio.com']);

        $untrustedUrl = 'https://untrusted-phishing-site.net/login?user=victim';

        $link = ShortLink::create([
            'team_id' => $this->team->id,
            'destination_url' => $untrustedUrl,
            'short_code' => ShortLink::generateUniqueCode(),
        ]);

        $response = $this->get(route('short-link.redirect', ['code' => $link->short_code]));

        $response->assertStatus(200);
        $response->assertViewIs('short-link.redirect');
        $response->assertSeeText('Security Alert: Leaving Platform');
        $response->assertSee($untrustedUrl);
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    /**
     * Test that the Artisan command deletes links for the specified domain.
     */
    public function test_artisan_command_deletes_specified_domain_links()
    {
        // Link to block
        $blockLink1 = ShortLink::create([
            'team_id' => $this->team->id,
            'destination_url' => 'https://evilphishing.com/malware',
            'short_code' => ShortLink::generateUniqueCode(),
        ]);

        $blockLink2 = ShortLink::create([
            'team_id' => $this->team->id,
            'destination_url' => 'https://subdomain.evilphishing.com/page',
            'short_code' => ShortLink::generateUniqueCode(),
        ]);

        // Link to keep
        $keepLink = ShortLink::create([
            'team_id' => $this->team->id,
            'destination_url' => 'https://google.com/search',
            'short_code' => ShortLink::generateUniqueCode(),
        ]);

        $this->assertDatabaseHas('short_links', ['id' => $blockLink1->id]);
        $this->assertDatabaseHas('short_links', ['id' => $blockLink2->id]);
        $this->assertDatabaseHas('short_links', ['id' => $keepLink->id]);

        // Run the disable command
        $exitCode = Artisan::call('shortlinks:disable-domain', [
            'domain' => 'evilphishing.com'
        ]);

        $this->assertEquals(0, $exitCode);

        // Verify databases
        $this->assertDatabaseMissing('short_links', ['id' => $blockLink1->id]);
        $this->assertDatabaseMissing('short_links', ['id' => $blockLink2->id]);
        $this->assertDatabaseHas('short_links', ['id' => $keepLink->id]);
    }
}
