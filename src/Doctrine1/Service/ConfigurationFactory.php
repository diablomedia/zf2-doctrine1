<?php

namespace Doctrine1\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ConfigurationFactory implements FactoryInterface
{
    protected $connections = array();

    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');
        $cacheDriver = $serviceLocator->get('Doctrine1\CacheDriver');

        if (!isset($config['doctrine1'])) {
            throw new \Exception('You must set the "doctrine1" config setting to use this module');
        }

        foreach ($config['doctrine1']['connections'] as $name => $connection) {
            $this->connections[$name] = $this->connect($name, $connection, $cacheDriver);
        }

        // Set default connection
        $manager = \Doctrine_Manager::getInstance();
        $connections = $manager->getConnections();

        if (count($connections) > 1 && !empty($config['doctrine1']['default_connection'])) {
            $manager->setCurrentConnection($config['doctrine1']['default_connection']);
        }

        return $this->connections;
    }

    protected function connect($name, $options, $cacheDriver)
    {
        $conn = \Doctrine_Manager::connection(
            $options['system'] . '://' . $options['user'] . ':'
            . $options['password'] . '@'
            . $options['server'] . ':' . $options['port'] . '/'
            . $options['database'],
            $name
        );

        // Query cache (global)
        if (isset($options['enable_query_cache']) && $options['enable_query_cache'] == true) {
            $conn->setAttribute(\Doctrine::ATTR_QUERY_CACHE, $cacheDriver);
        }

        // Result cache (enabled on a per-query basis)
        $conn->setAttribute(\Doctrine::ATTR_RESULT_CACHE, $cacheDriver);
        if (!empty($options['result_cache_lifespan'])) {
            $conn->setAttribute(\Doctrine::ATTR_RESULT_CACHE_LIFESPAN, $options['result_cache_lifespan']);
        }

        // Override default collection class so we can add our extensions
        if (!empty($options['collection_class'])) {
            $conn->setAttribute(\Doctrine_Core::ATTR_COLLECTION_CLASS, $options['collection_class']);
        }

        // Set connection to UTF-8
        if (!empty($options['connection_charset'])) {
            $conn->setCharset($options['connection_charset']);
        }

        // Doctrine stores hydrated objects in memory, which may have negative consequences in some environments
        // i.e long running processes like daemons or workers, or automated testing environments
        if (!empty($options['auto_free_query_objects'])) {
            $conn->setAttribute(\Doctrine_Core::ATTR_AUTO_FREE_QUERY_OBJECTS, $options['auto_free_query_objects']);
        }

        // Identifier Quoting
        $conn->setAttribute(
            \Doctrine::ATTR_QUOTE_IDENTIFIER,
            isset($options['quote_identifier']) ? $options['quote_identifier'] : true
        );

        // Callbacks (for timestampable and other behaviors)
        $conn->setAttribute(
            \Doctrine::ATTR_USE_DQL_CALLBACKS,
            isset($options['use_dql_callbacks']) ? $options['use_dql_callbacks'] : true
        );

        return $conn;
    }
}
