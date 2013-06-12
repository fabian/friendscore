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
            $userId = $user->getFacebookId();

            $output->writeln("Crawling for User ID $userId");
            
            if ($accessToken) {

                // API call
                //get data of user's friends
                $request = $this->client->get($userId);
                $query = $request->getQuery();
                $query->set('access_token', $accessToken);
                $query->set('fields', 'id,name,friends.fields(first_name,last_name,checkins.fields(coordinates,place))');
                
                $response = $request->send();
                $body = $response->getBody();
                $json = json_decode($body);
//                var_dump($json);
                
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
                
                foreach ($json->friends->data as $friend) {
                    if($friend->checkins) {
                        
                        $userId = $friend->id;
                        
                        foreach($friend->checkins->data as $checkin) {
                            $place = $checkin;
                            $placeId = $place->id;
                            $location = $place->location;
                            $checkinId = $checkin->id;
                            
                            $placeIdFacebook = 'facebook_' . $venueId;

                            $facebook = array(
                                'id' => $placeIdFacebook,
                                'place_id' => $placeId,
                                'name' => $place->name,
                                'location'=> array('lat' => $location->latitude, 'lon' => $location->longitude)
                            );

                            $document = new \Elastica\Document($placeIdFacebook, $faccebook);

                            $type->addDocument($document);

                            $facebookCheckin = array(
                                'user' => $userId,
                                'checkin' => $checkinId,
                                'first_name' => $friend->first_name,
                                'first_name' => $friend->last_name,
                            );

                            $document = new \Elastica\Document($userId . '/' . $checkinId);
                            $document->setParent($placeId);

                            $placeType->addDocument($document);

                            $output->writeln("Added Check-In from {$friend->first_name} to {$place->name}");    
                        }
                       
                    }
                }
                
            }
            
        }
	}
}