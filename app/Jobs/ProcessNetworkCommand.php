<?php

namespace App\Jobs;

use App\Models\NetworkCommand;
use App\Services\Network\NetworkCommandProcessor;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessNetworkCommand implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public array $backoff = [10, 30, 60];

    public int $timeout = 90;

    public DateTimeInterface $retryUntilAt;

    public function __construct(
        public int $commandId
    ) {
        $this->retryUntilAt = now()->addMinutes(5);
    }

    public function retryUntil(): DateTimeInterface
    {
        return $this->retryUntilAt;
    }

    public function handle(
        NetworkCommandProcessor $processor
    ): void {
        $processor->process($this->commandId, $this);
    }

    public function failed(Throwable $exception): void
    {
        $message = $exception->getMessage();

        /*
         * If the command was still pending, the processor
         * had not claimed it for execution.
         *
         * Therefore, it is safe to mark it failed.
         */
        NetworkCommand::query()
            ->whereKey($this->commandId)
            ->where('status', 'pending')
            ->update([
                'status' => 'failed',
                'error_message' =>
                    'Queue job failed before network command execution: ' . $message,
            ]);

        /*
         * If execution had already started, the controller
         * may have received the request.
         *
         * Never assume that the command failed externally.
         * Mark it unknown and require manual reconciliation.
         */
        NetworkCommand::query()
            ->whereKey($this->commandId)
            ->where('status', 'processing')
            ->update([
                'status' => 'unknown',
                'response' => [
                    'success' => false,
                    'outcome' => 'unknown',
                    'message' =>
                        'Queue worker failed after execution started. Controller outcome could not be confirmed.',
                ],
                'error_message' =>
                    'Queue worker failed after execution started. Manual reconciliation is required. ' . $message,
                'executed_at' => now(),
            ]);
    }
}
