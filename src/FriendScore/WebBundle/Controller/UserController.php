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
    protected $doctrine;
    protected $security;
    protected $router;
    protected $foursquare;

    /**
     * @InjectParams({
     *     "doctrine" = @Inject("doctrine"),
     *     "security" = @Inject("security.context"),
     *     "router" = @Inject("router"),
     *     "foursquare" = @Inject("friend_score.foursquare_bundle.service.foursquare"),
     * })
     */
    public function __construct($doctrine, $security, $router, $foursquare)
    {
        $this->doctrine = $doctrine;
        $this->security = $security;
        $this->router = $router;
        $this->foursquare = $foursquare;
    }

    protected function getFoursquareUser()
    {
        $currentUser = $this->security->getToken()->getUser();

        $user = $this->doctrine
            ->getRepository('FriendScoreFoursquareBundle:User')
            ->findOneBy(
                array('user' => $currentUser)
            );

        return $user;
    }

    /**
     * @Route("/user")
     * @Template()
     */
    public function indexAction()
    {
        $foursquareConnected = false;

        $user = $this->getFoursquareUser();
        if ($user) {
            try {
                $this->foursquare->setAccessToken($user->getAccessToken());
                $this->foursquare->getCurrentUser();
                $foursquareConnected = true;
            } catch (\Exception $e) {
                // ignore, Foursquare not connected
            }
        }

        return array(
            'foursquare_connected' => $foursquareConnected,
            'foursquare_client_id' => $this->foursquare->getClientId(),
        );
    }
}
