<?php

namespace FriendScore\TwitterBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Guzzle\Http\Client;

class DefaultController extends Controller
{
    protected $consumerKey = 'JMTgnzOZfYCPOPFKbpMuVg';
    protected $consumerSecret = 'U96c576TLtVBY9CB4EkZR7fNvBot0a9jv85td3mh2c';

    protected $authToken = '5908302-2asxFENI8zscp3zTzACkQGp4LF3XwEJW2z0umYvIMS';
    protected $authSecret = 'Rca4RNY9hChJ4d1v8BMJeyRoi3uxnlY6vLstCEq18';

    protected $redirectUri = 'http://127.0.0.1:8000/twitter/callback';

    protected $client;
	
    protected $code = '';

    public function __construct()
    {
        $this->client = new Client('https://api.twitter.com');
    }
    
    /**
     * @Route("/")
     * @Template()
     */
    public function indexAction()
    {
        if($this->authToken && $this->authSecret) {
            
            //Add OAuth Header
            $this->client->addSubscriber(new \Guzzle\Plugin\Oauth\OauthPlugin(array(
                'consumer_key' => $this->consumerKey,
                'consumer_secret' => $this->consumerSecret,
                'token' => $this->authToken,
                'token_secret' => $this->authSecret,
            )));
            
            // API call
            $request = $this->client->get('/1.1/friends/ids.json');
            $query = $request->getQuery();
            $query->set('cursor', '-1');
            $query->set('screen_name', 'gabac');
            $query->set('count', '5000');

            $response = $request->send();

            $body = $response->getBody();
            var_dump(json_decode($body));
            echo $body;
            
    		return array('oauth_token' => '');
		} else {
            $this->client->addSubscriber(new \Guzzle\Plugin\Oauth\OauthPlugin(array(
                'consumer_key' => $this->consumerKey,
                'consumer_secret' => $this->consumerSecret,
                'callback' => $this->redirectUri,
            )));
        
            $request = $this->client->post('oauth/request_token');
        
            $response = $request->send();
        
            $body = $response->getBody();
		
    		$params = array();
		
    		parse_str($response->getBody(), $params);
            
    		return array('oauth_token' => $params['oauth_token']);
		}
    }
    
    /**
     * @Route("/callback")
     */
    public function callbackAction(Request $request)
    {
        $this->authToken = $request->get('oauth_token');
        $verifier = $request->get('oauth_verifier');
        
        $this->client->addSubscriber(new \Guzzle\Plugin\Oauth\OauthPlugin(array(
            'consumer_key' => $this->consumerKey,
            'consumer_secret' => $this->consumerSecret,
            'token' => $this->authToken,
        )));
    
        $request = $this->client->post('oauth/access_token', null, array(
            'oauth_verifier' => $verifier,
        ));
        
        $response = $request->send();
    
        $body = $response->getBody();
    
        $params = array();
    
        parse_str($response->getBody(), $params);

        return new Response($body);
    }
    
}
