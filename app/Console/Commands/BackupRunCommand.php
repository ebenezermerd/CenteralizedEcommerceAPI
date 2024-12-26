<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Schedule;
use Illuminate\Console\Command;

class BackupRunCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:run-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the Laravel Backup command every 3 days';

    /**
     * Schedule the command to run every 3 days at 2 AM.
     */
    #[Schedule('0 2 */3 * *')]
    public function handle()
    {
        $this->call('backup:run');
        $this->info('Scheduled backup has been executed.');
    }
}
