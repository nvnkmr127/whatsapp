<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;

class RunReverbPoll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reverb:run-poll {--seconds=55 : The number of seconds to run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run Reverb server for a fixed amount of time (for shared hosting)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $seconds = (int) $this->option('seconds');
        $this->info("Starting Reverb for {$seconds} seconds...");

        // Start the Reverb process
        // We use the same PHP binary that is running this command
        $phpBinary = PHP_BINARY;
        $process = new Process([$phpBinary, 'artisan', 'reverb:start']);
        $process->setTimeout(null); // No timeout for the process itself, we manage it
        $process->start();

        $startTime = time();
        $running = true;

        while ($running) {
            // Check if process is still running
            if (!$process->isRunning()) {
                $this->error('Reverb process exited unexpectedly.');
                $this->error($process->getErrorOutput());
                return 1;
            }

            // Stream output to console
            $output = $process->getIncrementalOutput();
            if ($output) {
                $this->output->write($output);
            }

            $errorOutput = $process->getIncrementalErrorOutput();
            if ($errorOutput) {
                $this->output->write($errorOutput);
            }

            // Check time
            if (time() - $startTime >= $seconds) {
                $running = false;
                $this->info('Time limit reached. Stopping Reverb...');
            } else {
                usleep(500000); // Sleep 0.5s
            }
        }

        // Graceful stop
        $process->stop(3); // Wait 3 seconds for signal
        $this->info('Reverb stopped.');

        return 0;
    }
}
