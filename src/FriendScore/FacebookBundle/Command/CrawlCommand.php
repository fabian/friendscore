<?php

namespace FriendScore\FacebookBundle\Command;

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
    
    protected function configure()
    {
        $this
            ->setName('facebook:crawl')
            ->setDescription('Index all facebook data')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
	{
	    $container = $this->getContainer();
	    $this->doctrine = $container->get('doctrine');
	    $this->elastica = $container->get('friend_score.facebook_bundle.elastica');
        $this->client = $container->get('friend_score.facebook_bundle.facebook.client');
        
        $users = $this->doctrine
            ->getRepository('FriendScoreFacebookBundle:User')
            ->findAll();
        
        foreach ($users as $user) {
            $accessToken = $user->getAccessToken();
            $userId = $user->getUser()->getId();
            $facebookUserId = $user->getFacebookId();

            $output->writeln("Crawling for User ID $userId");
            
            if ($accessToken) {

                // API call
                //get data of user's friends
                $request = $this->client->get('me');
                $query = $request->getQuery();
                $query->set('access_token', $accessToken);
                $query->set('fields', 'id,friends.fields(id, first_name, last_name)');

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
                
                //crawling
                if(isset($json->friends) && isset($json->friends->data)) {
                    foreach ($json->friends->data as $friend) {
                        
                        // API call
                        //get checkins of users
                        $request = $this->client->get($friend->id);
                        $query = $request->getQuery();
                        $query->set('access_token', $accessToken);
                        $query->set('fields', 'checkins.fields(place, coordinates)');

                        $response = $request->send();
                        $body = $response->getBody();
                        $jsonCheckins = json_decode($body);
                        
                        if (isset($jsonCheckins->checkins) && $jsonCheckins->checkins->data) {
                            foreach($jsonCheckins->checkins->data as $checkin) {

                                $place = $checkin->place;
                                $placeId = $place->id;
                                $location = isset($place->location) ? $place->location : null;
                                $checkinId = $checkin->id;

                                $placeIdFacebook = 'facebook_' . $placeId;

                                $facebook = array(
                                    'id' => $placeIdFacebook,
                                    'place_id' => $placeId,
                                    'name' => $place->name,
                                    'url' => 'http://graph.facebook.com/' . $placeId,
                                );
                                
                                if (isset($location) && isset($location->latitude) && isset($location->longitude)) {
                                    $facebook['location'] = array('lat' => $location->latitude, 'lon' => $location->longitude);
                                }

                                $document = new \Elastica\Document($placeIdFacebook, $facebook);

                                $type->addDocument($document);

                                $facebookCheckin = array(
                                    'user_id' => $userId,
                                    'place_id' => $placeIdFacebook,
                                    'checkin' => $checkinId,
                                    'first_name' => $friend->first_name,
                                    'last_name' => $friend->last_name,
                                );

                                $document = new \Elastica\Document($userId . '_facebook_' . $checkinId, $facebookCheckin);
                                $document->setParent($placeIdFacebook);

                                $visitType->addDocument($document);

                                $output->writeln("Added Check-In from {$friend->first_name} to {$place->name}");    
                            }
                        }
                    }
                }
                
            }
            
        }
	}
}