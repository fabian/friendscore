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

class DefaultController extends Controller
{
    protected $clientId = 'YDFCQSZDK1PEJFD4J4HGBSTS04OQZBPYCR1GPOVAXA3WTYGX';
    protected $clientSecret = 'M33CTMOOFY0CO1JKDGXJZKITVGQFPFEQLLDJIJ4J4UETRY4F';

    protected $version = '20130415';

    protected $redirectUri;

    protected $doctrine;
    protected $security;
    protected $router;
    protected $elastica;
    protected $client;

    protected $code = '';
    protected $accessToken = '';

    /**
     * @InjectParams({
     *     "doctrine" = @Inject("doctrine"),
     *     "security" = @Inject("security.context"),
     *     "router" = @Inject("router"),
     *     "elastica" = @Inject("friend_score_foursquare.elastica"),
     * })
     */
    public function __construct($doctrine, $security, $router, $elastica)
    {
        $this->doctrine = $doctrine;
        $this->security = $security;
        $this->router = $router;
        $this->elastica = $elastica;

        $this->redirectUri = $this->router->generate('friendscore_foursquare_default_callback', array(), true);

        $this->client = new Client('https://api.foursquare.com');
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
        $userId = '52185640';
        $query = new \Elastica\Query\Term(array('user' => $userId));

        //Search on the index.
        $resultSet = $index->search(new \Elastica\Query\HasChild($query, 'foursquare_visit'));
        //var_dump($resultSet->getResponse());

        foreach ($resultSet->getResults() as $result) {
            var_dump($result->getData());
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
        $json = json_decode($body);
        
        $accessToken = $json->access_token;

        $request = $this->client->get('v2/users/self');
        $query = $request->getQuery();
        $query->set('oauth_token', $accessToken);
        $query->set('v', $this->version);
        $response = $request->send();

        $body = $response->getBody();
        $json = json_decode($body);

        $foursquareId = $json->response->user->id;

        $user = new User($this->security->getToken()->getUser());
        $user->setFoursquareId($foursquareId);
        $user->setAccessToken($accessToken);

        $em = $this->doctrine->getManager();
        $em->persist($user);
        $em->flush();

        return new Response($body);
    }
}
