<?php

namespace Codito\Silex\Console\Helper;

use Codito\Silex\Console\Descriptor\TextDescriptor;

use Symfony\Component\Console\Helper\DescriptorHelper as BaseDescriptorHelper;

/**
 * This descriptor is based on SymfonyFrameworkBundle's descriptor helper
 * @author Jean-François Simon <jeanfrancois.simon@sensiolabs.com>
 * 
 * Silex integration:
 * @author Grzegorz Korba <grzegorz.korba@codito.net>
 */
class DescriptorHelper extends BaseDescriptorHelper
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this
            ->register('txt', new TextDescriptor())
        ;
    }
}
