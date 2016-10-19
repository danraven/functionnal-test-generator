<?php

namespace huitiemesens\FunctionalTestGeneratorBundle\Tests;

use Symfony\Component\HttpKernel\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use huitiemesens\FunctionalTestGeneratorBundle\Tests\FunctionalTestCaseInterface;

abstract class AbstractFunctionalTestCase extends WebTestCase implements FunctionalTestCaseInterface
{
    /** @var Client */
    protected $client;

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return self::$kernel->getContainer();
    }

    public function setUp()
    {
        $this->client = self::createClient();
    }
}
