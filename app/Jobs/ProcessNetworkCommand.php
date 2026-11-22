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
        NetworkCommand::whereKey($this->commandId)
            ->whereIn('status', ['pending', 'processing'])
            ->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'executed_at' => now(),
            ]);
    }
}
