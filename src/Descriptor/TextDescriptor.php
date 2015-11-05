<?php

namespace Codito\Silex\Console\Descriptor;

use Symfony\Component\Console\Helper\Table;
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
        $headers = array('Name', 'Method', 'Scheme', 'Host', 'Path');
        $table = new Table($this->getOutput());
        $table->setStyle('compact');
        $table->setHeaders($showControllers ? array_merge($headers, array('Controller')) : $headers);

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

            $table->addRow($row);
        }

        $this->writeText($this->formatSection('router', 'Current routes')."\n", $options);
        $this->renderTable($table, !(isset($options['raw_output']) && $options['raw_output']));
    }

    /**
     * {@inheritdoc}
     */
    protected function describeRoute(Route $route, array $options = array())
    {
        $requirements = $route->getRequirements();
        unset($requirements['_scheme'], $requirements['_method']);

        // fixme: values were originally written as raw
        $description = array(
            '<comment>Path</comment>         '.$route->getPath(),
            '<comment>Path Regex</comment>   '.$route->compile()->getRegex(),
            '<comment>Host</comment>         '.('' !== $route->getHost() ? $route->getHost() : 'ANY'),
            '<comment>Host Regex</comment>   '.('' !== $route->getHost() ? $route->compile()->getHostRegex() : ''),
            '<comment>Scheme</comment>       '.($route->getSchemes() ? implode('|', $route->getSchemes()) : 'ANY'),
            '<comment>Method</comment>       '.($route->getMethods() ? implode('|', $route->getMethods()) : 'ANY'),
            '<comment>Class</comment>        '.get_class($route),
            '<comment>Defaults</comment>     '.$this->formatRouterConfig($route->getDefaults()),
            '<comment>Requirements</comment> '.($requirements ? $this->formatRouterConfig($requirements) : 'NO CUSTOM'),
            '<comment>Options</comment>      '.$this->formatRouterConfig($route->getOptions()),
        );

        if (isset($options['name'])) {
            array_unshift($description, '<comment>Name</comment>         '.$options['name']);
            array_unshift($description, $this->formatSection('router', sprintf('Route "%s"', $options['name'])));
        }

        $this->writeText(implode("\n", $description)."\n", $options);
    }

    /**
     * @param array $array
     *
     * @return string
     */
    private function formatRouterConfig(array $array)
    {
        if (!count($array)) {
            return 'NONE';
        }

        $string = '';
        ksort($array);
        foreach ($array as $name => $value) {
            $string .= ($string ? "\n".str_repeat(' ', 13) : '').$name.': '.$this->formatValue($value);
        }

        return $string;
    }

    /**
     * @param string $section
     * @param string $message
     *
     * @return string
     */
    private function formatSection($section, $message)
    {
        return sprintf('<info>[%s]</info> %s', $section, $message);
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