<?php

/**
 * Laravel Socialite用のYahoo JAPAN ID(YConnect)ドライバ実装
 * https://qiita.com/zaburo/items/25ebbb3d1b580a0df9f3
 */

namespace SocialiteProviders\Yahoo;

use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'YAHOO';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [
        'openid',
        'profile',
        'email',
    ];

    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://auth.login.yahoo.co.jp/yconnect/v2/authorization', $state);
    }

    protected function getTokenUrl()
    {
        return 'https://auth.login.yahoo.co.jp/yconnect/v2/token';
    }

    public function getAccessToken($code)
    {
        $basic_auth_key = base64_encode($this->clientId . ":" . $this->clientSecret);

        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'headers' => [
                'Authorization' => 'Basic ' . $basic_auth_key,
            ],
            'form_params' => [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->redirectUrl
            ],
        ]);
        return $response->getBody()->getContents();
    }

    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://userinfo.yahooapis.jp/yconnect/v2/attribute?schema=openid', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);
        return json_decode($response->getBody(), true);
    }

    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => Arr::get($user, 'sub'),
            'nickname' => Arr::get($user, 'nickname', Arr::get($user, 'sub')),
            'name'     => trim(sprintf('%s %s', Arr::get($user, 'given_name'), Arr::get($user, 'family_name'))),
            'email'    => Arr::get($user, 'email'),
            'avatar'   => Arr::get($user, 'picture'),
        ]);
    }
}
