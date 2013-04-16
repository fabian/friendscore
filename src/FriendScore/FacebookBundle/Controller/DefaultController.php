<?php

namespace FriendScore\FacebookBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Guzzle\Http\Client;

class DefaultController extends Controller
{
    protected $clientId = '355382504571696';
    protected $clientSecret = 'a4ddd707fe4bcf53e96c4b9e5313991a';

    protected $redirectUri = 'http://localhost:8000/facebook/callback';

    protected $client;
	
	protected $state = '';
    protected $code = '';
    protected $accessToken = '';

    public function __construct()
    {
        $this->client = new Client('https://graph.facebook.com');
    }
    
    /**
     * @Route("/")
     * @Template()
     */
    public function indexAction()
    {
		$accessToken = 'AAAFDNZBZCxwzABAIYyXQCOzEh1IZCNcUEX8dajnPTecDglWVRgKuaFGcVyVwlCq44ETlJlvvnHDvypbEi2fg5FwZAZArHpWcRv8lkiZBccXgZDZD';

		if(empty($accessToken)) {
	    	$this->state = md5(uniqid(rand(), TRUE)); // CSRF protection	
	   }
	   	
            
        // API call
        //first call me to get the id of the user
        $request = $this->client->get('me');
        $request->getQuery()->set('access_token', $accessToken);
        $response = $request->send();

        $body = $response->getBody();
        $jsonBody = json_decode($body);
        
        //get data of user
        $request = $this->client->get($jsonBody->id);
        $query = $request->getQuery();
        $query->set('access_token', $accessToken);
        $query->set('fields', 'id,name,friends.fields(first_name,last_name,checkins.fields(coordinates,place))');
        
	    $response = $request->send();

        $body = $response->getBody();
        var_dump(json_decode($body));
		
		return array('client_id' => $this->clientId, 'redirect_uri' => $this->redirectUri, 'state' => $this->state);
    }

    /**
     * @Route("/callback")
     */
    public function callbackAction(Request $request)
    {
        $code = $request->get('code');	
		var_dump($code);
		
        $request = $this->client->post('oauth/access_token', null, array (
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'code' => $code,
        ));
        $response = $request->send();

        $body = $response->getBody();
		
		$params = array();
		
		parse_str($response->getBody(), $params);

        return new Response($body);
    }
}
