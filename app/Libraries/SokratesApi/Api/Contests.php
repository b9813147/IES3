<?php

namespace App\Libraries\SokratesApi\Api;

class Contests extends AbstractApi
{
    /**
     * 加入頻道
     *
     * @param string $parameters 參數
     * $parameters = [
     *      "schoolCode" => ""
     *      "userList" => [
     *          [
     *              "id" => "",
     *              "name" => "",
     *              "email" => "",
     *          ]
     *      ]
     * ]
     *
     * @return array
     *
     * @throws
     */
    public function createUsers($parameters)
    {
        if (!isset($parameters['schoolCode'], $parameters['userList']) || !is_array($parameters['userList'])) {
            throw new \InvalidArgumentException();
        }

        if (!isset($parameters['userList'][0])) {
            $parameters['userList'] = array($parameters['userList']);
        }

        foreach ($parameters['userList'] as $key => $user) {
            if (!isset($user['id'], $user['name'])) {
                throw new \InvalidArgumentException();
            }

            if (!array_key_exists('email', $user)) {
                throw new \InvalidArgumentException();
            }
        }

        return $this->post('/api/contests/member/', $parameters);
    }
}