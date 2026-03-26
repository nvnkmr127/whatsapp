<?php

namespace App\Livewire\Automations\Traits;

trait HasHistory
{
    public array $history = [];
    public int $historyIndex = -1;
    public bool $historyEnabled = true;

    protected function pushHistory(): void
    {
        if (!$this->historyEnabled) return;

        // Clear any redo states after current position
        if ($this->historyIndex < count($this->history) - 1) {
            $this->history = array_slice($this->history, 0, $this->historyIndex + 1);
        }

        $this->history[] = [
            'nodes' => $this->nodes,
            'edges' => $this->edges,
        ];

        // Keep max 50 snapshots
        if (count($this->history) > 50) {
            array_shift($this->history);
        }

        $this->historyIndex = count($this->history) - 1;
    }

    public function undo(): void
    {
        if ($this->historyIndex <= 0) {
            session()->flash('info', 'Nothing to undo.');
            return;
        }

        $this->historyIndex--;
        $snapshot = $this->history[$this->historyIndex];
        $this->historyEnabled = false;
        $this->nodes = $snapshot['nodes'];
        $this->edges = $snapshot['edges'];
        $this->historyEnabled = true;
        $this->selectNode(null);
        $this->runValidation();
    }

    public function redo(): void
    {
        if ($this->historyIndex >= count($this->history) - 1) {
            session()->flash('info', 'Nothing to redo.');
            return;
        }

        $this->historyIndex++;
        $snapshot = $this->history[$this->historyIndex];
        $this->historyEnabled = false;
        $this->nodes = $snapshot['nodes'];
        $this->edges = $snapshot['edges'];
        $this->historyEnabled = true;
        $this->selectNode(null);
        $this->runValidation();
    }

    public function getCanUndo(): bool
    {
        return $this->historyIndex > 0;
    }

    public function getCanRedo(): bool
    {
        return $this->historyIndex < count($this->history) - 1;
    }
}
