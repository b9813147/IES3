<?php

namespace App\Listeners;

use App\Events\SokratesAddChannel;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Libraries\SokratesApi\Client;
use App\Libraries\SokratesApi\ApiTokenFactory;
use Illuminate\Support\Facades\Log;
use App\Repositories\Eloquent\MemberRepository;
use App\Exceptions\SokratesSendAddChannelException;

class SendSokratesAddChannelNotification
{
    protected $memberRepository;
    protected $apiTokenFactory;

    /**
     * Create the event listener.
     *
     * @param MemberRepository $memberRepository
     * @param ApiTokenFactory $apiTokenFactory
     *
     * @return void
     */
    public function __construct(MemberRepository $memberRepository, ApiTokenFactory $apiTokenFactory)
    {
        $this->memberRepository = $memberRepository;
        $this->apiTokenFactory = $apiTokenFactory;
    }

    /**
     * 向蘇格拉底發送加入頻道資料
     *
     * @param SokratesAddChannel $event
     *
     * @return void
     *
     * @throws SokratesSendAddChannelException
     * @throws \App\Exceptions\RepositoryException
     */
    public function handle(SokratesAddChannel $event)
    {
        $user = $this->memberRepository->findForUser($event->memberId);
        if (!$user) {
            throw new \InvalidArgumentException();
        }

        $schoolCode = (empty($event->channel)) ? $user->SchoolCode : $event->channel;

        // 建立 token
        $token = $this->apiTokenFactory->make([
            'iss' => 'IES-WEB',
            'aud' => 'sokradeo',
            'sub' => 'tj5uwasq4a1bn',
            'accType' => 'Client',
            'accUser' => '1',
            'name' => 'IES-WEB',
            'email' => null,
        ], 60);

        // 加入頻道
        $sokrates = new Client(config('sokrates.contests_api.url'), $token);
        $sokratesRequest = $sokrates->api('contests')->createUsers([
            'schoolCode' => $schoolCode,
            'userList' => array('id' => $event->teamModelId, 'name' => $user->RealName, 'email' => $user->Email)
        ]);

        Log::info($sokratesRequest);

        if (!isset($sokratesRequest['status']) || $sokratesRequest['status'] !== 1) {
            throw new SokratesSendAddChannelException();
        }

        return $sokratesRequest;
    }
}
