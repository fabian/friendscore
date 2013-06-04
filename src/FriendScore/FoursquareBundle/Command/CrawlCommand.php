<?php

namespace FriendScore\FoursquareBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CrawlCommand extends ContainerAwareCommand
{
    protected $doctrine;
    protected $elastica;
    protected $client;

    protected $version = '20130415';

    protected function configure()
    {
        $this
            ->setName('foursquare:crawl')
            ->setDescription('Index all Foursquare data')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $this->doctrine = $container->get('doctrine');
        $this->elastica = $container->get('friend_score_foursquare.elastica');
        $this->client = new \Guzzle\Http\Client('https://api.foursquare.com');

        $users = $this->doctrine
            ->getRepository('FriendScoreFoursquareBundle:User')
            ->findAll();

        foreach ($users as $user) {

            $accessToken = $user->getAccessToken();
            $userId = $user->getFoursquareId();

            if ($accessToken) {

                // API call
                $request = $this->client->get('v2/users/self');
                $query = $request->getQuery();
                $query->set('oauth_token', $accessToken);
                $query->set('v', $this->version);
                $response = $request->send();
    
                //$body = $response->getBody();
                //var_dump(json_decode($body));
                //echo $body;
    
                // API call
                $request = $this->client->get('v2/users/241175');
                $query = $request->getQuery();
                $query->set('oauth_token', $accessToken);
                $query->set('v', $this->version);
                $response = $request->send();
    
                //$body = $response->getBody();
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
                //var_dump(json_decode($body));
                //echo $body;
    
                // API call
                $request = $this->client->get('v2/venues/4af57ab3f964a52054f921e3');
                $query = $request->getQuery();
                $query->set('oauth_token', $accessToken);
                $query->set('v', $this->version);
                $response = $request->send();
                
                $body = $response->getBody();
                //var_dump(json_decode($body));
                //echo $body;
    
                // API call
                $request = $this->client->get('v2/checkins/recent');
                $query = $request->getQuery();
                $query->set('oauth_token', $accessToken);
                $query->set('v', $this->version);
                $query->set('limit', 100);
                $response = $request->send();
    
                $body = $response->getBody();
                $json = json_decode($body);
                //var_dump($json);
                //echo $body;
    
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
                        'venue' => $venueId,
                        'name' => $venue->name,
                        'location'=> array('lat' => $location->lat, 'lon' => $location->lng),
                        'url' => $venue->canonicalUrl
                    );
    
                    $document = new \Elastica\Document($venueId, $foursquare);
    
                    $type->addDocument($document);
    
                    $visit = $checkin->user;
                    $visitId = $visit->id;
                    $photo = $visit->photo;
                    $size = '100x100';
    
                    $foursquareVisit = array(
                        'user' => $userId,
                        'visit' => $visitId,
                        'first_name' => $visit->firstName,
                        'photo' => $photo->prefix . $size . $photo->suffix,
                    );
    
                    if (isset($visit->lastName)) {
                        $foursquareVisit['last_name'] = $visit->lastName;
                    }
    
                    $document = new \Elastica\Document($userId . '/' . $visitId, $foursquareVisit);
                    $document->setParent($venueId);
    
                    $visitType->addDocument($document);
                }
            }
        }
    }
}
