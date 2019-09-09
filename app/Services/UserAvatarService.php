<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\InteractsWithTime;
use App\Repositories\Eloquent\MemberRepository;

class UserAvatarService
{
    use InteractsWithTime;

    /** @var MemberRepository */
    protected $memberRepository;

    /**
     * UserAvatarService constructor.
     *
     * @param MemberRepository $memberRepository
     */
    public function __construct(MemberRepository $memberRepository)
    {
        $this->memberRepository = $memberRepository;
    }

    /**
     * 上傳使用者的頭像檔案
     *
     * @param integer $memberId Member ID
     * @param object $file Request->file
     *
     * @return bool
     */
    public function uploadedFile($memberId, $file)
    {
        try {
            // 刪除舊檔案
            $this->deleteFile($memberId);

            $newFileName = $this->currentTime() . '.' . $file->extension();

            // 上傳
            $path = Storage::disk('ies2')->putFileAs(avatar_path($memberId), $file, habook_base64_encode($newFileName));
            if (empty($path)) {
                return false;
            }

            // 寫入 DB
            $this->memberRepository->update(['HeadImg' => $newFileName], $memberId);

            return Storage::disk('ies2');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 刪除使用者頭像
     *
     * @param integer $memberId Member ID
     *
     * @return bool
     */
    public function deleteFile($memberId)
    {
        try {
            $user = $this->memberRepository->findForUser($memberId);
            if (!$user) {
                return false;
            }

            // 頭像檔案
            $file = avatar_path($memberId) . '/' . habook_base64_encode($user->HeadImg);

            // 檢查是否存在並刪除檔案
            $exists = Storage::disk('ies2')->exists($file);
            if ($exists) {
                if (!Storage::disk('ies2')->delete($file)) {
                    return false;
                }
            }

            // 寫入 DB
            $this->memberRepository->update(['HeadImg' => null], $memberId);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}