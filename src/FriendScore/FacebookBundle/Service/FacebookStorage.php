<?php

namespace FriendScore\FacebookBundle\Service;

use JMS\DiExtraBundle\Annotation\Service;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;

use FriendScore\FacebookBundle\Entity\User;

/**
 * @Service
 */
class FacebookStorage
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
            ->getRepository('FriendScoreFacebookBundle:User')
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
