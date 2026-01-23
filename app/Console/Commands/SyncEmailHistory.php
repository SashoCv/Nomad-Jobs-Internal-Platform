<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EmailLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SyncEmailHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:sync-history {--days=90 : How many days back to look} {--limit=500 : Max number of emails to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import sent email history from Titan Mail IMAP';

    /**
     * Titan Mail IMAP Config
     */
    private $imapHost = '{imap.titan.email:993/imap/ssl}Sent'; // Usually Sent or INBOX.Sent
    private $username = 'contact@nomadjobs.cloud';
    // Ideally this should be in .env, but for this one-off migration/sync tool we use the known credential
    private $password = 'Phoenix123$';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (!function_exists('imap_open')) {
            $this->error('PHP IMAP extension is not installed.');
            return Command::FAILURE;
        }

        $this->info('Connecting to Titan Mail IMAP...');

        $mbox = @imap_open($this->imapHost, $this->username, $this->password);

        if (!$mbox) {
            // Try alternative folder name if "Sent" fails
            $this->warn('Could not open "Sent". Trying "INBOX.Sent"...');
            $this->imapHost = '{imap.titan.email:993/imap/ssl}INBOX.Sent';
            $mbox = @imap_open($this->imapHost, $this->username, $this->password);
        }

        if (!$mbox) {
            $this->error('Could not connect to IMAP: ' . imap_last_error());
            return Command::FAILURE;
        }

        $this->info('Connected successfully. Fetching emails...');

        $days = (int) $this->option('days');
        $limit = (int) $this->option('limit');
        $sinceDate = Carbon::now()->subDays($days)->format('d-M-Y');

        // Search for emails since the date
        $emailIds = imap_search($mbox, 'SINCE "' . $sinceDate . '"');

        if (!$emailIds) {
            $this->info('No emails found in the specified period.');
            imap_close($mbox);
            return Command::SUCCESS;
        }

        // Sort by date descending (newest first)
        rsort($emailIds);
        
        // Apply limit
        $emailIds = array_slice($emailIds, 0, $limit);
        
        $count = count($emailIds);
        $this->info("Found {$count} emails. Processing...");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($emailIds as $id) {
            try {
                $header = imap_headerinfo($mbox, $id);
                
                // Extract timestamp
                $dateSpan = $header->date;
                $timestamp = strtotime($dateSpan);
                $sentAt = Carbon::createFromTimestamp($timestamp);

                // Extract recipient
                $toAddress = isset($header->to) ? $header->to[0]->mailbox . '@' . $header->to[0]->host : null;
                $toName = isset($header->to) ? ($header->to[0]->personal ?? null) : null;

                if (!$toAddress) {
                     $bar->advance();
                     continue;
                }

                // Extract subject
                $subject = isset($header->subject) ? mb_decode_mimeheader($header->subject) : '(No Subject)';
                
                // Check if already exists to avoid duplicates
                // We check for same recipient, subject, and approximate time (within 5 mins)
                $exists = EmailLog::where('recipient_email', $toAddress)
                    ->where('subject', $subject)
                    ->whereBetween('sent_at', [$sentAt->copy()->subMinutes(5), $sentAt->copy()->addMinutes(5)])
                    ->exists();

                if ($exists) {
                    $bar->advance();
                    continue;
                }

                // If strictly simple text body, we could fetch it (section 1), 
                // but for logs often header info is enough if structure is complex.
                // Let's try to get a simple body preview.
                // $body = imap_fetchbody($mbox, $id, 1);
                
                EmailLog::create([
                    'recipient_email' => $toAddress,
                    'recipient_name' => $toName,
                    'subject' => $subject,
                    'email_type' => 'legacy_import', // Mark these specifically
                    'status' => EmailLog::STATUS_SENT,
                    'sent_at' => $sentAt,
                    'metadata' => [
                        'imported_from_imap' => true,
                        'imap_id' => $id,
                        'message_id' => $header->message_id ?? null
                    ]
                ]);

            } catch (\Exception $e) {
                // Log but continue
                Log::warning("Failed to import IMAP email ID $id: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Import completed.');

        imap_close($mbox);
        return Command::SUCCESS;
    }
}
