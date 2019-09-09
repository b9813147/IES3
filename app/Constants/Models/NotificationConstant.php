<?php

namespace App\Constants\Models;

/**
 * 定義 Table `notification` 的欄位參數
 *
 * @package App\Constants
 */
abstract class NotificationConstant
{
    /*
     |--------------------------------------------------------------------------
     | ntype 通知類型
     |--------------------------------------------------------------------------
    */
    const N_TYPE_GENERAL = 1; //一般通知
    const N_TYPE__E_PAPER_ADD = 2; // 電子紙條
    const N_TYPE_COURSE_ADD = 3; // 加入課程
    const N_TYPE_COURSE_DEL = 4; // 退出課程
    const N_TYPE_MATERIAL = 5; // 發佈教材
    const N_TYPE_FLIP_CLASS = 6; // 發佈翻轉課堂
    const N_TYPE_HOMEWORK = 7; // 發佈家庭作業
    const N_TYPE_EXAM = 8; // 發佈線上評量
    const N_TYPE_MOVIE = 9; // 發佈學習影片
    const N_TYPE_HI_TEACH = 10; // HiTeach 上傳評量紀錄

    const N_TYPE_HI_TEACH_FILE = 11; // HiTeach 上傳附件
    const N_TYPE_EZ = 12; // ezStation 上傳影片
    const N_TYPE_OMR = 13; // 網路閱卷 - 上傳評量紀錄
    const N_TYPE_OMR_FILE = 14; // 網路閱卷 - 上傳附件
    const N_TYPE_ADAS = 15; // 產生診斷報告
    const N_TYPE_ADAS_GRADE = 16; // 產生年級報告
    const N_TYPE_E_PAPER_DEL = 17; // 電子紙條 - 刪除
    const N_TYPE_MATERIAL_DEL = 18; // 教材 - 刪除
    const N_TYPE_FLIP_CLASS_DEL = 19; // 翻轉課堂 - 刪除
    const N_TYPE_FLIP_CLASS_OUTLINE = 20; // 翻轉課堂課綱 - 發佈

    const N_TYPE_FLIP_CLASS_OUTLINE_UPD = 21; // 翻轉課堂課綱 - 修改
    const N_TYPE_FLIP_CLASS_OUTLINE_DEL = 22; // 翻轉課堂課綱 - 刪除
    const N_TYPE_HOMEWORK_UPD = 23; // 家庭作業 - 修改
    const N_TYPE_HOMEWORK_DEL = 24; // 家庭作業 - 刪除
    const N_TYPE_EXAM_UPD_STATUS = 25; // 評量測驗 - 修改狀態
    const N_TYPE_EXAM_UPD_DATA = 26; // 評量測驗 - 修改評量內容
    const N_TYPE_EXAM_DEL = 27; // 評量測驗 - 刪除
    const N_TYPE_MOVIE_DEL = 28; // 學習影片 - 刪除
    const N_TYPE_SCORE = 29; // 一般評量紀錄發佈
    const N_TYPE_SCORE_DEL = 30; // 評量紀錄 - 刪除
    const N_TYPE_EZ_DEL = 31; // ezStation 上傳影片 - 刪除

    const N_TYPE_ADAS_DEL = 32; // 診斷報告 - 刪除
    const N_TYPE_ADAS_GRADE_DEL = 33; // 年級報告 - 刪除
    const N_TYPE_MOVIE_UPD = 34; // 學習影片 - 修改
    const N_TYPE_COURSE_ANNOUNCE = 35; // 課程公告 - 發佈
    const N_TYPE_COURSE_ANNOUNCE_UPD = 36; // 課程公告 - 修改
    const N_TYPE_COURSE_ANNOUNCE_DEL = 37; // 課程公告 - 刪除
    const N_TYPE_TEST_ITEM_INFO = 38; // 評量測驗題目或逐題作答記錄 - 發佈
    const N_TYPE_TEST_ITEM_INFO_UPD = 39; // 評量測驗題目或逐題作答記錄 - 修改
    const N_TYPE_TEST_ITEM_INFO_DEL = 40; // 評量測驗題目或逐題作答記錄 - 刪除

    const N_TYPE_COURSE_UPD = 41; // 課程 - 修改
    const N_TYPE_HI_TEACH_UPD = 42; // HiTeach 上傳評量紀錄 - 修改
    const N_TYPE_OMR_UPD = 43; // 網路閱卷 - 上傳評量紀錄 - 修改
    const N_TYPE_SCORE_UPD = 44; // 一般評量紀錄發佈 - 修改

    /** AsignFlag = T 在 notification_member table 有資料 */
    const ASIGN_FLAG_TRUE = 'T';

    /** AsignFlag = F 在 notification_member table 沒有資料 */
    const ASIGN_FLAG_FALSE = 'F';
}