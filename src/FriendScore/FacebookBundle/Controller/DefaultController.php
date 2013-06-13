<?php

namespace FriendScore\FacebookBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;
use Guzzle\Http\Client;

use FriendScore\FacebookBundle\Entity\User;

class DefaultController
{
    protected $redirectUri;

    protected $router;
    protected $facebook;
    protected $facebookStorage;
	
	/**
	 * @InjectParams({
	 *     "router"   = @Inject("router"),
	 *     "facebook" = @Inject("friend_score.facebook_bundle.service.facebook"),
	 *     "facebookStorage" = @Inject("friend_score.facebook_bundle.service.facebook_storage"),
	 * })
	 */
    public function __construct($router, $facebook, $facebookStorage)
    {
        $this->router   = $router;
        $this->facebook = $facebook;
        $this->facebookStorage = $facebookStorage;

        $this->redirectUri = $this->router->generate('friendscore_facebook_default_callback', array(), true);
    }

    /**
     * @Route("/callback")
     */
    public function callbackAction(Request $request)
    {
        $code = $request->get('code');

        $accessToken = $this->facebook->generateAccessToken($code, $this->redirectUri);

        $this->facebook->setAccessToken($accessToken);
        $facebookUser = $this->facebook->getCurrentUser();
        $facebookId = $facebookUser->id;

        $user = $this->facebookStorage->getUser();

        $user->setFacebookId($facebookId);
        $user->setAccessToken($accessToken);

        $this->facebookStorage->saveUser($user);

        return new RedirectResponse($this->router->generate('friendscore_web_user_index', array(), true));
    }
}
