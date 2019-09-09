<?php

namespace App\Console\Commands;

use App\Http\Controllers\YunHeApi\RepeaterController;
use Illuminate\Console\Command;

class FileUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:file-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command file update';

    /**
     * Create a new command instance.
     *
     * @param RepeaterController $repeaterController
     */
    public function __construct(RepeaterController $repeaterController)
    {
        parent::__construct();
        $this->repeaterController = $repeaterController;
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
            'status' => 'start file update'
        ];
        // 記錄 JSON 字串
        $log_info_json = json_encode($log_info) . "\r\n";
        // 記錄 Log
        \File::append($log_file_path, $log_info_json);


        $this->repeaterController->updateToFile();
        // 檔案紀錄在 storage/test.log

        // 記錄當時的時間
        $log_info = [
            'date'   => date('Y-m-d H:i:s'),
            'status' => 'success file update'
        ];
        // 記錄 JSON 字串
        $log_info_json = json_encode($log_info) . "\r\n";

        // 記錄 Log
        \File::append($log_file_path, $log_info_json);
    }
}
