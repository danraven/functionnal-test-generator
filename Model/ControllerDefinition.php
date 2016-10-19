<?php

namespace huitiemesens\FunctionalTestGeneratorBundle\Model;

use huitiemesens\FunctionalTestGeneratorBundle\Model\ActionDefinition;

class ControllerDefinition implements \IteratorAggregate
{
    /** @var string */
    protected $identifier;

    /** @var string */
    protected $controllerNamespace;

    /** @var string */
    protected $controllerName;

    /** @var ActionDefinition[] */
    protected $actions = [];

    /**
     * @param string $namespace
     * @param string $name
     * @param string $identifier
     */
    public function __construct($namespace, $name, $identifier = null)
    {
        $this->controllerNamespace = $namespace;
        $this->controllerName = $name;
        $this->identifier = $identifier ?: $namespace . '\\' . $name;
    }

    /**
     * @return string
     */
    public function getIdentififer()
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function getControllerNamespace()
    {
        return $this->controllerNamespace;
    }

    /**
     * @return string
     */
    public function getControllerName()
    {
        return $this->controllerName;
    }

    /**
     * @return ActionDefinition[]
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @param ActionDefinition $action
     */
    public function addAction(ActionDefinition $action)
    {
        if (!in_array($action, $this->actions)) {
            $this->actions[] = $action;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->actions);
    }

}
