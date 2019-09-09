<?php

namespace App\Jobs\TeamModel;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Entities\Oauth2MemberEntity;

/**
 * 發送 license 給 core service
 *
 * @package App\Jobs\TeamModel
 */
class SendLicense implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var string job name */
    public $jobName;

    /** @var array data */
    public $data;

    /**
     * Create a new job instance.
     *
     * @param Oauth2MemberEntity $oauth2MemberEntity
     *
     * @return void
     */
    public function __construct(Oauth2MemberEntity $oauth2MemberEntity)
    {
        $this->onConnection('resque');
        $this->onQueue(config('queue.team_model.queue'));
        $this->jobName = config('queue.team_model.jobs.sendLicense');

        // 發送的資料
        $this->data = [
            'TEAMModelId' => $oauth2MemberEntity->oauth2_account,
            'licenseStatus' => $oauth2MemberEntity->belongsToSystemAuthority->license_is_valid,
            'exp' => $oauth2MemberEntity->belongsToSystemAuthority->EDate,
            'memberId' => $oauth2MemberEntity->MemberID,
        ];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

    }
}
