<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ViewExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


class ExcelController extends Controller
{

    //匯出excel
    public function export(Request $request)
    {
        $data = $request->all();

        return ((new ViewExport(compact('data')))->download());
    }

    //計算目錄使用空間
    function CalcDirectorySize($DirectoryPath)
    {
        $Size = 0;
        $Dir = opendir($DirectoryPath);
        if (!$Dir)
            return -1;

        while (($File = readdir($Dir)) !== false) {
            // Skip file pointers
            if ($File[0] == '.') continue;
            // Go recursive down, or add the file size
            //判斷是不是一個目錄
            if (is_dir($DirectoryPath . $File)) {

                $Size += $this->CalcDirectorySize($DirectoryPath . $File . DIRECTORY_SEPARATOR);

            } else {

                $Size += filesize($DirectoryPath . $File);
            }
        }
        closedir($Dir);
        //單位換算
        if ($Size >= 1073741824) {
            $Size = number_format($Size / 1073741824, 2) . ' GB';
        } elseif ($Size >= 1048576) {
            $Size = number_format($Size / 1048576, 2) . ' MB';
        } elseif ($Size >= 1024) {
            $Size = number_format($Size / 1024, 2) . ' kB';
        } elseif ($Size > 1) {
            $Size = $Size . ' bytes';
        } elseif ($Size == 1) {
            $Size = $Size . ' byte';
        } else {
            $Size = '0 bytes';
        }

        return $Size;
    }

    // 傳送計算目錄路徑
    public function path()
    {
        $path = $_SERVER['DOCUMENT_ROOT'] . '/css/';

        return $this->CalcDirectorySize($path);


    }


}


