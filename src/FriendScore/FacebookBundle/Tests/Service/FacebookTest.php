<?php

namespace FriendScore\FacebookBundle\Tests\Service;

use FriendScore\FacebookBundle\Service\Facebook;

class FacebookTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->client = $this->getMockBuilder('Guzzle\Http\Client')
            ->disableOriginalConstructor()
            ->getMock();
        $this->facebook = new Facebook($this->client, '123', 'ABC');
    }

    public function testGetClientId()
    {
        $this->assertEquals('123', $this->facebook->getClientId());
    }

    public function testGetCurrentUser()
    {
        $request = $this->getMockBuilder('Guzzle\Http\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $query = $this->getMockBuilder('Guzzle\Http\QueryString')
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $this->client->expects($this->once())
             ->method('get')
             ->with(
                 $this->equalTo('me')
             )
             ->will($this->returnValue($request));

        $request->expects($this->once())
             ->method('getQuery')
             ->will($this->returnValue($query));

        $query->expects($this->at(0))
             ->method('set')
             ->with(
                 $this->equalTo('access_token'),
                 $this->equalTo('ABC123')
             );

        $request->expects($this->once())
             ->method('send')
             ->will($this->returnValue($response));

        $response->expects($this->once())
             ->method('getBody')
             ->will($this->returnValue('{"id":"728916713"}'));
                 
        $this->facebook->setAccessToken('ABC123');

        $user = new \stdClass();
        $user->id = '728916713';
        $this->assertEquals($user, $this->facebook->getCurrentUser());
    }
    
    public function testGenerateAccessToken()
    {
        $request = $this->getMockBuilder('Guzzle\Http\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $query = $this->getMockBuilder('Guzzle\Http\QueryString')
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

                 
        $this->client->expects($this->once())
          ->method('get')
          ->with(
              $this->equalTo('oauth/access_token')
          )
          ->will($this->returnValue($request));

        $request->expects($this->once())
          ->method('getQuery')
          ->will($this->returnValue($query));

        $query->expects($this->at(0))
            ->method('set')
            ->with(
              $this->equalTo('client_id'),
              $this->equalTo('123')
        );
        
        $query->expects($this->at(1))
            ->method('set')
            ->with(
                $this->equalTo('client_secret'),
                $this->equalTo('ABC')
        );
        
        $query->expects($this->at(2))
            ->method('set')
            ->with(
                $this->equalTo('redirect_uri'),
                $this->equalTo('http://example.org')
        );
         
        $query->expects($this->at(3))
            ->method('set')
            ->with(
                $this->equalTo('code'),
                $this->equalTo('XYZ')
        );    

        $request->expects($this->once())
             ->method('send')
             ->will($this->returnValue($response));

        $returnValue = array('access_token'=>'ABC123');
        $response->expects($this->once())
             ->method('getBody')
             ->will($this->returnValue(http_build_query($returnValue)));
        
        $this->assertEquals('ABC123', $this->facebook->generateAccessToken('XYZ', 'http://example.org'));
    }
}
