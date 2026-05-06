<?php

namespace App\Http\Middleware;

use App\Jobs\RunDailyBackupJob;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class EnsureDailyBackup
{
    public function handle(Request $request, Closure $next)
    {
        $today = Carbon::today()->toDateString();
        $lastBackupDate = Cache::get('last_backup_date');

        if ($lastBackupDate !== $today) {
            $queueConnection = config('queue.default');

            try {
                if ($queueConnection === 'sync') {
                    // Local/Client machines – run backup immediately in this request
                    Artisan::call('backup:run');
                } else {
                    // Servers / dev where a queue worker is running
                    RunDailyBackupJob::dispatch();
                }
            } catch (\Throwable $e) {
                // Never block the user request because backup failed.
                Log::warning('Daily backup failed but request was allowed to continue', [
                    'error' => $e->getMessage(),
                    'request_path' => $request->path(),
                ]);
            }

            Cache::forever('last_backup_date', $today);
        }

        return $next($request);
    }
}


