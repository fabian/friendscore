<?php

namespace FriendScore\FacebookBundle\Service;

use JMS\DiExtraBundle\Annotation\Service;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;
use Guzzle\Http\Client;

/**
 * @Service
 */
class Facebook
{
    protected $client;

    protected $clientId;
    protected $clientSecret;

    protected $accessToken = '';

    /**
     * @InjectParams({
     *     "client" = @Inject("friend_score.facebook_bundle.facebook.client"),
     *     "clientId" = @Inject("%friend_score.facebook_bundle.client_id%"),
     *     "clientSecret" = @Inject("%friend_score.facebook_bundle.client_secret%"),
     * })
     */
    public function __construct($client, $clientId, $clientSecret)
    {
        $this->client = $client;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    public function getClientId()
    {
        return $this->clientId;
    }

    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    public function getCurrentUser()
    {
        $request = $this->client->get('me');
        $query = $request->getQuery();
        $query->set('access_token', $this->accessToken);
        $response = $request->send();

        $body = $response->getBody();
        $json = json_decode($body);

        return $json;
    }
    
    public function generateAccessToken($code, $redirectUri)
    {
        // $request = $this->client->get('oauth/access_token', null, array (
//             'client_id' => $this->clientId,
//             'client_secret' => $this->clientSecret,
//             'redirect_uri' => $redirectUri,
//             'code' => $code,
//         ));


        $request = $this->client->get('oauth/access_token');
      //  var_dump($this->client->getBaseUrl());
        $query = $request->getQuery();
        $query->set('client_id', $this->clientId);
        $query->set('client_secret', $this->clientSecret);
        $query->set('redirect_uri', $redirectUri);
        $query->set('code', $code);
        
        $response = $request->send();
        var_dump($this->clientId);
        var_dump($this->clientSecret);
        var_dump($code);
        var_dump($redirectUri);
        
        $body = $response->getBody();
        parse_str($body, $arr);
        
        return $arr['access_token'];
    }
}
