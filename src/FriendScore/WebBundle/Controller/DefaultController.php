<?php

namespace FriendScore\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;

/**
 * Web Controller
 */
class DefaultController
{
    protected $doctrine;
    protected $security;
    protected $router;
    protected $elastica;

    /**
     * @InjectParams({
     *     "doctrine" = @Inject("doctrine"),
     *     "security" = @Inject("security.context"),
     *     "router" = @Inject("router"),
     *     "elastica" = @Inject("friend_score.foursquare_bundle.elastica"),
     * })
     */
    public function __construct($doctrine, $security, $router, $elastica)
    {
        $this->doctrine = $doctrine;
        $this->security = $security;
        $this->router = $router;
        $this->elastica = $elastica;
    }

    /**
     * @Route("/")
     * @Template()
     */
    public function indexAction()
    {
        $index = $this->elastica->getIndex('friendscore');

        $places = array();
        $visits = array();

        if ($index->exists()) {

            // search
            $user = $this->security->getToken()->getUser();
            if ($user) {

                $userId = $user->getId();
                $userQuery = new \Elastica\Query\Term(array('user_id' => $userId));

                // Search on the index.
                $hasChildQuery = new \Elastica\Query\HasChild($userQuery, 'visit');
                $query = new \Elastica\Query();
                $query->setQuery($hasChildQuery);
                $query->setSize(900);
                $resultSet = $index->search($query);
                //var_dump($resultSet->getResponse());

                foreach ($resultSet->getResults() as $result) {
                    $places[] = $result->getData();
                }

                //Search visits
                $query = new \Elastica\Query();
                $query->setQuery($userQuery);
                $query->setSort(array('last_checkin' => 'desc'));
                $query->setSize(5);
                $resultSet = $index->getType('visit')->search($query);
                //var_dump($resultSet->getResponse());exit;

                foreach ($resultSet->getResults() as $result) {
                    $visits[] = $result->getData();
                }
            }
        }

        return array('places' => $places, 'visits' => $visits);
    }

    /**
     * @Route("/search")
     * @Template()
     */
    public function searchAction(Request $request)
    {
        $index = $this->elastica->getIndex('friendscore');

        $q = $request->get('q');

        $user = $this->security->getToken()->getUser();

        $userId = $user->getId();
        $userQuery = new \Elastica\Query\Term(array('user_id' => $userId));
        $hasChildQuery = new \Elastica\Query\HasChild($userQuery, 'visit');
        $stringQuery = new \Elastica\Query\QueryString('*' . $q . '*');

        $boolQuery = new \Elastica\Query\Bool();
        $boolQuery->addMust($hasChildQuery);
        $boolQuery->addMust($stringQuery);

        $query = new \Elastica\Query();
        $query->setQuery($boolQuery);
        $query->setSize(5);
        $resultSet = $index->getType('place')->search($query);

        $places = array();
        foreach ($resultSet->getResults() as $result) {
            $places[] = $result->getData();
        }

        return array('places' => $places, 'q' => $q);
    }

    /**
     * @Route("/places/{id}")
     * @Template()
     */
    public function placeAction($id)
    {
        $index = $this->elastica->getIndex('friendscore');

        $place = $index->getType('place')->getDocument($id);

        $visitors = array();
        $user = $this->security->getToken()->getUser();
        if ($user) {

            $userId = $user->getId();
            $userQuery = new \Elastica\Query\Term(array('user_id' => $userId));
            $placeQuery = new \Elastica\Query\Term(array('place_id' => $id));

            $boolQuery = new \Elastica\Query\Bool();
            $boolQuery->addMust($userQuery);
            $boolQuery->addMust($placeQuery);

            $query = new \Elastica\Query();
            $query->setQuery($boolQuery);
            $query->setSort(array('last_checkin' => 'desc'));
            $query->setSize(5);
            $resultSet = $index->getType('visit')->search($query);

            foreach ($resultSet->getResults() as $result) {
                $visitors[] = $result->getData();
            }
        }
        
        //$place->friendscore = '0.83';
        $place->friendscore = $this->calcualteFriendScore($user, $place->name);
        
        return array('place' => $place, 'visitors' => $visitors);
    }
    
    protected function calcualteFriendScore($user, $placeName) {
        $index = $this->elastica->getIndex('friendscore');
        
        $userId = $user->getId();
        
        $userQuery = new \Elastica\Query\Term(array('user_id' => $userId));
        $hasChildQuery = new \Elastica\Query\HasChild($userQuery, 'visit');
        $stringQuery = new \Elastica\Query\QueryString('*' . $placeName . '*');

        $boolQuery = new \Elastica\Query\Bool();
        $boolQuery->addMust($hasChildQuery);
        $boolQuery->addMust($stringQuery);

        $query = new \Elastica\Query();
        $query->setQuery($boolQuery);
        $query->setSize(5);
        $resultSet = $index->getType('place')->search($query);

        $places = array();
        
        $friendsCheckins = array();
        
        foreach ($resultSet->getResults() as $result) {
            $placeId = $result->getData()['id'] ;
            
            $place = $index->getType('place')->getDocument($placeId);

            $visitors = array();
 
            $userForVisitQuery = new \Elastica\Query\Term(array('user_id' => $userId));
            $placeForVisitQuery = new \Elastica\Query\Term(array('place_id' => $placeId));

            $boolForVisitQuery = new \Elastica\Query\Bool();
            $boolForVisitQuery->addMust($userForVisitQuery);
            $boolForVisitQuery->addMust($placeForVisitQuery);

            $visitQuery = new \Elastica\Query();
            $visitQuery->setQuery($boolForVisitQuery);
            $visitQuery->setSort(array('last_checkin' => 'desc'));
            $visitQuery->setSize(5);
            $visitResultSet = $index->getType('visit')->search($visitQuery);

            foreach ($visitResultSet->getResults() as $visitResult) {

                //change to id
                $friendId = $visitResult->getData()['last_name'];
                
                //check start of string for fb or fq
                $checkinsToAdd = null;
                
                if(true) {
                    if(!isset($friendsCheckins['facebook'])) {
                        $friendsCheckins['facebook'] = array();
                    }
                    
                    $service = 'facebook';
                } else {
                    if(!isset($friendsCheckins['foursquare'])) {
                        $friendsCheckins['foursquare'] = array();
                    }  

                    $service = 'foursquare';
                }
                
                if(isset($friendsCheckins[$service][$friendId])) {
                    $friendsCheckins[$service][$friendId]++;
                } else {
                    $friendsCheckins[$service][$friendId] = 1;
                }
            }
        }
        
        //how to do a constant?
        $baspoint = 1 / (3 * 2) / 2;
        
        $friendscore = 0;

        foreach($friendsCheckins as $service) {
            $serviceScore = 0;

            foreach($service as $friendCheckin) {
                if($friendCheckin > 1) {
                    $serviceScore += $baspoint * 2;
                } else {
                    $serviceScore += $baspoint;
                }
            }
   
            if($serviceScore > 0.5) {
                $serviceScore = 0.5;
            }
            
            $friendscore += $serviceScore;
            $serviceScore = 0;
        }
        
        return $friendscore;
    }
}
