<?php

namespace FriendScore\InstagramBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Guzzle\Http\Client;

class DefaultController extends Controller
{
    protected $clientId = '9f0cca2544ce4f43adfa4122ee3cfab4';
    protected $clientSecret = 'a4ade51db675461b81674313c789c8dc';

    protected $redirectUri = 'http://localhost:8000/instagram/callback';

    protected $client;

    protected $code = '';
    protected $accessToken = '';

    public function __construct()
    {
        $this->client = new Client('https://api.instagram.com');
    }

    /**
     * @Route("/")
     * @Template()
     */
    public function indexAction()
    {
        $accessToken = '1982948.9f0cca2.3db8865868274573b68a41e052a0071b';

        if ($accessToken) {
            
            // API call
            $request = $this->client->get('v1/users/self/feed');
            $query = $request->getQuery();
            $query->set('access_token', $accessToken);
            $query->set('count', 30);
            $response = $request->send();

            $body = $response->getBody();
            var_dump(json_decode($body));
            echo $body;
        }

        return array('client_id' => $this->clientId, 'redirect_uri' => $this->redirectUri);
    }

    /**
     * @Route("/callback")
     */
    public function callbackAction(Request $request)
    {
        $code = $request->get('code');

        $request = $this->client->post('oauth/access_token', null, array (
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri,
            'code' => $code,
        ));
        $response = $request->send();

        $body = $response->getBody();

        return new Response($body);
    }
}
