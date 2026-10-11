<?php

namespace App\Notifications;

use App\Models\ExportJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Sent once a queued export (spec §10.4) finishes writing its file — never
 * links to a public path, only a signed, expiring URL generated fresh here
 * so the link's lifetime starts at "export ready", not "export requested".
 */
class ExportReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly ExportJob $exportJob) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = $this->downloadUrl();

        return (new MailMessage)
            ->subject('Your export is ready')
            ->line('The export you requested has finished processing.')
            ->action('Download Export', $url)
            ->line('This link expires in 24 hours.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'export_job_id' => $this->exportJob->id,
            'type' => $this->exportJob->type,
            'format' => $this->exportJob->format,
            'row_count' => $this->exportJob->row_count,
            'download_url' => $this->downloadUrl(),
        ];
    }

    private function downloadUrl(): string
    {
        return URL::temporarySignedRoute(
            'api.exports.download',
            now()->addHours(24),
            ['exportJob' => $this->exportJob->id],
        );
    }
}
