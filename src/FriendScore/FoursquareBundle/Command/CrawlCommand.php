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
        $this->elastica = $container->get('friend_score.foursquare_bundle.elastica');
        $this->client = new \Guzzle\Http\Client('https://api.foursquare.com');

        $users = $this->doctrine
            ->getRepository('FriendScoreFoursquareBundle:User')
            ->findAll();

        foreach ($users as $user) {

            $userId = $user->getUser()->getId();
            $accessToken = $user->getAccessToken();
            $foursquareId = $user->getFoursquareId();

            $output->writeln("Crawling for User ID $userId and Foursquare ID $foursquareId");

            if ($accessToken) {

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
    
                $type = $index->getType('place');
    
                $mapping = \Elastica\Type\Mapping::create(array(
                    'location' => array('type' => 'geo_point'),
                ));
                $type->setMapping($mapping);
    
                $visitType = $index->getType('visit');
                
                $mapping = new \Elastica\Type\Mapping();
                $mapping->setParent('place');
                $visitType->setMapping($mapping);
    
                foreach ($json->response->recent as $checkin) {

                    $venue = $checkin->venue;
                    $visitor = $checkin->user;

                    // ignore private homes
                    if (!isset($venue->location->isFuzzed)) {

                        $venueId = $venue->id;
                        $venueName = $venue->name;
                        $location = $venue->location;

                        $placeId = 'foursquare_' . $venueId;
                        $foursquare = array(
                            'id' => $placeId,
                            'venue_id' => $venueId,
                            'name' => $venueName,
                            'location'=> array('lat' => $location->lat, 'lon' => $location->lng),
                            'url' => $venue->canonicalUrl,
                            'type' => 'foursquare',
                        );

                        $document = new \Elastica\Document($placeId, $foursquare);

                        $type->addDocument($document);

                        $timestamp = $checkin->createdAt;
                        $lastCheckin = date('c', $timestamp);

                        $visitorId = $visitor->id;
                        $visitId = $foursquareId . '_foursquare_' . $venueId . '_' . $visitorId;
                        $photo = $visitor->photo;
                        $size = '100x100';

                        try {
                            $visit = $index->getType('visit')->getDocument($visitId)->getData();
                        } catch (\Elastica\Exception\NotFoundException $e) {
                            $visit = array();
                        }

                        $checkins = isset($visit['checkins']) ? $visit['checkins'] : array();
                        $checkins[] = $checkin->id;

                        $foursquareVisit = array(
                            'id' => $visitId,
                            'user_id' => $userId,
                            'visitor_id' => $visitorId,
                            'place_id' => $placeId,
                            'place_name' => $venueName,
                            'first_name' => $visitor->firstName,
                            'last_name' => isset($visitor->lastName) ? $visitor->lastName : '',
                            'photo' => $photo->prefix . $size . $photo->suffix,
                            'last_checkin' => $lastCheckin,
                            'checkins' => array_unique($checkins),
                            'type' => 'foursquare',
                        );

                        $document = new \Elastica\Document($visitId, $foursquareVisit);
                        $document->setParent($placeId);

                        $visitType->addDocument($document);

                        $output->writeln("Added Check-In from {$visitor->firstName} to {$venue->name}");

                    } else {
                        $output->writeln("Skipped Check-In from {$visitor->firstName} to private home {$venue->name}");
                    }
                }
            }
        }
    }
}
