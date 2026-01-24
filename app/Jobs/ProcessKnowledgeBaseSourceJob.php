<?php

namespace App\Jobs;

use App\Models\KnowledgeBaseSource;
use App\Services\KnowledgeBaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessKnowledgeBaseSourceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes timeout for large files/slow URLs

    /**
     * Create a new job instance.
     */
    public function __construct(public KnowledgeBaseSource $source)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(KnowledgeBaseService $service): void
    {
        try {
            $this->source->update([
                'status' => KnowledgeBaseSource::STATUS_PROCESSING,
                'error_message' => null,
            ]);

            $content = '';

            if ($this->source->type === 'file' && $this->source->path) {
                // Determine original name from metadata or path
                $originalName = $this->source->metadata['original_name'] ?? basename($this->source->path);
                $content = $service->extractFromFile($this->source->path, $originalName);
            } elseif ($this->source->type === 'url' && $this->source->path) {
                $content = $service->extractFromUrl($this->source->path);
            } elseif ($this->source->type === 'text') {
                // Text is already in content, but we might want to "process" it or clean it in future
                $content = $this->source->content;
            }

            // Check if content extraction returned an error string (naive check based on current service implementation)
            // ideally service should throw exceptions, but we'll check for error markers or empty content
            if (empty($content) || str_starts_with($content, '[Error')) {
                throw new \Exception("Extraction failed: " . $content);
            }

            $this->source->update([
                'content' => $content,
                'status' => KnowledgeBaseSource::STATUS_READY,
                'last_synced_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error("Knowledge Base Processing Failed Source ID: {$this->source->id}. Error: " . $e->getMessage());

            $this->source->update([
                'status' => KnowledgeBaseSource::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);

            // Re-throw if you want the job to be retried by the queue worker, 
            // but for now we mark it as failed effectively.
            // fail($e); 
        }
    }
}
