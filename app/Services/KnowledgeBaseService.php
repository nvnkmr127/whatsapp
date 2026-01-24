<?php

namespace App\Services;

use App\Models\KnowledgeBaseSource;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class KnowledgeBaseService
{
    /**
     * Extract text from a file (PDF or TXT).
     */
    /**
     * Extract text from a file (PDF or TXT).
     * @throws \Exception
     */
    public function extractFromFile(string $filePath, string $originalName)
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $content = '';

        if (!Storage::disk('local')->exists($filePath)) {
            throw new \Exception("File not found at path: $filePath");
        }

        if ($extension === 'txt') {
            $content = Storage::disk('local')->get($filePath);
        } elseif ($extension === 'pdf') {
            if (class_exists(\Smalot\PdfParser\Parser::class)) {
                try {
                    $parser = new \Smalot\PdfParser\Parser();
                    $pdf = $parser->parseFile(Storage::path($filePath));
                    $content = $pdf->getText();
                } catch (\Exception $e) {
                    throw new \Exception("PDF Parsing Error: " . $e->getMessage());
                }
            } else {
                throw new \Exception("PDF Parser not installed (smalot/pdfparser). Cannot extract content.");
            }
        } else {
            throw new \Exception("Unsupported file extension: $extension");
        }

        return trim($content);
    }

    /**
     * Scrape text from a URL with cleanup.
     * @throws \Exception
     */
    public function extractFromUrl(string $url)
    {
        try {
            $response = Http::timeout(30)->get($url);
            if ($response->successful()) {
                $html = $response->body();

                // Remove scripts, styles, navigation, footer, etc to reduce noise
                $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $html);
                $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', "", $html);
                $html = preg_replace('/<nav\b[^>]*>(.*?)<\/nav>/is', "", $html);
                $html = preg_replace('/<footer\b[^>]*>(.*?)<\/footer>/is', "", $html);
                $html = preg_replace('/<!--(.*?)-->/s', "", $html);

                $text = strip_tags($html, '<p><h1><h2><h3><h4><h5><h6><li><div>');
                $text = html_entity_decode($text);
                $text = preg_replace('/\s+/', ' ', $text); // Cleanup whitespace
                return trim($text);
            } else {
                throw new \Exception("HTTP request failed with status: " . $response->status());
            }
        } catch (\Exception $e) {
            Log::error("URL Extraction Error ($url): " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if the team's knowledge base has at least one ready source and no sources are currently in a failed/pending state that might be critical.
     * (Blocking AI usage when KB is not ready)
     */
    public function isReady(int $teamId, ?array $scopedSourceIds = null): bool
    {
        $query = KnowledgeBaseSource::where('team_id', $teamId)
            ->where('is_active', true)
            ->whereIn('status', [KnowledgeBaseSource::STATUS_READY, 'indexed']);

        if (!empty($scopedSourceIds)) {
            $query->whereIn('id', $scopedSourceIds);
        }

        $hasReady = $query->exists();

        // We consider it ready if there is at least one READY source within the scope
        return $hasReady;
    }

    /**
     * Search for relevant context within the team's knowledge base.
     */
    public function searchContext(int $teamId, string $query, ?array $scopedSourceIds = null)
    {
        // 1. Clean Query
        $query = strtolower(trim($query));
        $keywords = array_filter(explode(' ', $query), function ($k) {
            return strlen($k) > 3; // Filter short words
        });

        if (empty($keywords)) {
            return "No valid keywords found in query.";
        }

        // 2. Database Search (Basic 'LIKE' fallback if no FullText)
        $dbQuery = KnowledgeBaseSource::where('team_id', $teamId)
            ->where('is_active', true)
            ->whereIn('status', [KnowledgeBaseSource::STATUS_READY, 'indexed']);

        if (!empty($scopedSourceIds)) {
            $dbQuery->whereIn('id', $scopedSourceIds);
        }

        $sources = $dbQuery->where(function ($q) use ($keywords) {
            foreach ($keywords as $keyword) {
                $q->orWhere('content', 'LIKE', "%{$keyword}%")
                    ->orWhere('name', 'LIKE', "%{$keyword}%");
            }
        })
            ->get();

        // 3. Rank/Filter Results in Memory (since we don't have vector DB yet)
        $hits = [];
        foreach ($sources as $source) {
            $score = 0;
            $snippet = '';
            $contentLower = strtolower($source->content);

            foreach ($keywords as $kw) {
                $count = substr_count($contentLower, $kw);
                $score += $count;
            }

            if ($score > 0) {
                // Determine best snippet window
                $firstPos = strpos($contentLower, reset($keywords));
                $start = max(0, $firstPos - 50);
                $length = 800; // Return substantial context
                $snippet = substr($source->content, $start, $length);

                $hits[] = [
                    'source' => $source->name,
                    'score' => $score,
                    'content' => $snippet,
                    'synced' => $source->last_synced_at?->diffForHumans() ?? 'Unknown'
                ];
            }
        }

        // Sort by score desc
        usort($hits, fn($a, $b) => $b['score'] <=> $a['score']);

        // Limit top 3
        $topHits = array_slice($hits, 0, 3);

        if (empty($topHits)) {
            return "No specific business context found in knowledge base.";
        }

        $context = "Found " . count($topHits) . " relevant sources:\n\n";
        foreach ($topHits as $hit) {
            $context .= "--- Source: {$hit['source']} (Synced: {$hit['synced']}) ---\n";
            $context .= "{$hit['content']}...\n\n";
        }

        return $context;
    }
    /**
     * Log a knowledge gap for admin review.
     */
    public function logGap(int $teamId, string $query, string $type = 'unanswered', ?array $searchMetadata = null)
    {
        try {
            \App\Models\KnowledgeBaseGap::create([
                'team_id' => $teamId,
                'query' => $query,
                'gap_type' => $type,
                'search_metadata' => $searchMetadata,
                'status' => 'pending'
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to log knowledge gap: " . $e->getMessage());
        }
    }
}
