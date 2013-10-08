<?php

namespace Doctrine1;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\Mvc\MvcEvent;

class Module implements AutoloaderProviderInterface
{
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    // if we're in a namespace deeper than one level we need to fix the \ in the path
                    __NAMESPACE__ => __DIR__ . '/src/' . str_replace('\\', '/', __NAMESPACE__),
                )
                // Put doctrine here as prefixes?
            ),
        );
    }

    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'Doctrine1\Configuration' => 'Doctrine1\Service\ConfigurationFactory',
                'Doctrine1\CacheDriver'   => function ($serviceManager) {
                    $config = $serviceManager->get('Config');
                    if (empty($config['cache_driver_class'])) {
                        return new \Doctrine_Cache_Array();
                    } else {
                        return new $config['cache_driver_class'];
                    }
                },
            ),
        );
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(MvcEvent $e)
    {

    }
}
