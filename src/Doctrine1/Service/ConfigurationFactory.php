<?php declare(strict_types=1);

namespace Doctrine1\Service;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

use Doctrine_Core;
use Doctrine_Manager;
use Exception;

class ConfigurationFactory implements FactoryInterface
{
    protected $connections = [];

    // For ZF3
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return $this->create($container);
    }

    // For older ZF2
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return $this->create($serviceLocator);
    }

    protected function create($locator)
    {
        $config      = $locator->get('Config');
        $cacheDriver = $locator->get('Doctrine1\CacheDriver');

        if (!isset($config['doctrine1'])) {
            throw new Exception('You must set the "doctrine1" config setting to use this module');
        }

        foreach ($config['doctrine1']['connections'] as $name => $connection) {
            $this->connections[$name] = $this->connect($name, $connection, $cacheDriver);
        }

        // Set default connection
        $manager     = Doctrine_Manager::getInstance();
        $connections = $manager->getConnections();

        if (count($connections) > 1 && !empty($config['doctrine1']['default_connection'])) {
            $manager->setCurrentConnection($config['doctrine1']['default_connection']);
        }

        // Custom hydrators if defined in config
        if (!empty($config['doctrine1']['hydrators'])) {
            foreach ($config['doctrine1']['hydrators'] as $hydrator => $className) {
                $manager->registerHydrator($hydrator, $className);
            }
        }

        return $this->connections;
    }

    protected function connect($name, $options, $cacheDriver)
    {
        $conn = Doctrine_Manager::connection(
            $options['system'] . '://' . urlencode($options['user']) . ':'
            . urlencode($options['password']) . '@'
            . $options['server'] . ':' . $options['port'] . '/'
            . $options['database'],
            $name
        );

        // Query cache (global)
        if (isset($options['enable_query_cache']) && $options['enable_query_cache'] == true) {
            $conn->setAttribute(Doctrine_Core::ATTR_QUERY_CACHE, $cacheDriver);
        }

        // Result cache (enabled on a per-query basis)
        $conn->setAttribute(Doctrine_Core::ATTR_RESULT_CACHE, $cacheDriver);
        if (!empty($options['result_cache_lifespan'])) {
            $conn->setAttribute(Doctrine_Core::ATTR_RESULT_CACHE_LIFESPAN, $options['result_cache_lifespan']);
        }

        // Override default collection class so we can add our extensions
        if (!empty($options['collection_class'])) {
            $conn->setAttribute(Doctrine_Core::ATTR_COLLECTION_CLASS, $options['collection_class']);
        }

        // Set connection to UTF-8
        if (!empty($options['connection_charset'])) {
            $conn->setCharset($options['connection_charset']);
        }

        // Doctrine stores hydrated objects in memory, which may have negative consequences in some environments
        // i.e long running processes like daemons or workers, or automated testing environments
        if (!empty($options['auto_free_query_objects'])) {
            $conn->setAttribute(Doctrine_Core::ATTR_AUTO_FREE_QUERY_OBJECTS, $options['auto_free_query_objects']);
        }

        // Identifier Quoting
        $conn->setAttribute(
            Doctrine_Core::ATTR_QUOTE_IDENTIFIER,
            $options['quote_identifier'] ?? true
        );

        // Callbacks (for timestampable and other behaviors)
        $conn->setAttribute(
            Doctrine_Core::ATTR_USE_DQL_CALLBACKS,
            $options['use_dql_callbacks'] ?? true
        );

        return $conn;
    }
}
