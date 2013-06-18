<?php

namespace FriendScore\FoursquareBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExploreCommand extends ContainerAwareCommand
{
    protected $doctrine;
    protected $elastica;
    protected $client;

    protected $version = '20130415';

    protected function configure()
    {
        $this
            ->setName('foursquare:explore')
            ->setDescription('Explore more Foursquare data')
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

        $index = $this->elastica->getIndex('friendscore');
        if (!$index->exists()) {
            $index->create();
        }

        foreach ($users as $user) {

            $userId = $user->getUser()->getId();
            $accessToken = $user->getAccessToken();
            $foursquareId = $user->getFoursquareId();

            $output->writeln("Crawling for User ID $userId and Foursquare ID $foursquareId");

            if ($accessToken) {

                $userQuery = new \Elastica\Query\Term(array('user_id' => $userId));
                $hasChildQuery = new \Elastica\Query\HasChild($userQuery, 'visit');

                $query = new \Elastica\Query();
                $query->setQuery($hasChildQuery);
                $resultSet = $index->getType('place')->search($query);

                foreach ($resultSet->getResults() as $result) {

                    $placeData = $result->getData();

                    // API call
                    $request = $this->client->get('v2/venues/explore');
                    $query = $request->getQuery();
                    $query->set('oauth_token', $accessToken);
                    $query->set('v', $this->version);
                    $query->set('ll', implode(',', $placeData['location']));
                    $query->set('friendVisits', 'visited');
                    $response = $request->send();

                    $body = $response->getBody();
                    $json = json_decode($body);

                    $type = $index->getType('place');

                    $mapping = \Elastica\Type\Mapping::create(array(
                        'location' => array('type' => 'geo_point'),
                    ));
                    $type->setMapping($mapping);

                    $visitType = $index->getType('visit');

                    $mapping = new \Elastica\Type\Mapping();
                    $mapping->setParent('place');
                    $visitType->setMapping($mapping);

                    foreach ($json->response->groups[0]->items as $item) {

                        $venue = $item->venue;

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

                            foreach ($venue->friendVisits->items as $checkin) {

                                $visitor = $checkin->user;

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

                                $foursquareVisit = array(
                                    'id' => $visitId,
                                    'user_id' => $userId,
                                    'visitor_id' => $visitorId,
                                    'place_id' => $placeId,
                                    'place_name' => $venueName,
                                    'first_name' => $visitor->firstName,
                                    'last_name' => isset($visitor->lastName) ? $visitor->lastName : '',
                                    'photo' => $photo->prefix . $size . $photo->suffix,
                                    'checkins' => array_unique($checkins),
                                    'type' => 'foursquare',
                                );

                                if (isset($checkin->visitedCount)) {
                                    $foursquareVisit['checkin_count'] = $checkin->visitedCount;
                                }
                                if (isset($visit['last_checkin'])) {
                                    $foursquareVisit['last_checkin'] = $visit['last_checkin'];
                                }

                                $document = new \Elastica\Document($visitId, $foursquareVisit);
                                $document->setParent($placeId);

                                $visitType->addDocument($document);

                                $output->writeln("Added Check-In from {$visitor->firstName} to {$venue->name}");
                            }

                        } else {
                            $output->writeln("Skipped Check-In from {$visitor->firstName} to private home {$venue->name}");
                        }
                    }
                }
            }
        }
    }
}
