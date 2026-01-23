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

        if (!$mbox) {
            $this->error('Could not connect to initial folder. Trying to list folders...');
            $mbox = @imap_open('{imap.titan.email:993/imap/ssl}', $this->username, $this->password);
            
            if (!$mbox) {
                 $this->error('Could not connect to IMAP: ' . imap_last_error());
                 return Command::FAILURE;
            }
        }

        $this->info('Connected successfully. Fetching folder list...');
        
        $folders = imap_list($mbox, '{imap.titan.email:993/imap/ssl}', '*');
        
        if (!$folders) {
            $this->error('No folders found.');
            return Command::FAILURE;
        }

        $days = (int) $this->option('days');
        $limit = (int) $this->option('limit');
        $sinceDate = Carbon::now()->subDays($days)->format('d-M-Y');
        $totalImported = 0;

        foreach ($folders as $folder) {
            $this->info("Scanning folder: $folder");
            
            // Re-open specific folder
            $folderMbox = @imap_open($folder, $this->username, $this->password);
            if (!$folderMbox) {
                $this->warn("Skipping folder $folder (could not open)");
                continue;
            }

            // Search for emails FROM us (nomadjobs.cloud) since date
            // This catches sent emails even if they are in Trash, Archive, or Inbox
            $searchCriteria = 'FROM "nomadjobs.cloud" SINCE "' . $sinceDate . '"';
            $emailIds = imap_search($folderMbox, $searchCriteria);

            if (!$emailIds) {
                $this->line("  No sent emails found in this folder.");
                imap_close($folderMbox);
                continue;
            }

            // Sort & Limit
            rsort($emailIds);
            
            // We apply limit per folder or global? Let's keep it generous for deep scan
            // $emailIds = array_slice($emailIds, 0, $limit); 

            $count = count($emailIds);
            $this->info("  Found {$count} matching emails. Processing...");

            $bar = $this->output->createProgressBar($count);
            $bar->start();

            foreach ($emailIds as $id) {
                try {
                    $header = imap_headerinfo($folderMbox, $id);
                    
                    // Double check it's actually from us (extra safety)
                    $fromAddress = isset($header->from) ? $header->from[0]->mailbox . '@' . $header->from[0]->host : '';
                    if (!str_contains($fromAddress, 'nomadjobs.cloud')) {
                         $bar->advance();
                         continue;
                    }

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
                    $exists = EmailLog::where('recipient_email', $toAddress)
                        ->where('subject', $subject)
                        ->whereBetween('sent_at', [$sentAt->copy()->subMinutes(5), $sentAt->copy()->addMinutes(5)])
                        ->exists();

                    if ($exists) {
                        $bar->advance();
                        continue;
                    }

                    EmailLog::create([
                        'recipient_email' => $toAddress,
                        'recipient_name' => $toName,
                        'subject' => $subject,
                        'email_type' => 'legacy_import', 
                        'status' => EmailLog::STATUS_SENT,
                        'sent_at' => $sentAt,
                        'metadata' => [
                            'imported_from_imap' => true,
                            'imap_folder' => $folder,
                            'imap_id' => $id,
                            'message_id' => $header->message_id ?? null
                        ]
                    ]);
                    $totalImported++;

                } catch (\Exception $e) {
                    Log::warning("Failed to import IMAP email ID $id in $folder: " . $e->getMessage());
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            imap_close($folderMbox);
        }

        $this->info("Deep scan completed. Total emails imported: $totalImported");
        return Command::SUCCESS;
    }
}
