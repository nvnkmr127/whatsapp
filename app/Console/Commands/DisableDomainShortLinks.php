<?php

namespace App\Console\Commands;

use App\Models\ShortLink;
use Illuminate\Console\Command;

class DisableDomainShortLinks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shortlinks:disable-domain {domain : The domain name (e.g. evilphishing.com) to block and delete links for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Instantly search and delete all short links pointing to a specific domain';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $domain = strtolower(trim($this->argument('domain')));

        if (empty($domain)) {
            $this->error('The domain argument cannot be empty.');
            return 1;
        }

        $this->info("Scanning database for short links pointing to domain: {$domain}...");

        $deletedCount = 0;

        // Fetch and process links in chunks to prevent memory bloat
        ShortLink::chunk(100, function ($links) use ($domain, &$deletedCount) {
            foreach ($links as $link) {
                $host = parse_url($link->destination_url, PHP_URL_HOST);
                if ($host) {
                    $host = strtolower($host);
                    // Match exact domain or subdomains (e.g. test.evil.com matching evil.com)
                    if ($host === $domain || str_ends_with($host, '.' . $domain)) {
                        $link->delete();
                        $deletedCount++;
                    }
                }
            }
        });

        if ($deletedCount > 0) {
            $this->info("Successfully deleted {$deletedCount} short links pointing to '{$domain}'.");
        } else {
            $this->warn("No short links found pointing to '{$domain}'.");
        }

        return 0;
    }
}
