<?php

namespace FriendScore\FacebookBundle\Tests\Service;

use FriendScore\FacebookBundle\Service\FacebookStorage;

class FacebookStorageTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->security = $this->getMockBuilder('Symfony\Component\Security\Core\SecurityContext')
            ->disableOriginalConstructor()
            ->getMock();
        $this->storage = new FacebookStorage($this->doctrine, $this->security);
    }

    public function testGetCurrentUser()
    {
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $currentUser = $this->getMock('FriendScore\UserBundle\Entity\User');

        $this->security->expects($this->once())
             ->method('getToken')
             ->will($this->returnValue($token));

        $token->expects($this->once())
             ->method('getUser')
             ->will($this->returnValue($currentUser));

        $this->doctrine->expects($this->once())
             ->method('getRepository')
             ->with(
                 $this->equalTo('FriendScoreFacebookBundle:User')
             )
             ->will($this->returnValue($repository));

        $repository->expects($this->once())
             ->method('findOneBy')
             ->with(
                 $this->equalTo(array('user' => $currentUser))
             )
             ->will($this->returnValue(null));

        $user = $this->storage->getUser();

        $this->assertInstanceOf('FriendScore\FacebookBundle\Entity\User', $user);
        $this->assertEquals($currentUser, $user->getUser());
    }
    
    public function testSaveUser()
    {
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $user = $this->getMockBuilder('FriendScore\FacebokBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrine->expects($this->once())
             ->method('getManager')
             ->will($this->returnValue($em));

        $em->expects($this->once())
             ->method('persist')
             ->with(
                 $this->equalTo($user)
             );

        $em->expects($this->at(0))
             ->method('flush');

        $this->storage->saveUser($user);
    }
}
