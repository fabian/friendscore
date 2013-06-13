<?php

namespace FriendScore\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;

/**
 * Web User Controller
 */
class UserController
{
    protected $foursquare;
    protected $foursquareStorage;
    protected $facebook;
    protected $facebookStorage;

    /**
     * @InjectParams({
     *     "foursquare" = @Inject("friend_score.foursquare_bundle.service.foursquare"),
     *     "foursquareStorage" = @Inject("friend_score.foursquare_bundle.service.foursquare_storage"),
     *     "facebook" = @Inject("friend_score.facebook_bundle.service.facebook"),
     *     "facebookStorage" = @Inject("friend_score.facebook_bundle.service.facebook_storage"),
     * })
     */
    public function __construct($foursquare, $foursquareStorage, $facebook, $facebookStorage)
    {
        $this->foursquare = $foursquare;
        $this->foursquareStorage = $foursquareStorage;
        $this->facebook = $facebook;
        $this->facebookStorage = $facebookStorage;
    }

    /**
     * @Route("/user")
     * @Template()
     */
    public function indexAction()
    {
        $foursquareConnected = false;
        $user = $this->foursquareStorage->getUser();
        try {
            $this->foursquare->setAccessToken($user->getAccessToken());
            $this->foursquare->getCurrentUser();
            $foursquareConnected = true;
        } catch (\Exception $e) {
            // ignore, Foursquare not connected
        }

        $facebookConnected = false;
        $user = $this->facebookStorage->getUser();
        try {
            $this->facebook->setAccessToken($user->getAccessToken());
            $this->facebook->getCurrentUser();
            $facebookConnected = true;
        } catch (\Exception $e) {
            // ignore, Foursquare not connected
        }

        return array(
            'foursquare_connected' => $foursquareConnected,
            'foursquare_client_id' => $this->foursquare->getClientId(),
            'facebook_connected' => $facebookConnected,
            'facebook_client_id' => $this->facebook->getClientId(),
        );
    }
}
