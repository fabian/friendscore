<?php

namespace FriendScore\FoursquareBundle\Service;

use JMS\DiExtraBundle\Annotation\Service;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;
use Guzzle\Http\Client;

/**
 * @Service
 */
class Foursquare
{
    protected $client;

    protected $clientId;
    protected $clientSecret;

    protected $accessToken = '';

    protected $version = '20130415';

    /**
     * @InjectParams({
     *     "client" = @Inject("friend_score_foursquare.foursquare.client"),
     *     "clientId" = @Inject("%friend_score_foursquare.foursquare.client_id%"),
     *     "clientSecret" = @Inject("%friend_score_foursquare.foursquare.client_secret%"),
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
        $request = $this->client->get('v2/users/self');
        $query = $request->getQuery();
        $query->set('oauth_token', $this->accessToken);
        $query->set('v', $this->version);
        $response = $request->send();

        $body = $response->getBody();
        $json = json_decode($body);

        return $json->response->user;
    }
}
