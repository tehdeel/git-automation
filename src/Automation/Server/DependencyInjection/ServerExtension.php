<?php

namespace Automation\Server\DependencyInjection;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Symfony\Component\DependencyInjection\Loader;

class ServerExtension extends Extension
{
    const HOOK_ACTION_TAG = 'ga.hook_action';

    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $preRecieveActions = [];
        /** @var array $tags */
        $tags = $container->findTaggedServiceIds(self::HOOK_ACTION_TAG);
        foreach ($tags as $key => $tag) {
            $tag = array_pop($tag);
            if (empty($tag['type'])) {
                throw new InvalidConfigurationException(
                    sprintf('All tags %s must have attribute "type"', self::HOOK_ACTION_TAG)
                );
            }
            switch ($tag['type']) {
                case "pre-recieve" :
                    $preRecieveActions[] = $container->getDefinition($key);
                    break;
                default:
                    throw new InvalidConfigurationException(sprintf('Action type %s not implemented', $tag['type']));
            }
        }
        $container->getDefinition('ga.hook.pre_recieve')->replaceArgument(0, $preRecieveActions);
        $container->getDefinition('ga.service.git_repo')->replaceArgument(0, realpath('.'));
    }
}