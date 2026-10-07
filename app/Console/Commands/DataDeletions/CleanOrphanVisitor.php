<?php

namespace App\Console\Commands\DataDeletions;

use Throwable;
use App\Models\Visitor;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanOrphanVisitor extends Command
{
    protected $signature = 'clean-orphan:visitor {--batch=10000 : Number of records to delete per batch}';

    protected $description = 'Delete old orphan visitors and preregister_visitors older than 1 year that are expired.';

    public function handle(): int
    {
        $expiryDate = Carbon::now()->subYear()->toDateString();
        $batchSize = (int) $this->option('batch');

        $this->info("Checking for expired preregister_visitors older than {$expiryDate}...");

        try {
            DB::transaction(function () use ($expiryDate, $batchSize) {

                // DELETE EXPIRED PRE-BOOKED VISITORS (>1 year & expired)
                $expiredPrebooksQuery = DB::table('preregister_visitors')
                    ->whereDate('validity_end_date', '<', $expiryDate)
                    ->where('is_qr_code_expired', 1);

                $totalExpiredPrebooks = $expiredPrebooksQuery->count();
                $this->info("Found {$totalExpiredPrebooks} expired preregisters older than 1 year.");

                if ($totalExpiredPrebooks > 0) {
                    if (! $this->confirm("Do you want to delete these {$totalExpiredPrebooks} expired preregisters?")) {
                        $this->info('Preregister deletions cancelled.');

                        return;
                    }

                    $this->deleteExpiredPreregisters($expiredPrebooksQuery, $batchSize);
                }

                // DELETE ORPHAN VISITORS (only after expired preregisters are removed)
                $this->info("\nScanning orphan visitors older than 1 year...");

                $totalOrphans = $this->getDeletableVisitorsQuery($expiryDate)->count();

                if ($totalOrphans === 0) {
                    $this->info('No orphan visitors eligible for deletion.');

                    return;
                }

                $this->warn("Found {$totalOrphans} orphan visitors eligible for cleanup.");

                if (! $this->confirm("Do you want to delete these {$totalOrphans} orphan visitors?")) {
                    $this->info('Visitor deletion cancelled.');

                    return;
                }

                $this->deleteOrphanVisitors($expiryDate, $batchSize, $totalOrphans);
            }); // End of DB::transaction

            $this->info("Cleanup complete!");
        } catch (Throwable $e) {
            $this->error("Cleanup failed! Transaction rolled back. Error: " . $e->getMessage());
        }

        return self::SUCCESS;
    }

    /**
     * Delete expired preregister_visitors in batches.
     */
    private function deleteExpiredPreregisters($query, int $batchSize)
    {
        $totalDeleted = 0;

        do {
            $ids = (clone $query)->limit($batchSize)->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            DB::table('preregister_visitors')->whereIn('id', $ids)->delete();

            $totalDeleted += $ids->count();
            $this->info("Deleted {$ids->count()} expired preregisters...");
        } while (true);

        $this->info("Total expired preregisters deleted: {$totalDeleted}");
    }

    /**
     * Delete fully orphan visitors in batches.
     */
    private function deleteOrphanVisitors(string $expiryDate, int $batchSize, int $totalOrphans)
    {
        $bar = $this->output->createProgressBar($totalOrphans);
        $bar->start();

        $totalDeleted = 0;

        do {
            $visitorIds = $this->getDeletableVisitorsQuery($expiryDate)
                ->limit($batchSize)
                ->pluck('id');

            if ($visitorIds->isEmpty()) {
                break;
            }

            $deleted = Visitor::whereIn('id', $visitorIds)->forceDelete();
            $totalDeleted += $deleted;

            $bar->advance($deleted);
        } while (true);

        $bar->finish();
        $this->newLine(2);

        $this->info("Total orphan visitors deleted: {$totalDeleted}");
    }

    /**
     * Query defining a visitor eligible for deletion.
     */
    private function getDeletableVisitorsQuery(string $expiryDate)
    {
        return DB::table('visitors')
            ->whereDate('visitors.updated_at', '<', $expiryDate)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('visitor_logs')
                    ->whereColumn('visitor_logs.visitor_id', 'visitors.id');
            }) // No visitor logs exist
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('blacklisted_visitors')
                    ->whereColumn('blacklisted_visitors.visitor_id', 'visitors.id');
            }) // No blacklisted visitor exists
            ->whereNotExists(function ($query) use ($expiryDate) {
                $query->select(DB::raw(1))
                    ->from('preregister_visitors')
                    ->whereColumn('preregister_visitors.visitor_id', 'visitors.id')
                    ->where(function ($sub) use ($expiryDate) {
                        $sub->where('is_qr_code_expired', 0) // QR still active
                            ->orWhere('validity_end_date', '>=', $expiryDate); // QR still valid
                    });
            }); // No active preregisters (flag active OR date still valid)
    }
}
