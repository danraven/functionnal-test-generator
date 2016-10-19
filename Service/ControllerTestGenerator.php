<?php

namespace huitiemesens\FunctionalTestGeneratorBundle\Service;

use Symfony\Component\Routing\Route;

class ControllerTestGenerator
{
    const BASE_TEST_CLASS = \PHPUnit_Framework_TestCase::class;

    /** @var string */
    protected $extensionNamespace;

    /** @var string */
    protected $extensionClass;

    /** @var string */
    protected $testsDir;

    public function __construct($extensionClass, $testsDir)
    {
        if (!class_exists($extensionClass)) {
            throw new \RuntimeException("Class '$extensionClass' not found or could not be autoloaded.");
        }
        if ($extensionClass !== self::BASE_TEST_CLASS && !is_subclass_of($extensionClass, self::BASE_TEST_CLASS)) {
            throw new \InvalidArgumentException(
                "Class '$extensionClass' should be an extension of '" . self::BASE_TEST_CLASS. "'"
            );
        }

        $namespace = explode('\\', $extensionClass);
        $this->extensionClass = array_pop($namespace);
        $this->extensionNamespace = count($namespace) ? implode('\\', $namespace) : '\\';

        $this->testsDir = $testsDir;
    }

    public function generateTestForRoute(Route $route)
    {

    }

}
