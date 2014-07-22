<?php

/**
 * @copyright  Aldo Anizio Lugão Camacho
 * @license    http://www.makoframework.com/license
 */

namespace carteiro\service;

use \carteiro\MailFactory;

/**
 * Mail factory service.
 *
 * @author  Aldo Anizio Lugão Camacho
 */

class MailFactoryService extends \mako\application\services\Service
{
	/**
	 * Registers the service.
	 *
	 * @access  public
	 */

	public function register()
	{
		$this->container->registerSingleton(['carteiro\service\MailFactory', 'carteiro'], function($container)
		{
			return new MailFactory($container->get('config'), $container->get('view'), $container->get('logger'));
		});
	}
}