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
		$accessToken = 'BAAFDNZBZCxwzABAKh2DLgRPlEnC67OJyiFwFwUFhLVDSVTJA8D1ZCt3OjOKORJXTwXkGq6hEgHsZBsqImJjdVmggHbjc6jfsn8HXCcoXWp73KJiJIhY09C12RSAUyDsXgtlfw4rgo1Hbo37loddhOQxRjNt7lKMgifadYdBOsvczrAWefWoW';

		if(empty($accessToken)) {
	    	$this->state = md5(uniqid(rand(), TRUE)); // CSRF protection	
	   }
	   	
            
        // API call
        $request = $this->client->get('me');
        $request->getQuery()->set('access_token', $accessToken);
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
