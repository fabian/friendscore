<?php

namespace FriendScore\FoursquareBundle\Tests\Service;

use FriendScore\FoursquareBundle\Service\FoursquareAuth;

class FoursquareAuthTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->client = $this->getMockBuilder('Guzzle\Http\Client')
            ->disableOriginalConstructor()
            ->getMock();
        $this->foursquare = new FoursquareAuth($this->client, '123', 'ABC');
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
             ->method('post')
             ->with(
                 $this->equalTo('oauth2/access_token'),
                 $this->equalTo(null),
                 $this->equalTo(array(
                     'client_id' => '123',
                     'client_secret' => 'ABC',
                     'grant_type' => 'authorization_code',
                     'redirect_uri' => 'http://example.org',
                     'code' => 'XYZ',
                 ))
             )
             ->will($this->returnValue($request));

        $request->expects($this->once())
             ->method('send')
             ->will($this->returnValue($response));

        $response->expects($this->once())
             ->method('getBody')
             ->will($this->returnValue('{"access_token":"ABC123"}'));

        $this->assertEquals('ABC123', $this->foursquare->generateAccessToken('XYZ', 'http://example.org'));
    }
}
