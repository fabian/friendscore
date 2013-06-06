<?php

namespace FriendScore\FoursquareBundle\Service;

use JMS\DiExtraBundle\Annotation\Service;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;
use Guzzle\Http\Client;

/**
 * @Service
 */
class FoursquareAuth
{
    protected $client;

    protected $clientId;
    protected $clientSecret;

    /**
     * @InjectParams({
     *     "client" = @Inject("friend_score.foursquare_bundle.foursquare_auth.client"),
     *     "clientId" = @Inject("%friend_score.foursquare_bundle.client_id%"),
     *     "clientSecret" = @Inject("%friend_score.foursquare_bundle.client_secret%"),
     * })
     */
    public function __construct($client, $clientId, $clientSecret)
    {
        $this->client = $client;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    public function generateAccessToken($code, $redirectUri)
    {
        $request = $this->client->post('oauth2/access_token', null, array (
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri,
            'code' => $code,
        ));
        $response = $request->send();

        $body = $response->getBody();
        $json = json_decode($body);

        return $json->access_token;
    }
}
