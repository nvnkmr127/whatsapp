<?php

namespace App\Livewire\Automations\Traits;

trait HasHistory
{
    public int $historyIndex = -1;
    public bool $historyEnabled = true;

    protected function getHistoryKey(): string
    {
        return 'auto_hist_' . auth()->id() . '_' . $this->automationId;
    }

    protected function pushHistory(): void
    {
        if (! $this->historyEnabled) {
            return;
        }

        $history = cache()->get($this->getHistoryKey(), []);

        if ($this->historyIndex < count($history) - 1) {
            $history = array_slice($history, 0, $this->historyIndex + 1);
        }

        $history[] = [
            'nodes' => array_values($this->nodes),
            'edges' => array_values($this->edges),
        ];

        if (count($history) > 15) {
            array_shift($history);
        }

        cache()->put($this->getHistoryKey(), $history, now()->addHours(1));
        $this->historyIndex = count($history) - 1;
    }

    public function undo(): void
    {
        $history = cache()->get($this->getHistoryKey(), []);
        if ($this->historyIndex <= 0 || empty($history)) {
            return;
        }

        $this->historyIndex--;
        $snapshot = $history[$this->historyIndex];
        $this->historyEnabled = false;
        $this->nodes = $snapshot['nodes'];
        $this->edges = $snapshot['edges'];
        $this->historyEnabled = true;
        $this->selectNode(null);
    }

    public function redo(): void
    {
        $history = cache()->get($this->getHistoryKey(), []);
        if ($this->historyIndex >= count($history) - 1 || empty($history)) {
            return;
        }

        $this->historyIndex++;
        $snapshot = $history[$this->historyIndex];
        $this->historyEnabled = false;
        $this->nodes = $snapshot['nodes'];
        $this->edges = $snapshot['edges'];
        $this->historyEnabled = true;
        $this->selectNode(null);
    }

    public function getCanUndo(): bool
    {
        return $this->historyIndex > 0;
    }

    public function getCanRedo(): bool
    {
        $history = cache()->get($this->getHistoryKey(), []);
        return $this->historyIndex < count($history) - 1;
    }
}
