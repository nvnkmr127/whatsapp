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
    public function extractFromFile(string $filePath, string $originalName)
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $content = '';

        if ($extension === 'txt') {
            $content = Storage::disk('local')->get($filePath);
        } elseif ($extension === 'pdf') {
            // Simplified: If PDF parser not available, we can't extract.
            // In a real app we'd use smalot/pdfparser
            try {
                if (class_exists(\Smalot\PdfParser\Parser::class)) {
                    $parser = new \Smalot\PdfParser\Parser();
                    $pdf = $parser->parseFile(Storage::path($filePath));
                    $content = $pdf->getText();
                } else {
                    Log::warning("PDF Parser not installed. Cannot extract from $originalName");
                    $content = "[PDF Extraction Not Available - Please install smalot/pdfparser]";
                }
            } catch (\Exception $e) {
                Log::error("PDF Extraction Error: " . $e->getMessage());
                $content = "[Error extracting PDF content]";
            }
        }

        return $content;
    }

    /**
     * Scrape text from a URL.
     */
    public function extractFromUrl(string $url)
    {
        try {
            $response = Http::get($url);
            if ($response->successful()) {
                $html = $response->body();
                // Basic cleanup of HTML to get text
                $text = strip_tags($html, '<p><h1><h2><h3><h4><h5><h6><li>');
                $text = preg_replace('/<[^>]*>/', "\n", $text); // Replace tags with newlines
                $text = preg_replace('/\s+/', ' ', $text); // Cleanup whitespace
                return trim($text);
            }
        } catch (\Exception $e) {
            Log::error("URL Extraction Error ($url): " . $e->getMessage());
        }

        return "[Error extracting content from URL]";
    }

    /**
     * Search for relevant context within the team's knowledge base.
     * Simple keyword-based search for now.
     */
    public function searchContext(int $teamId, string $query)
    {
        $sources = KnowledgeBaseSource::where('team_id', $teamId)
            ->where('is_active', true)
            ->get();

        $context = "";
        $keywords = explode(' ', strtolower($query));

        // Very basic RAG: Find sources that contain keywords
        foreach ($sources as $source) {
            $matched = false;
            foreach ($keywords as $kw) {
                if (strlen($kw) > 3 && str_contains(strtolower($source->content), $kw)) {
                    $matched = true;
                    break;
                }
            }

            if ($matched) {
                $context .= "Source: {$source->name}\nContent: " . substr($source->content, 0, 1000) . "...\n\n";
            }
        }

        return $context ?: "No specific business context found for this query.";
    }
}
