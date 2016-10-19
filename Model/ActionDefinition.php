<?php

namespace huitiemesens\FunctionalTestGeneratorBundle\Model;

use Symfony\Component\Routing\Route;

class ActionDefinition
{
    /** @var string */
    protected $actionName;

    /** @var Route */
    protected $route;

    /**
     * @param Route $route
     */
    public function __construct(Route $route)
    {
        $this->route = $route;

        $actionId = $route->getDefault('_controller');
        $this->actionName = substr($actionId, strrpos(':', $actionId) + 1);
    }

    /**
     * @return Route
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @return string
     */
    public function getActionName()
    {
        return $this->actionName;
    }

}
