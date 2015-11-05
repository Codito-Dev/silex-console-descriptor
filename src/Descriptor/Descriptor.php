<?php

namespace Codito\Silex\Console\Descriptor;

use Symfony\Component\Console\Descriptor\DescriptorInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * This descriptor is based on SymfonyFrameworkBundle's descriptor
 * @author Jean-François Simon <jeanfrancois.simon@sensiolabs.com>
 * 
 * Silex integration:
 * @author Grzegorz Korba <grzegorz.korba@codito.net>
 */
abstract class Descriptor implements DescriptorInterface
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * {@inheritdoc}
     */
    public function describe(OutputInterface $output, $object, array $options = array())
    {
        $this->output = $output;

        switch (true) {
            case $object instanceof RouteCollection:
                $this->describeRouteCollection($object, $options);
                break;
            case $object instanceof Route:
                $this->describeRoute($object, $options);
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Object of type "%s" is not describable.', get_class($object)));
        }
    }

    /**
     * Returns the output.
     *
     * @return OutputInterface The output
     */
    protected function getOutput()
    {
        return $this->output;
    }

    /**
     * Writes content to output.
     *
     * @param string $content
     * @param bool   $decorated
     */
    protected function write($content, $decorated = false)
    {
        $this->output->write($content, false, $decorated ? OutputInterface::OUTPUT_NORMAL : OutputInterface::OUTPUT_RAW);
    }

    /**
     * Writes content to output.
     *
     * @param Table $table
     * @param bool  $decorated
     */
    protected function renderTable(Table $table, $decorated = false)
    {
        if (!$decorated) {
            $table->getStyle()->setCellRowFormat('%s');
            $table->getStyle()->setCellRowContentFormat('%s');
            $table->getStyle()->setCellHeaderFormat('%s');
        }

        $table->render();
    }

    /**
     * Describes an InputArgument instance.
     *
     * @param RouteCollection $routes
     * @param array           $options
     */
    abstract protected function describeRouteCollection(RouteCollection $routes, array $options = array());

    /**
     * Describes an InputOption instance.
     *
     * @param Route $route
     * @param array $options
     */
    abstract protected function describeRoute(Route $route, array $options = array());

    /**
     * Formats a value as string.
     *
     * @param mixed $value
     *
     * @return string
     */
    protected function formatValue($value)
    {
        if (is_object($value)) {
            return sprintf('object(%s)', get_class($value));
        }

        if (is_string($value)) {
            return $value;
        }

        return preg_replace("/\n\s*/s", '', var_export($value, true));
    }
}