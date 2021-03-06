<?php
namespace App;

use Swoole\Client\CURL;

class CrowdUser
{
    const BASE_URL = 'http://10.10.1.30:8095/crowd/rest/';
    const EMAIL_DOMAIN_NAME = 'chelun.com';

    const CURL_USER = 'eclicks_confluence';
    const CURL_PASSWORD = 'eclicks0716';

    /**
     * @return CURL
     */
    protected function getCurl()
    {
        $curl = new CURL(false);
        $curl->setHeader('Content-Type', 'application/json');
        $curl->setHeader('Accept', 'application/json');
        $curl->setCredentials(self::CURL_USER, self::CURL_PASSWORD);
        $curl->setHeaderOut();
        return $curl;
    }

    static function setPassword($username, $password)
    {
        $curl = self::getCurl();
        $curl->setMethod('PUT');
        $res = $curl->post(self::BASE_URL . 'usermanagement/1/user/password?username=' . urlencode($username), json_encode([
            'value' => $password,
        ]));
        if ($curl->httpCode != 204)
        {
            var_dump($res, $curl->info);
            return false;
        }
        return true;
    }

    static function newAccount($username, $realname, $password)
    {
        //1---创建用户
        $curl = self::getCurl();
        $res = $curl->post(self::BASE_URL . 'usermanagement/1/user', json_encode([
            'name' => $username,
            'first-name' => mb_substr($realname, 0, 1),
            'last-name' => mb_substr($realname, 1),
            'display-name' => $realname,
            'email' => $username . '@' . self::EMAIL_DOMAIN_NAME,
            'password' => ['value' => $password,],
            'active' => true,
        ]));
        if ($curl->httpCode != 201)
        {
            //用户已存在了
            if ($curl->httpCode == 401)
            {
                return false;
            }
            var_dump($res,$curl->httpCode, $curl->info);
            return false;
        }

        //2—移除组
        $curl = self::getCurl();
        $curl->setMethod('DELETE');
        $curl->get(self::BASE_URL . 'usermanagement/1/group/user/direct?username=' . urlencode($username) . '&groupname=confluence-users');

        //3—加入组
        $curl = self::getCurl();
        $res = $curl->post(self::BASE_URL . 'usermanagement/1/user/group/direct?username=' . urlencode($username), json_encode([
            'name' => 'confluence-users',
        ]));

        if ($curl->httpCode != 201)
        {
            var_dump($res, $curl->info);
            return false;
        }
        return true;
    }

    /**
     * @param $username
     * @return bool|string
     */
    static function get($username)
    {
        $curl = self::getCurl();
        $ret = $curl->get(self::BASE_URL . 'usermanagement/1/user?username='.urlencode($username));
        if (empty($ret))
        {
            if ($curl->httpCode == 404)
            {
                return false;
            }
            else
            {
                throw new \Exception("curl->get [" . $curl->getEffectiveUrl() . '] failed. HttpCode=' . $curl->httpCode);
            }
        }
        return json_decode($ret, true);
    }
}