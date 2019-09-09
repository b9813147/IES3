<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BigBlueOrderAuthorizationService;

class UpdateBigBlueOrderAuthorization extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'BigBlueOrderAuthorization:Update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '更新 Big Blue 訂單授權';

    /**
     * @var BigBlueOrderAuthorizationService
     */
    protected $bigBlueOrderAuthorizationService;

    /**
     * Create a new command instance.
     *
     * @param BigBlueOrderAuthorizationService $bigBlueOrderAuthorizationService
     *
     * @return void
     */
    public function __construct(BigBlueOrderAuthorizationService $bigBlueOrderAuthorizationService)
    {
        parent::__construct();

        $this->bigBlueOrderAuthorizationService = $bigBlueOrderAuthorizationService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function handle()
    {
        $bar = $this->output->createProgressBar(2);

        $this->bigBlueOrderAuthorizationService->updateAllSchoolSalesAuthorization();
        $bar->advance();

        $this->bigBlueOrderAuthorizationService->updateAllTrialAuthorization();
        $bar->advance();

        $bar->finish();
    }
}
