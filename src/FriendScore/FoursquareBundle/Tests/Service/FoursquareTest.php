<?php

namespace FriendScore\FoursquareBundle\Tests\Service;

use FriendScore\FoursquareBundle\Service\Foursquare;

class FoursquareTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->client = $this->getMockBuilder('Guzzle\Http\Client')
            ->disableOriginalConstructor()
            ->getMock();
        $this->foursquare = new Foursquare($this->client, '123', 'ABC');
    }

    public function testGetClientId()
    {
        $this->assertEquals('123', $this->foursquare->getClientId());
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
                 $this->equalTo('v2/users/self')
             )
             ->will($this->returnValue($request));

        $request->expects($this->once())
             ->method('getQuery')
             ->will($this->returnValue($query));

        $query->expects($this->at(0))
             ->method('set')
             ->with(
                 $this->equalTo('oauth_token'),
                 $this->equalTo('ABC123')
             );

        $query->expects($this->at(1))
             ->method('set')
             ->with(
                 $this->equalTo('v'),
                 $this->equalTo('20130415')
             );

        $request->expects($this->once())
             ->method('send')
             ->will($this->returnValue($response));

        $response->expects($this->once())
             ->method('getBody')
             ->will($this->returnValue('{"response":{"user":{"id":"123456"}}}'));

        $this->foursquare->setAccessToken('ABC123');

        $user = new \stdClass();
        $user->id = '123456';
        $this->assertEquals($user, $this->foursquare->getCurrentUser());
    }
}
