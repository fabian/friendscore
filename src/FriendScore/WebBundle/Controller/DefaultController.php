<?php

namespace FriendScore\WebBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;

/**
 * Web Controller
 */
class DefaultController extends Controller
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
     * @Route("/places/{id}")
     * @Template()
     */
    public function placeAction($id)
    {
        $index = $this->elastica->getIndex('friendscore');
        if (!$index->exists()) {
            $index->create();
        }

        $place = $index->getType('place')->getDocument($id);

        return array('place' => $place);
    }
}
