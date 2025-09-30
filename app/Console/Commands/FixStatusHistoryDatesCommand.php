<?php

namespace App\Console\Commands;

use App\Models\Candidate;
use App\Models\Status;
use App\Models\Statushistory;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixStatusHistoryDatesCommand extends Command
{
    protected $signature = 'candidate:fix-status-dates {--candidate_id=} {--dry-run}';
    protected $description = 'Create missing status history entries and fix dates based on order';

    public function handle()
    {
        $candidateId = $this->option('candidate_id');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 Running in DRY RUN mode - no changes will be made');
        }

        $query = Candidate::query();
        
        if ($candidateId) {
            $query->where('id', $candidateId);
        }

        $candidates = $query->get();
        $this->info("Processing {$candidates->count()} candidates...");

        // Get all statuses ordered by their order field
        $allStatuses = Status::orderBy('order')->get();
        
        $processed = 0;
        $updated = 0;

        foreach ($candidates as $candidate) {
            $this->line("Processing candidate ID: {$candidate->id} - {$candidate->fullName}");
            
            $result = $this->processCandidate($candidate, $allStatuses, $dryRun);
            
            if ($result['processed']) {
                $processed++;
                if ($result['updated']) {
                    $updated++;
                }
            }
        }

        $this->info("✅ Completed: {$processed} candidates processed, {$updated} candidates updated");
    }

    private function processCandidate($candidate, $allStatuses, $dryRun)
    {
        // Get existing status histories for this candidate
        $existingHistories = Statushistory::where('candidate_id', $candidate->id)
            ->join('statuses', 'statushistories.status_id', '=', 'statuses.id')
            ->select('statushistories.*', 'statuses.order', 'statuses.nameOfStatus')
            ->orderBy('statuses.order')
            ->get()
            ->keyBy('status_id');

        if ($existingHistories->isEmpty()) {
            $this->line("  ⚠️  No status history found");
            return ['processed' => false, 'updated' => false];
        }

        // First, fix any date ordering issues in existing statuses
        $dateOrderingFixed = $this->fixDateOrdering($existingHistories, $dryRun);

        // Check if candidate has any rejection/termination statuses
        if ($this->hasRejectionStatus($existingHistories)) {
            $this->line("  🚫 Candidate has rejection/termination status - only fixed date ordering, skipping new status creation");
            return ['processed' => true, 'updated' => $dateOrderingFixed];
        }

        // Find anchor statuses using a smarter approach
        $anchorStatuses = $this->findSmartAnchors($existingHistories);

        if ($anchorStatuses->isEmpty()) {
            $this->line("  ⚠️  No anchor statuses found");
            return ['processed' => false, 'updated' => false];
        }

        $updated = false;
        $updates = [];

        $this->line("  📊 Found " . $anchorStatuses->count() . " anchor statuses");
        foreach ($anchorStatuses as $anchor) {
            $this->line("    🔗 {$anchor->nameOfStatus} (order: {$anchor->order}) - {$anchor->statusDate}");
        }

        // Get the highest order from existing histories
        $maxExistingOrder = $existingHistories->max('order');

        // Process all statuses up to max existing order using anchor-based logic
        foreach ($allStatuses as $status) {
            if ($status->order > $maxExistingOrder) {
                continue;
            }

            // Skip excluded statuses
            if ($this->shouldExcludeStatus($status->nameOfStatus)) {
                continue;
            }

            // Find which anchor this status should use
            $targetDate = $this->calculateDateBasedOnAnchors($status, $anchorStatuses);
            
            if (!$targetDate) {
                continue;
            }

            if (isset($existingHistories[$status->id])) {
                // Update existing entry if it needs update
                $existing = $existingHistories[$status->id];
                
                // Only update if it's not an anchor status and needs update
                if (!$anchorStatuses->contains('id', $existing->id) && 
                    ($this->needsDateUpdate($existing) || $existing->statusDate != $targetDate)) {
                    
                    $updates[] = [
                        'action' => 'update',
                        'id' => $existing->id,
                        'status_name' => $status->nameOfStatus,
                        'old_date' => $existing->statusDate,
                        'new_date' => $targetDate
                    ];

                    if (!$dryRun) {
                        Statushistory::where('id', $existing->id)
                            ->update(['statusDate' => $targetDate]);
                    }
                    $updated = true;
                }
            } else {
                // Create new status history entry
                $updates[] = [
                    'action' => 'create',
                    'status_name' => $status->nameOfStatus,
                    'date' => $targetDate
                ];

                if (!$dryRun) {
                    Statushistory::create([
                        'candidate_id' => $candidate->id,
                        'status_id' => $status->id,
                        'statusDate' => $targetDate,
                        'description' => null
                    ]);
                }
                $updated = true;
            }
        }

        if (!empty($updates)) {
            $this->line("  📝 Updates for candidate {$candidate->id}:");
            foreach ($updates as $update) {
                $prefix = $dryRun ? "  [DRY RUN]" : "  ✅";
                if ($update['action'] === 'create') {
                    $this->line("{$prefix} CREATE: {$update['status_name']} → {$update['date']}");
                } else {
                    $this->line("{$prefix} UPDATE: {$update['status_name']}: {$update['old_date']} → {$update['new_date']}");
                }
            }
        } else {
            $this->line("  ✅ No updates needed");
        }

        return ['processed' => true, 'updated' => $updated || $dateOrderingFixed];
    }

    private function fixDateOrdering($existingHistories, $dryRun)
    {
        $updated = false;
        $updates = [];
        
        // Sort statuses by order to check chronological sequence
        $sortedStatuses = $existingHistories->sortBy('order')->values();
        
        // Multiple passes to ensure all dates are fixed
        $maxPasses = 5;
        for ($pass = 0; $pass < $maxPasses; $pass++) {
            $changesInThisPass = false;
            
            for ($i = 1; $i < $sortedStatuses->count(); $i++) {
                $prevStatus = $sortedStatuses[$i - 1];
                $currentStatus = $sortedStatuses[$i];
                
                $prevDate = Carbon::parse($prevStatus->statusDate);
                $currentDate = Carbon::parse($currentStatus->statusDate);
                
                // If current status (higher order) has earlier date than previous status (lower order)
                // then fix the PREVIOUS status by giving it the earlier date from current status
                if ($currentDate->lessThan($prevDate)) {
                    $targetDate = $currentDate->format('Y-m-d');
                    
                    $updates[] = [
                        'action' => 'date_order_fix',
                        'id' => $prevStatus->id,
                        'status_name' => $prevStatus->nameOfStatus,
                        'old_date' => $prevStatus->statusDate,
                        'new_date' => $targetDate,
                        'reason' => "Order {$prevStatus->order} had later date than order {$currentStatus->order}"
                    ];

                    if (!$dryRun) {
                        Statushistory::where('id', $prevStatus->id)
                            ->update(['statusDate' => $targetDate]);
                    }
                    
                    // Update the collection for subsequent checks
                    $prevStatus->statusDate = $targetDate;
                    $updated = true;
                    $changesInThisPass = true;
                }
            }
            
            // If no changes in this pass, we're done
            if (!$changesInThisPass) {
                break;
            }
        }
        
        if (!empty($updates)) {
            $this->line("  🔧 Date ordering fixes:");
            foreach ($updates as $update) {
                $prefix = $dryRun ? "  [DRY RUN]" : "  ✅";
                $this->line("{$prefix} FIX: {$update['status_name']}: {$update['old_date']} → {$update['new_date']}");
                $this->line("       Reason: {$update['reason']}");
            }
        }
        
        return $updated;
    }

    private function hasRejectionStatus($existingHistories)
    {
        $rejectionStatuses = [
            'Отказ от посолството',
            'Прекратен договор', 
            'Отказ от Миграция',
            'Отказ от кандидата',
            'Отказ от компанията'
        ];

        foreach ($existingHistories as $history) {
            if (in_array($history->nameOfStatus, $rejectionStatuses)) {
                return true;
            }
        }

        return false;
    }

    private function findSmartAnchors($existingHistories)
    {
        // Simply use all existing statuses as anchors, excluding problematic ones
        $anchors = $existingHistories->reject(function($history) {
            return $this->shouldExcludeStatus($history->nameOfStatus) || 
                   $history->statusDate == '2025-09-30' || 
                   $history->description === 'отказ от работодателя, избягал';
        })->sortBy('order');
        
        $this->line("  📊 Found " . $anchors->count() . " existing statuses as anchors");
        foreach ($anchors as $anchor) {
            $this->line("  🔗 {$anchor->nameOfStatus} (order: {$anchor->order}) - " . Carbon::parse($anchor->statusDate)->format('Y-m-d'));
        }
        
        return $anchors;
    }

    private function findAnchorPoints($existingHistories)
    {
        $anchorPoints = [];
        
        foreach ($existingHistories as $history) {
            // Skip excluded statuses
            if ($this->shouldExcludeStatus($history->nameOfStatus)) {
                continue;
            }
            
            // Valid anchor points are those that don't need date updates
            if (!$this->needsDateUpdate($history)) {
                $anchorPoints[] = $history;
            }
        }
        
        // Sort by order
        usort($anchorPoints, function($a, $b) {
            return $a->order <=> $b->order;
        });
        
        return $anchorPoints;
    }

    private function needsDateUpdate($history)
    {
        // Only update statuses with problematic patterns
        return $history->statusDate == '2025-09-30' || 
               $history->description === 'отказ от работодателя, избягал';
    }

    private function isLikelyAnchorByDate($history, $allHistories)
    {
        // Check if this status has a unique date that makes it likely to be an anchor
        $sameDate = $allHistories->where('statusDate', $history->statusDate)->count();
        
        // If only this status has this date, it's likely an anchor
        return $sameDate === 1;
    }

    private function shouldExcludeStatus($statusName)
    {
        $excludedStatuses = [
            'Отказ от посолството',
            'Приключил договор',
            'Прекратен договор',
            'Отказ от Миграция', 
            'Отказ от кандидата',
            'Отказ от компанията'
        ];
        
        return in_array($statusName, $excludedStatuses);
    }

    private function calculateDateBasedOnAnchors($status, $anchorStatuses)
    {
        // Find the previous anchor status with lower order  
        $prevAnchor = null;
        foreach ($anchorStatuses->reverse() as $anchor) {
            if ($anchor->order < $status->order) {
                $prevAnchor = $anchor;
                break;
            }
        }

        // Find the next anchor status with higher order
        $nextAnchor = null;
        foreach ($anchorStatuses as $anchor) {
            if ($anchor->order > $status->order) {
                $nextAnchor = $anchor;
                break;
            }
        }

        // NEW LOGIC: Missing statuses get the date from the next higher existing status
        // This means statuses flow backwards from existing anchors to fill gaps
        
        if ($nextAnchor) {
            // Use the date from the next higher status (поголем order)
            return Carbon::parse($nextAnchor->statusDate)->format('Y-m-d');
        }
        
        if ($prevAnchor) {
            // If no higher status exists, use the previous one
            return Carbon::parse($prevAnchor->statusDate)->format('Y-m-d');
        }

        return null;
    }

    private function calculateDateForStatusInRange($status, $validExistingStatuses)
    {
        // Find the closest existing status before and after this status order
        $prevStatus = null;
        $nextStatus = null;

        foreach ($validExistingStatuses as $existingStatus) {
            if ($existingStatus->order < $status->order) {
                $prevStatus = $existingStatus; // Keep updating to get the closest one
            } elseif ($existingStatus->order > $status->order && !$nextStatus) {
                $nextStatus = $existingStatus; // Get the first one after
                break;
            }
        }

        // If we have both prev and next, interpolate between them
        if ($prevStatus && $nextStatus) {
            return $this->interpolateDate($prevStatus, $nextStatus, $status->order);
        }

        // If we only have previous, use it as base
        if ($prevStatus) {
            return Carbon::parse($prevStatus->statusDate)->format('Y-m-d');
        }

        // If we only have next, use it as base (for statuses before the first valid status)
        if ($nextStatus) {
            return Carbon::parse($nextStatus->statusDate)->format('Y-m-d');
        }

        return null;
    }

    private function interpolateDate($prevStatus, $nextStatus, $targetOrder)
    {
        $prevDate = Carbon::parse($prevStatus->statusDate);
        $nextDate = Carbon::parse($nextStatus->statusDate);
        
        // If dates are the same, return that date
        if ($prevDate->equalTo($nextDate)) {
            return $prevDate->format('Y-m-d');
        }

        // Calculate position ratio
        $totalOrderDiff = $nextStatus->order - $prevStatus->order;
        $targetOrderDiff = $targetOrder - $prevStatus->order;
        $ratio = $totalOrderDiff > 0 ? $targetOrderDiff / $totalOrderDiff : 0;

        // Calculate total days between dates
        $totalDays = $prevDate->diffInDays($nextDate);
        
        // Calculate target days
        $targetDays = round($totalDays * $ratio);

        // Return interpolated date
        return $prevDate->copy()->addDays($targetDays)->format('Y-m-d');
    }

    private function calculateDateForStatus($status, $anchorPoints)
    {
        // Find the nearest anchor point after this status
        $nextAnchor = null;
        foreach ($anchorPoints as $anchor) {
            if ($anchor->order > $status->order) {
                $nextAnchor = $anchor;
                break;
            }
        }

        // If no next anchor, find the previous anchor
        if (!$nextAnchor) {
            $prevAnchor = null;
            foreach (array_reverse($anchorPoints) as $anchor) {
                if ($anchor->order < $status->order) {
                    $prevAnchor = $anchor;
                    break;
                }
            }
            
            if ($prevAnchor) {
                // Use previous anchor date + some days
                $orderDiff = $status->order - $prevAnchor->order;
                $daysToAdd = $this->calculateDaysToAdd($orderDiff);
                return Carbon::parse($prevAnchor->statusDate)->addDays($daysToAdd)->format('Y-m-d');
            }
            
            return null;
        }

        // Calculate date based on next anchor
        $baseDate = Carbon::parse($nextAnchor->statusDate);
        $orderDiff = $nextAnchor->order - $status->order;
        $daysToSubtract = $this->calculateDaysToSubtract($orderDiff);
        
        return $baseDate->copy()->subDays($daysToSubtract)->format('Y-m-d');
    }

    private function calculateDaysToSubtract($orderDiff)
    {
        // Calculate days to subtract based on order difference
        if ($orderDiff <= 1) {
            return 1;
        } elseif ($orderDiff <= 3) {
            return $orderDiff * 2;
        } elseif ($orderDiff <= 5) {
            return $orderDiff * 3;
        } else {
            return $orderDiff * 5;
        }
    }

    private function calculateDaysToAdd($orderDiff)
    {
        // Calculate days to add when going forward from a previous anchor
        if ($orderDiff <= 1) {
            return 1;
        } elseif ($orderDiff <= 3) {
            return $orderDiff * 2;
        } elseif ($orderDiff <= 5) {
            return $orderDiff * 3;
        } else {
            return $orderDiff * 5;
        }
    }
}