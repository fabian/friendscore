<?php

namespace FriendScore\FacebookBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;
use Guzzle\Http\Client;

use FriendScore\FacebookBundle\Entity\User;

class DefaultController
{
      // protected $redirectUri = 'http://localhost:8000/facebook/callback';

    
    protected $redirectUri;
    
    protected $doctrine;
    protected $security;
    protected $router;
    protected $elastica;
    protected $facebook;

	protected $state = '';
    protected $code = '';
    protected $accessToken = '';
	
	
	/**
	 * @InjectParams({
	 *     "doctrine" = @Inject("doctrine"),
	 *     "security" = @Inject("security.context"),
	 *     "router"   = @Inject("router"),
	 *     "elastica" = @Inject("friend_score.facebook_bundle.elastica"),
	 *     "facebook" = @Inject("friend_score.facebook_bundle.service.facebook"),
	 * })
	 */
    public function __construct($doctrine, $security, $router, $elastica, $facebook)
    {
        $this->doctrine = $doctrine;
        $this->security = $security;
        $this->router   = $router;
        $this->elastica = $elastica;
        $this->facebook = $facebook;

        $this->redirectUri = $this->router->generate('friendscore_facebook_default_callback', array(), true);
    }
    
    protected function getUser()
    {
        $currentUser = $this->security->getToken()->getUser();

        $user = $this->doctrine
            ->getRepository('FriendScoreFacebookBundle:User')
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
        $places = array();
        $user = $this->getUser();
        if ($user) {

            $userId = $user->getFacebookId();
            $query = new \Elastica\Query\Term(array('user' => $userId));

            //Search on the index.
            $resultSet = $index->search(new \Elastica\Query\HasChild($query, 'facebook_visit'));
            //var_dump($resultSet->getResponse());

            foreach ($resultSet->getResults() as $result) {
                $places[] = $result->getData();
            }
        }
        
        return array('client_id' => $this->facebook->getClientId(), 'redirect_uri' => $this->redirectUri, 'places' => $places);
		
		
        // API call
        //first call me to get the id of the user
        // $request = $this->facebook->get('me');
//         $request->getQuery()->set('access_token', $accessToken);
//         $response = $request->send();
// 
//         $body = $response->getBody();
//         $jsonBody = json_decode($body);
//         
//         //get data of user
//         $request = $this->facebook->get($jsonBody->id);
//         $query = $request->getQuery();
//         $query->set('access_token', $accessToken);
//         $query->set('fields', 'id,name,friends.fields(first_name,last_name,checkins.fields(coordinates,place))');
//         
//         $response = $request->send();
// 
//         $body = $response->getBody();
//         var_dump(json_decode($body));
//         
//         return array('client_id' => $this->clientId, 'redirect_uri' => $this->redirectUri, 'state' => $this->state);
		
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

        $user = $this->getUser();

        if (!$user) {
            $user = new User($this->security->getToken()->getUser());
        }

        $user->setFacebookId($facebookId);
        $user->setAccessToken($accessToken);

        $em = $this->doctrine->getManager();
        $em->persist($user);
        $em->flush();

        return new Response(json_encode($facebookUser));
    }
}
