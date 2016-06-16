<?php

use chobie\Jira\Api;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{

    public function registerBundles()
    {
        $bundles = [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Automation\Client\ClientBundle(),
            new Automation\Server\ServerBundle(),
            new Coyl\JiraApiRestClientBundle\JiraApiRestClientBundle(),
        ];

        if ($this->shouldLoadDevBundles()) {
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
        return $this->getRootDir() . '/../var/cache/' . $this->getEnvironment();
    }

    public function getLogDir()
    {
        return $this->getRootDir() . '/../var/logs';
    }

    /**
     * @return bool
     */
    public function shouldLoadDevBundles()
    {
        return in_array($this->getEnvironment(), ['dev', 'test'], true);
    }

    /**
     * @inheritdoc
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getRootDir() . '/config/config.yml');
    }
}
