<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use chobie\Jira\Api;
use Symfony\Component\DependencyInjection\Definition;

class AppKernel extends Kernel
{

    use MicroKernelTrait;

    public function registerBundles()
    {
        $bundles = [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Automation\Client\ClientBundle(),
        ];

        if (in_array($this->getEnvironment(), ['dev', 'test'], true)) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
        }

        return $bundles;
    }

    public function getRootDir()
    {
        return __DIR__;
    }

    public function getCacheDir()
    {
        return Phar::running() ? '/tmp/frag' : $this->getRootDir() . '/var/cache/' . $this->getEnvironment();
    }

    public function getLogDir()
    {
        return Phar::running() ? '/tmp/frag/logs' : $this->getRootDir() . '/var/logs';
    }

    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        // TODO: Implement configureRoutes() method.
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader)
    {
        $parser = new \Symfony\Component\Yaml\Parser();
        $parametersFile = file_get_contents(
            $this->getRootDir() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'parameters.yml'
        );
        $parameters = $parser->parse($parametersFile);
        $c->loadFromExtension('framework', ['secret' => 'none']);
        $c->addDefinitions(
            ['git_automation.jira_api' => $this->createJiraApiService($parameters['parameters'])]
        );

    }

    protected function createJiraApiService($parameters)
    {
        $auth = new Definition(
            Api\Authentication\Basic::class, [$parameters['jira.user'], $parameters['jira.password']]
        );

        return new Definition(Api::class, [$parameters['jira.host'], $auth]);
    }

}
