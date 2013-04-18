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

class DefaultController extends Controller
{
    protected $clientId = 'YDFCQSZDK1PEJFD4J4HGBSTS04OQZBPYCR1GPOVAXA3WTYGX';
    protected $clientSecret = 'M33CTMOOFY0CO1JKDGXJZKITVGQFPFEQLLDJIJ4J4UETRY4F';

    protected $version = '20130415';

    protected $redirectUri = 'http://localhost:8000/foursquare/callback';

    protected $elastica;
    protected $client;

    protected $code = '';
    protected $accessToken = '';

    /**
     * @InjectParams({
     *     "elastica" = @Inject("friend_score_foursquare.elastica"),
     * })
     */
    public function __construct($elastica)
    {
        $this->elastica = $elastica;
        $this->client = new Client('https://api.foursquare.com');
    }

    /**
     * @Route("/")
     * @Template()
     */
    public function indexAction()
    {
        $accessToken = 'L0SYAFHXQQ31QIC3EGQT4D2X11WJQMIDAIUO3WF2DI32XOQY';
        $userId = '52185640';

        if ($accessToken) {

            // API call
            $request = $this->client->get('v2/users/241175');
            $query = $request->getQuery();
            $query->set('oauth_token', $accessToken);
            $query->set('v', $this->version);
            $response = $request->send();

            $body = $response->getBody();
            //var_dump(json_decode($body));
            //echo $body;

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
            $json = json_decode($body);

            $index = $this->elastica->getIndex('friendscore');
            if (!$index->exists()) {
                $index->create();
            }

            $type = $index->getType('foursquare_place');

            $mapping = \Elastica\Type\Mapping::create(array(
                'location' => array('type' => 'geo_point'),
            ));
            $type->setMapping($mapping);

            $visitType = $index->getType('foursquare_visit');
            
            $mapping = new \Elastica\Type\Mapping();
            $mapping->setParam('_parent', array('type' => 'foursquare_place'));
            $visitType->setMapping($mapping);

            foreach ($json->response->recent as $checkin) {

                $venue = $checkin->venue;
                $venueId = $venue->id;
                $location = $venue->location;

                $foursquare = array(
                    'user' => $userId,
                    'venue' => $venueId,
                    'name' => $venue->name,
                    'location'=> array('lat' => $location->lat, 'lon' => $location->lng),
                    'url' => $venue->canonicalUrl
                );

                $id = $userId . '/' . $venueId;
                $document = new \Elastica\Document($id, $foursquare);

                $type->addDocument($document);

                $visit = $checkin->user;
                $visitId = $visit->id;
                $photo = $visit->photo;
                $size = '100x100';

                $foursquareVisit = array(
                    'user' => $visitId,
                    'first_name' => $visit->firstName,
                    'photo' => $photo->prefix . $size . $photo->suffix,
                );

                if (isset($visit->lastName)) {
                    $foursquareVisit['last_name'] = $visit->lastName;
                }

                $document = new \Elastica\Document($id . '/' . $visitId, $foursquareVisit);
                $document->setParent($id);

                $visitType->addDocument($document);
            }

            var_dump($json);
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
