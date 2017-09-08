<?php

namespace Codito\Silex\Console\Descriptor;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * This descriptor is based on SymfonyFrameworkBundle's descriptor
 * @author Jean-François Simon <jeanfrancois.simon@sensiolabs.com>
 * 
 * Silex integration:
 * @author Grzegorz Korba <grzegorz.korba@codito.net>
 */
class TextDescriptor extends Descriptor
{
    /**
     * {@inheritdoc}
     */
    protected function describeRouteCollection(RouteCollection $routes, array $options = array())
    {
        $showControllers = isset($options['show_controllers']) && $options['show_controllers'];

        $tableHeaders = array('Name', 'Method', 'Scheme', 'Host', 'Path');
        if ($showControllers) {
            $tableHeaders[] = 'Controller';
        }

        $tableRows = array();
        foreach ($routes->all() as $name => $route) {
            $row = array(
                $name,
                $route->getMethods() ? implode('|', $route->getMethods()) : 'ANY',
                $route->getSchemes() ? implode('|', $route->getSchemes()) : 'ANY',
                '' !== $route->getHost() ? $route->getHost() : 'ANY',
                $route->getPath(),
            );

            if ($showControllers) {
                $controller = $route->getDefault('_controller');
                if ($controller instanceof \Closure) {
                    $controller = 'Closure';
                } elseif (is_object($controller)) {
                    $controller = get_class($controller);
                }
                $row[] = $controller;
            }

            $tableRows[] = $row;
        }

        if (isset($options['output'])) {
            $options['output']->table($tableHeaders, $tableRows);
        } else {
            $table = new Table($this->getOutput());
            $table->setHeaders($tableHeaders)->setRows($tableRows);
            $table->render();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function describeRoute(Route $route, array $options = array())
    {
        $tableHeaders = array('Property', 'Value');
        $tableRows = array(
            array('Route Name', isset($options['name']) ? $options['name'] : ''),
            array('Path', $route->getPath()),
            array('Path Regex', $route->compile()->getRegex()),
            array('Host', ('' !== $route->getHost() ? $route->getHost() : 'ANY')),
            array('Host Regex', ('' !== $route->getHost() ? $route->compile()->getHostRegex() : '')),
            array('Scheme', ($route->getSchemes() ? implode('|', $route->getSchemes()) : 'ANY')),
            array('Method', ($route->getMethods() ? implode('|', $route->getMethods()) : 'ANY')),
            array('Requirements', ($route->getRequirements() ? $this->formatRouterConfig($route->getRequirements()) : 'NO CUSTOM')),
            array('Class', get_class($route)),
            array('Defaults', $this->formatRouterConfig($route->getDefaults())),
            array('Options', $this->formatRouterConfig($route->getOptions())),
        );
        if (isset($options['callable'])) {
            $tableRows[] = array('Callable', $options['callable']);
        }

        $table = new Table($this->getOutput());
        $table->setHeaders($tableHeaders)->setRows($tableRows);
        $table->render();
    }

    /**
     * {@inheritdoc}
     */
    protected function describeEventDispatcherListeners(EventDispatcherInterface $eventDispatcher, array $options = array())
    {
        $event = array_key_exists('event', $options) ? $options['event'] : null;

        if (null !== $event) {
            $title = sprintf('Registered Listeners for "%s" Event', $event);
        } else {
            $title = 'Registered Listeners Grouped by Event';
        }

        $options['output']->title($title);

        $registeredListeners = $eventDispatcher->getListeners($event);
        if (null !== $event) {
            $this->renderEventListenerTable($eventDispatcher, $event, $registeredListeners, $options['output']);
        } else {
            ksort($registeredListeners);
            foreach ($registeredListeners as $eventListened => $eventListeners) {
                $options['output']->section(sprintf('"%s" event', $eventListened));
                $this->renderEventListenerTable($eventDispatcher, $eventListened, $eventListeners, $options['output']);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function describeCallable($callable, array $options = array())
    {
        $this->writeText($this->formatCallable($callable), $options);
    }

    /**
     * @param array $array
     */
    private function renderEventListenerTable(EventDispatcherInterface $eventDispatcher, $event, array $eventListeners, SymfonyStyle $io)
    {
        $tableHeaders = array('Order', 'Callable', 'Priority');
        $tableRows = array();

        $order = 1;
        foreach ($eventListeners as $order => $listener) {
            $tableRows[] = array(sprintf('#%d', $order + 1), $this->formatCallable($listener), $eventDispatcher->getListenerPriority($event, $listener));
        }

        $io->table($tableHeaders, $tableRows);
    }

    /**
     * @param array $config
     *
     * @return string
     */
    private function formatRouterConfig(array $config)
    {
        if (empty($config)) {
            return 'NONE';
        }

        ksort($config);

        $configAsString = '';
        foreach ($config as $key => $value) {
            $configAsString .= sprintf("\n%s: %s", $key, $this->formatValue($value));
        }

        return trim($configAsString);
    }

    /**
     * @param callable $callable
     *
     * @return string
     */
    private function formatCallable($callable)
    {
        if (is_array($callable)) {
            if (is_object($callable[0])) {
                return sprintf('%s::%s()', get_class($callable[0]), $callable[1]);
            }

            return sprintf('%s::%s()', $callable[0], $callable[1]);
        }

        if (is_string($callable)) {
            return sprintf('%s()', $callable);
        }

        if ($callable instanceof \Closure) {
            return '\Closure()';
        }

        if (method_exists($callable, '__invoke')) {
            return sprintf('%s::__invoke()', get_class($callable));
        }

        throw new \InvalidArgumentException('Callable is not describable.');
    }

    /**
     * @param string $content
     * @param array  $options
     */
    private function writeText($content, array $options = array())
    {
        $this->write(
            isset($options['raw_text']) && $options['raw_text'] ? strip_tags($content) : $content,
            isset($options['raw_output']) ? !$options['raw_output'] : true
        );
    }
}
