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
    protected $friendScore;
    
    /**
     * @InjectParams({
     *     "doctrine" = @Inject("doctrine"),
     *     "security" = @Inject("security.context"),
     *     "router" = @Inject("router"),
     *     "elastica" = @Inject("friend_score.foursquare_bundle.elastica"),
     *     "friendScore" = @Inject("friend_score.web_bundle.service.friend_score"),
     * })
     */
    public function __construct($doctrine, $security, $router, $elastica, $friendScore)
    {
        $this->doctrine = $doctrine;
        $this->security = $security;
        $this->router = $router;
        $this->elastica = $elastica;
        $this->friendScore = $friendScore;
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

            //$place->friendscore = '0.83';
            $friendsCheckins = $this->friendScore->getFriendsCheckins($user, $place);

            foreach ($friendsCheckins as $checkin) {
                $visitors[$checkin['first_name']] = $checkin;
            }

            $place->friendscore = $this->friendScore->calcualteFriendScore($friendsCheckins);
        }

        return array('place' => $place, 'visitors' => $visitors);
    }
}
