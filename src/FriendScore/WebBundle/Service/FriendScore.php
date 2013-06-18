<?php

namespace FriendScore\WebBundle\Service;

use JMS\DiExtraBundle\Annotation\Service;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;

use FriendScore\FoursquareBundle\Entity\User;

/**
 * @Service
 */
class FriendScore
{
    protected $elastica;

    /**
     * @InjectParams({
     *     "elastica" = @Inject("friend_score.foursquare_bundle.elastica"),
     * })
     */
    public function __construct($elastica)
    {
        $this->elastica = $elastica;
    }

    public function getFriendsCheckins($user, $place)
    {
        $index = $this->elastica->getIndex('friendscore');

        $friendsCheckins = array();

        $userId = $user->getId();

        $userQuery = new \Elastica\Query\Term(array('user_id' => $userId));
        $hasChildQuery = new \Elastica\Query\HasChild($userQuery, 'visit');

        $mltQuery = new \Elastica\Query\MoreLikeThis();
        $mltQuery->setFields(array('name'));
        $mltQuery->setLikeText($place->name);
        $mltQuery->setMinTermFrequency(0);
        $mltQuery->setMinDocFrequency(0);

        $boolQuery = new \Elastica\Query\Bool();
        $boolQuery->addMust($hasChildQuery);
        $boolQuery->addMust($mltQuery);

        $geoFilter = new \Elastica\Filter\GeoDistance('location', $place->location, '500m');

        $query = new \Elastica\Query();
        $query->setQuery($boolQuery);
        $query->setFilter($geoFilter);
        $resultSet = $index->getType('place')->search($query);

        $places = array();

        foreach ($resultSet->getResults() as $result) {

            $placeData = $result->getData();
            $placeId = $placeData['id'] ;

            $place = $index->getType('place')->getDocument($placeId);

            $visitors = array();

            $userForVisitQuery = new \Elastica\Query\Term(array('user_id' => $userId));
            $placeForVisitQuery = new \Elastica\Query\Term(array('place_id' => $placeId));

            $boolForVisitQuery = new \Elastica\Query\Bool();
            $boolForVisitQuery->addMust($userForVisitQuery);
            $boolForVisitQuery->addMust($placeForVisitQuery);

            $visitQuery = new \Elastica\Query();
            $visitQuery->setQuery($boolForVisitQuery);
            $visitResultSet = $index->getType('visit')->search($visitQuery);

            foreach ($visitResultSet->getResults() as $visitResult) {

                $friendsCheckins[] = $visitResult->getData();
            }
        }

        return $friendsCheckins;
    }

    public function calcualteFriendScore($checkins) {

        $friendsCheckins = array();
        foreach ($checkins as $checkin) {

            $friendId = $checkin['visitor_id'];

            //check start of string for fb or fq
            $checkinsToAdd = strstr($checkin['place_id'], '_', true);;

            if(!isset($friendsCheckins[$checkinsToAdd])) {
                    $friendsCheckins[$checkinsToAdd] = array();
            }

            $friendsCheckins[$checkinsToAdd][$friendId] = count($checkin['checkins']);
        }

        //use constant?
        $basepoint = 1 / (3 * 2) / 2;

        $friendscore = 0;

        foreach($friendsCheckins as $service) {
            $serviceScore = 0;

            foreach($service as $friendCheckin) {
                if($friendCheckin > 1) {
                    if($friendCheckin > 3) {
                        $friendCheckin = 3;
                    }
                    $serviceScore += $friendCheckin * $basepoint * 2;
                } else {
                    $serviceScore += $basepoint;
                }
            }

            if($serviceScore > 0.5) {
                $serviceScore = 0.5;
            }

            $friendscore += $serviceScore;
            $serviceScore = 0;
        }

        return round($friendscore, 2);
    }
}
