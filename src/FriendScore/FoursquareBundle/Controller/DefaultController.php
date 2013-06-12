<?php

namespace FriendScore\FoursquareBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;
use Guzzle\Http\Client;

use FriendScore\FoursquareBundle\Entity\User;

/**
 * Foursquare Controller
 */
class DefaultController
{
    protected $redirectUri;

    protected $router;
    protected $foursquare;
    protected $foursquareAuth;
    protected $foursquareStorage;

    /**
     * @InjectParams({
     *     "router" = @Inject("router"),
     *     "foursquare" = @Inject("friend_score.foursquare_bundle.service.foursquare"),
     *     "foursquareAuth" = @Inject("friend_score.foursquare_bundle.service.foursquare_auth"),
     *     "foursquareStorage" = @Inject("friend_score.foursquare_bundle.service.foursquare_storage"),
     * })
     */
    public function __construct($router, $foursquare, $foursquareAuth, $foursquareStorage)
    {
        $this->router = $router;
        $this->foursquare = $foursquare;
        $this->foursquareAuth = $foursquareAuth;
        $this->foursquareStorage = $foursquareStorage;

        $this->redirectUri = $this->router->generate('friendscore_foursquare_default_callback', array(), true);
    }

    /**
     * @Route("/callback")
     */
    public function callbackAction(Request $request)
    {
        $code = $request->get('code');

        $accessToken = $this->foursquareAuth->generateAccessToken($code, $this->redirectUri);

        $this->foursquare->setAccessToken($accessToken);
        $foursquareUser = $this->foursquare->getCurrentUser();
        $foursquareId = $foursquareUser->id;

        $user = $this->foursquareStorage->getUser();

        $user->setFoursquareId($foursquareId);
        $user->setAccessToken($accessToken);

        $this->foursquareStorage->saveUser($user);

        return new RedirectResponse($this->router->generate('friendscore_web_user_index', array(), true));
    }
}
