<?php

namespace FriendScore\FoursquareBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;
use Guzzle\Http\Client;

use FriendScore\FoursquareBundle\Entity\User;

class DefaultController
{
    protected $redirectUri;

    protected $doctrine;
    protected $security;
    protected $router;
    protected $elastica;
    protected $foursquare;
    protected $foursquareAuth;

    protected $code = '';
    protected $accessToken = '';

    /**
     * @InjectParams({
     *     "doctrine" = @Inject("doctrine"),
     *     "security" = @Inject("security.context"),
     *     "router" = @Inject("router"),
     *     "elastica" = @Inject("friend_score.foursquare_bundle.elastica"),
     *     "foursquare" = @Inject("friend_score.foursquare_bundle.service.foursquare"),
     *     "foursquareAuth" = @Inject("friend_score.foursquare_bundle.service.foursquare_auth"),
     * })
     */
    public function __construct($doctrine, $security, $router, $elastica, $foursquare, $foursquareAuth)
    {
        $this->doctrine = $doctrine;
        $this->security = $security;
        $this->router = $router;
        $this->elastica = $elastica;
        $this->foursquare = $foursquare;
        $this->foursquareAuth = $foursquareAuth;

        $this->redirectUri = $this->router->generate('friendscore_foursquare_default_callback', array(), true);
    }

    protected function getUser()
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
     * @Route("/")
     * @Template()
     */
    public function indexAction()
    {
        $index = $this->elastica->getIndex('friendscore');
        if (!$index->exists()) {
            $index->create();
        }

        // search
        $user = $this->getUser();
        if ($user) {

            $userId = $user->getFoursquareId();
            $query = new \Elastica\Query\Term(array('user' => $userId));

            //Search on the index.
            $resultSet = $index->search(new \Elastica\Query\HasChild($query, 'foursquare_visit'));
            //var_dump($resultSet->getResponse());

            foreach ($resultSet->getResults() as $result) {
                var_dump($result->getData());
            }
        }

        return array('client_id' => $this->foursquare->getClientId(), 'redirect_uri' => $this->redirectUri);
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

        $user = $this->getUser();

        if (!$user) {
            $user = new User($this->security->getToken()->getUser());
        }

        $user->setFoursquareId($foursquareId);
        $user->setAccessToken($accessToken);

        $em = $this->doctrine->getManager();
        $em->persist($user);
        $em->flush();

        return new Response(json_encode($foursquareUser));
    }
}
