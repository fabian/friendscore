<?php

namespace FriendScore\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;

/**
 * Web User Controller
 */
class UserController extends Controller
{
    protected $doctrine;
    protected $security;
    protected $router;

    /**
     * @InjectParams({
     *     "doctrine" = @Inject("doctrine"),
     *     "security" = @Inject("security.context"),
     *     "router" = @Inject("router"),
     * })
     */
    public function __construct($doctrine, $security, $router)
    {
        $this->doctrine = $doctrine;
        $this->security = $security;
        $this->router = $router;
    }

    /**
     * @Route("/user")
     * @Template()
     */
    public function indexAction()
    {
        return array();
    }
}
