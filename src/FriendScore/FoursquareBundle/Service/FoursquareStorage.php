<?php

namespace FriendScore\FoursquareBundle\Service;

use JMS\DiExtraBundle\Annotation\Service;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;

use FriendScore\FoursquareBundle\Entity\User;

/**
 * @Service
 */
class FoursquareStorage
{
    protected $doctrine;
    protected $security;

    /**
     * @InjectParams({
     *     "doctrine" = @Inject("doctrine"),
     *     "security" = @Inject("security.context"),
     * })
     */
    public function __construct($doctrine, $security)
    {
        $this->doctrine = $doctrine;
        $this->security = $security;
    }

    public function getUser()
    {
        $currentUser = $this->security->getToken()->getUser();

        $user = $this->doctrine
            ->getRepository('FriendScoreFoursquareBundle:User')
            ->findOneBy(
                array('user' => $currentUser)
            );

        if (!$user) {
            $user = new User($currentUser);
        }

        return $user;
    }
    
    public function saveUser($user)
    {
        $em = $this->doctrine->getManager();
        $em->persist($user);
        $em->flush();
    }
}
