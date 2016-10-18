<?php

namespace huitiemesens\FunctionalTestGeneratorBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class FunctionalTestGeneratorCommand extends ContainerAwareCommand
{
    protected $em;
    protected $entities = array();
    protected $namespace;

    protected function configure()
    {
        $this
            ->setName('tests:generate')
            ->setDescription('Generate PHPUnit skeletons tests for symfony2 bundles')
            ->setDefinition(array(
                new InputArgument('bundle', InputArgument::REQUIRED, 'Specify which bundle to operate'),
                new InputOption('step', null, InputOption::VALUE_NONE, 'If defined, the generation will ask for each entity generation')
            ))
            ->setHelp(<<<EOT
The <info>tests:generate</info> command generate all Sonata admin files in order to manage all entities included in a defined bundle:
  <info>php app/console tests:generate recetas:myBundle</info>
This interactive will generate all Sonata admin stuff included in myBundle.
EOT
            )
        ;
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->namespace = str_replace(':', '\\', $input->getArgument('bundle'));
        $trimLength = strlen($this->namespace . '\\Controller\\');
        $output->writeln( "Every controller included in {$this->namespace} will be generated");
        $controllers = [];

        foreach ($this->getContainer()->get('router')->getRouteCollection() as $route) {
            $controller = $route->getDefault('_controller');
            if (0 === strpos($controller, $this->namespace)) {
                $controllerName = substr($controller, $trimLength, strrpos($controller, '::') - $trimLength);
                if (!array_key_exists($controllerName, $controllers)) {
                    $controllers[$controllerName] = [];
                }
                $controllers[$controllerName][] = $route;
            }
        }

        $this->generate($controllers, $input, $output);
    }

    public function generate(array $controllers, InputInterface $input, OutputInterface $output) {
        if (!$controllers) {
            return;
        }
        $bundleDir = $this->getContainer()->getParameter('kernel.root_dir') .
            DIRECTORY_SEPARATOR .
            '..' .
            DIRECTORY_SEPARATOR .
            'src' .
            DIRECTORY_SEPARATOR .
            str_replace('\\', DIRECTORY_SEPARATOR, $this->namespace) .
            DIRECTORY_SEPARATOR
        ;

        $setupFilePath = $bundleDir . DIRECTORY_SEPARATOR . 'Tests' . DIRECTORY_SEPARATOR . 'SetUpFunctionalTest.php';
        $testDir = $bundleDir . DIRECTORY_SEPARATOR . 'Tests' . DIRECTORY_SEPARATOR . 'Controller' . DIRECTORY_SEPARATOR;

        if (!file_exists($setupFilePath)){
            $output->writeln("\r\n Generating SetUpFunctionalTest in <info>$setupFilePath</info> ...");
            file_put_contents($setupFilePath, $this->generateSetupFunctionTest());
        }

        $output->writeln( "Writing controller test");
        foreach ($controllers as $name => $routes) {
            $addTest = $this->createControllerTestFile($name);

            if ($input->isInteractive()) {
                $question = new ChoiceQuestion(
                    "Do you confirm generation of <info>$name</info> controller?",
                    ['y' => 'Yes', 'n' => 'No'],
                    'y'
                );
                $choice = $this->getHelper('question')->ask($input, $output, $question);
                if ($choice !== 'y') {
                    continue;
                }
                foreach ($routes as $route) {
                    $fullActionName = $route->getDefault('_controller');
                    $actionName = substr($fullActionName, strrpos($fullActionName, '::') + 2);
                    $output->writeln("\r\n<info>Generating test for route $actionName ...</info>");

                    $this->generateDir($bundleDir, $output);
                    $addTest .= $this->addTestAction($actionName, $route);
                }
                $addTest .= "
}";
                file_put_contents($testDir . $name . "Test.php", $addTest);
            }
        }
    }

    public function generateDir($dir, $output)
    {
        $testPath = $dir . 'Tests';
        $controllerPath = $testPath . DIRECTORY_SEPARATOR . 'Controller';

        if (!is_dir($testPath)) {
            $output->writeln( "\r\n Generating directory..." );
            mkdir($testPath, 0755, true);
        }
        if (!is_dir($controllerPath)) {
            $output->writeln( "\r\n Generating tests controller directory..." );
            mkdir($controllerPath, 0755, true);
        }
    }

    public function createControllerTestFile($name)
    {
        return "
<?php
namespace {$this->namespace}\\Tests\\Controller;

use {$this->namespace}\\Tests\\SetUpFunctionalTest;
/**
 * Generated tests for {$this->namespace}
 */
class " . $name ."Test extends SetUpFunctionalTest
{

    /**
     * Set up test
     */
    public function setUp()
    {
        // setup sqlite database via fixtures
        \$this->setUpClientAndUser();
    }
";
    }

    public function addTestAction($actionName, $route)
    {
        return "
    /**
     * Tests the " . $actionName . " page
     */
    public function test" . $actionName ."()
    {
        //set up the test. Client is : \$this->client;
        \$crawler = \$this->client->request('GET', '" . $route->getPath() . "');
        \$response = \$this->client->getResponse();

        // Test page is available (code 2**)
        \$this->assertTrue(\$this->client->getResponse()->isSuccessful());
    }

";
    }

    public function addFixtures()
    {
        return "
    /**
     * Executes fixtures
     * @param \Doctrine\Common\DataFixtures\Loader \$loader
     */
    protected function executeFixtures(Loader \$loader)
    {
        \$purger = new ORMPurger();
        \$executor = new ORMExecutor(\$this->em, \$purger);
        \$executor->execute(\$loader->getFixtures());
    }
";
    }

    public function loadFixtures()
    {
        return "
    /**
     * Load and execute fixtures from a directory
     * @param string \$directory
     */
    protected function loadFixturesFromDirectory(\$directory)
    {
        \$loader = new Loader();
        \$loader->loadFromDirectory(\$directory);
        \$this->executeFixtures(\$loader);
    }
";
    }

    public function addEntityManager()
    {
        return "
    /**
     * Returns the doctrine orm entity manager
     *
     * @return object
     */
    protected function getEntityManager()
    {
        return \$this->container->get(\$this->entityManagerServiceId);
    }
";
    }

    public function addConstructor()
    {
        return "
    /**
     * Constructor
     *
     * @param string|null \$name     Test name
     * @param array       \$data     Test data
     * @param string      \$dataName Data name
     */
    public function __construct(\$name = null, array \$data = array(), \$dataName = '')
    {
        parent::__construct(\$name, \$data, \$dataName);
        if (!static::\$kernel) {
            static::\$kernel = self::createKernel(array('environment' => \$this->environment,'debug' => \$this->debug));
            static::\$kernel->boot();
        }

        \$this->container = static::\$kernel->getContainer();
        \$this->em = \$this->getEntityManager();
    }
";
    }

    public function generateSetupFunctionTest()
    {
        return "<?php
namespace {$this->namespace}\\Tests;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Liip\FunctionalTestBundle\Test\WebTestCase;

/**
 * Handle configuration and set up of unit tests for api
 * and loads doctrine fixtures using the symfony configuration
 */
abstract class SetUpFunctionalTest extends WebTestCase
{
    protected \$client;
    protected \$uEmail;
    protected \$uPassword;
    protected \$container;
    protected \$em;
    protected \$environment = 'test';
    protected \$debug = true;
    protected \$entityManagerServiceId = 'doctrine.orm.entity_manager';

" . $this->addConstructor() . "
" . $this->addFixtures() . "
" . $this->loadFixtures() . "
" . $this->addEntityManager() . "

    /**
     * Sets up client and user for tests
     */
    public function setUpClientAndUser()
    {
        \$credentials = array(
            'username' => \$this->container->getParameter('unit_test_password'),
            'password' => \$this->container->getParameter('unit_test_email')
        );

        \$this->client = static::makeClient(\$credentials);
        \$this->loadFixtures(array());
    }
}
";
    }
}

