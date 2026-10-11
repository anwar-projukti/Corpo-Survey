<?php

namespace App\Jobs;

use App\Actions\Imports\ImportRespondentsFromFile;
use App\Models\ImportJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportRespondentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly ImportJob $importJob) {}

    public function handle(ImportRespondentsFromFile $importer): void
    {
        try {
            $this->importJob->update(['status' => 'processing']);

            $importer->handle($this->importJob);
        } catch (\Throwable $e) {
            $this->importJob->markFailed($e->getMessage());

            throw $e;
        }
    }
}
