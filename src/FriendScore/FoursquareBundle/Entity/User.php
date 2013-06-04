<?php 

namespace FriendScore\FoursquareBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="foursquare_user")
 */
class User
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="FriendScore\UserBundle\Entity\User")
     */
    private $user;

    /**
     * @ORM\Column(name="foursquare_id", type="string", length=255)
     */
    protected $foursquareId;

    /**
     * @ORM\Column(name="access_token", type="string", length=255)
     */
    protected $accessToken;

    public function __construct(\FriendScore\UserBundle\Entity\User $user)
    {
        $this->user = $user;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set foursquareId
     *
     * @param string $foursquareId
     * @return User
     */
    public function setFoursquareId($foursquareId)
    {
        $this->foursquareId = $foursquareId;
    
        return $this;
    }

    /**
     * Get foursquareId
     *
     * @return string 
     */
    public function getFoursquareId()
    {
        return $this->foursquareId;
    }

    /**
     * Set accessToken
     *
     * @param string $accessToken
     * @return User
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    
        return $this;
    }

    /**
     * Get accessToken
     *
     * @return string 
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * Get user
     *
     * @return \FriendScore\UserBundle\Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }
}