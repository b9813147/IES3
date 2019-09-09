<?php

namespace App\Console\Commands;

use App\Http\Controllers\YunHeApi\RepeaterController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;


class UpdateApiDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:api-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command API Data Update';

    /**
     * Create a new command instance.
     * Inject Controller
     *
     * @param RepeaterController $repeaterController
     */
    public function __construct(RepeaterController $repeaterController)
    {
        parent::__construct();

        // $this->yunHeRepeaterController = $yunHeRepeaterController;
        $this->repeaterController      = $repeaterController;

    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $log_file_path = storage_path('logs/YuHeData.log');
        $log_info = [
            'date'   => date('Y-m-d H:i:s'),
            'status' => 'start Data Update'
        ];
        // 記錄 JSON 字串
        $log_info_json = json_encode($log_info) . "\r\n";
        // 記錄 Log
        File::append($log_file_path, $log_info_json);


        $this->repeaterController->runMiddle();
        // 檔案紀錄在 storage/test.log

        // 記錄當時的時間
        $log_info = [
            'date'   => date('Y-m-d H:i:s'),
            'status' => 'success Data Update'
        ];
        // 記錄 JSON 字串
        $log_info_json = json_encode($log_info) . "\r\n";

        // 記錄 Log
        File::append($log_file_path, $log_info_json);
    }

}
