<?php

namespace huitiemesens\FunctionalTestGeneratorBundle\Tests;

use Symfony\Component\HttpKernel\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

interface FunctionalTestCaseInterface
{
    /**
     * @return Client
     */
    public function getClient();

    /**
     * @return ContainerInterface
     */
    public function getContainer();
}
