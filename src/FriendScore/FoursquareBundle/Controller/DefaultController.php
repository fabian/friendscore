<?php

namespace FriendScore\FoursquareBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Guzzle\Http\Client;

class DefaultController extends Controller
{
    protected $clientId = 'YDFCQSZDK1PEJFD4J4HGBSTS04OQZBPYCR1GPOVAXA3WTYGX';
    protected $clientSecret = 'M33CTMOOFY0CO1JKDGXJZKITVGQFPFEQLLDJIJ4J4UETRY4F';

    protected $version = '20130415';

    protected $redirectUri = 'http://localhost:8000/foursquare/callback';

    protected $client;

    protected $code = '';
    protected $accessToken = '';

    public function __construct()
    {
        $this->client = new Client('https://api.foursquare.com');
    }

    /**
     * @Route("/")
     * @Template()
     */
    public function indexAction()
    {
        $accessToken = 'L0SYAFHXQQ31QIC3EGQT4D2X11WJQMIDAIUO3WF2DI32XOQY';

        if ($accessToken) {

            // API call
            $request = $this->client->get('v2/venues/explore');
            $query = $request->getQuery();
            $query->set('oauth_token', $accessToken);
            $query->set('v', $this->version);
            $query->set('near', 'Baden, Switzerland');
            $query->set('friendVisits', 'visited');
            $response = $request->send();

            $body = $response->getBody();
            var_dump(json_decode($body));
            echo $body;

            // API call
            $request = $this->client->get('v2/venues/4af57ab3f964a52054f921e3');
            $query = $request->getQuery();
            $query->set('oauth_token', $accessToken);
            $query->set('v', $this->version);
            $response = $request->send();
            
            $body = $response->getBody();
            var_dump(json_decode($body));
            echo $body;

            // API call
            $request = $this->client->get('v2/checkins/recent');
            $query = $request->getQuery();
            $query->set('oauth_token', $accessToken);
            $query->set('v', $this->version);
            $query->set('limit', 100);
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

        $client = new Client('https://foursquare.com');
        $request = $client->post('oauth2/access_token', null, array (
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
