<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Services\ContactStateManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecalculateContactMetrics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $contact;

    /**
     * Create a new job instance.
     */
    public function __construct(Contact $contact)
    {
        $this->contact = $contact;
    }

    /**
     * Execute the job.
     */
    public function handle(ContactStateManager $stateManager): void
    {
        // Refresh contact to get latest data
        $this->contact->refresh();

        // Recalculate all derived fields
        $stateManager->updateDerivedFields($this->contact);
    }
}
