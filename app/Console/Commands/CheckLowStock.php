<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\StockMonitoringService;

class CheckLowStock extends Command
{
    protected $signature = 'stock:check-low';
    protected $description = 'Check for products with low stock and notify vendors';

    private $stockMonitoringService;

    public function __construct(StockMonitoringService $stockMonitoringService)
    {
        parent::__construct();
        $this->stockMonitoringService = $stockMonitoringService;
    }

    public function handle()
    {
        $this->info('Checking for low stock products...');
        $this->stockMonitoringService->checkLowStock();
        $this->info('Low stock check completed.');
    }
}
