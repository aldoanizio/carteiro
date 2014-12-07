<?php

/**
 * @copyright  Aldo Anizio Lugão Camacho
 * @license    http://www.makoframework.com/license
 */

namespace aldoanizio\carteiro;


// Carteiro package

use \aldoanizio\carteiro\mailer\MailFactory;


/**
 * Carteiro package.
 *
 * @author  Aldo Anizio Lugão Camacho
 */

class CarteiroPackage extends \mako\application\Package
{
    /**
     * Package name.
     *
     * @var string
     */

    protected $packageName = 'aldoanizio/carteiro';

    /**
     * Package namespace.
     *
     * @var string
     */

    protected $fileNamespace = 'carteiro';

    /**
     * Register the service.
     *
     * @access  protected
     */

    protected function bootstrap()
    {
        $this->container->registerSingleton(['aldoanizio\carteiro\mailer\MailFactory', 'carteiro'], function($container)
        {
            return new MailFactory($container->get('config'), $container->get('view'), $container->get('logger'));
        });
    }
}