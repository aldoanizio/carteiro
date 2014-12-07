<?php

/**
 * @copyright  Aldo Anizio Lugão Camacho
 * @license    http://www.makoframework.com/license
 */

namespace aldoanizio\carteiro\mailer;


// Carteiro Mailer

use \aldoanizio\carteiro\mailer\Mail;

// Mako

use \mako\config\Config;
use \mako\view\ViewFactory;
use \mako\utility\Arr;

// Monolog

use \Psr\Log\LoggerInterface;


/**
 * Mail Factory.
 *
 * @author  Aldo Anizio Lugão Camacho
 */

class MailFactory
{
    //---------------------------------------------
    // Class properties
    //---------------------------------------------

    /**
     * Config instance.
     *
     * @var \mako\core\Config
     */

    protected $config;

    /**
     * View factory instance.
     *
     * @var \mako\view\ViewFactory
     */

    protected $view;

    /**
     * Logger Interface.
     *
     * @var \Psr\Log\LoggerInterface
     */

    protected $logger;

    //---------------------------------------------
    // Class constructor, destructor etc ...
    //---------------------------------------------

    /**
     * Constructor.
     *
     * @access  public
     * @param   \mako\core\Config         $config       Config instance
     * @param   \mako\view\ViewFactory    $viewFactory  View factory instance
     * @param   \Psr\Log\LoggerInterface  $logger       Logger Interface
     */

    public function __construct(Config $config, ViewFactory $viewFactory, LoggerInterface $logger)
    {
        // Instantiate Config

        $this->config = $config;

        // Define View Factory

        $this->view = $viewFactory;

        // Logger Interface

        $this->logger = $logger;
    }

    //---------------------------------------------
    // Class methods
    //---------------------------------------------

    /**
     * Creates and dispatch message.
     *
     * @access  public
     * @param   mixed           $view      View parameters
     * @param   array           $data      View data
     * @param   mixed           $callback  Callback functions
     * @return  \carteiro\Mail
     */

    public function create($view, array $data = [], $callback)
    {
        // Instantiate Mailer

        $mail = new Mail($this->config, $this->logger);

        // Extract views

        list($html, $text, $raw) = $this->extractViews($view);

        // Set mail content

        $mail = $this->setContent($mail, $html, $text, $raw, $data);

        // Call mail functions

        $this->callMailerBuilder($callback, $mail);

        // Dispatch email

        return $mail->send();
    }

    /**
     * Extract parsed views by string or array.
     *
     * @access  protected
     * @param   mixed  $views  View parameters
     * @return  array
     *
     * @throws \InvalidArgumentException
     */

    protected function extractViews($views)
    {
        // Return only html view

        if(is_string($views))
        {
            return [$views, null, null];
        }

        // If the given view is an array with numeric or associative keys

        if(is_array($views))
        {
            if(Arr::isAssoc($views))
            {
                // Return only raw body content

                if(Arr::has($views, 'raw') && Arr::get($views, 'raw'))
                {

                    return [null, null, Arr::get($views, 'raw')];
                }
                else
                {
                    // Return associative values in order (html | text | raw)

                    return [Arr::get($views, 'html'), Arr::get($views, 'text'), null];
                }
            }
            else
            {
                // Return numeric values in order (html | text | raw)

                return [Arr::get($views, 0), Arr::get($views, 1), null];
            }
        }

        throw new \InvalidArgumentException("Invalid view parameters.");
    }

    /**
     * Add content to mail instance.
     *
     * @access  protected
     * @param   string          $mail  Mail instance
     * @param   string          $html  Mako template path to use in html content
     * @param   string          $text  Mako template path to use in text content
     * @param   string          $raw   Raw mail content
     * @param   array           $data  Template data array
     * @return  \carteiro\Mail
     */

    protected function setContent($mail, $html, $text, $raw, array $data)
    {
        // Set mail content using template views

        if(empty($raw))
        {
            // Send html body content

            if(isset($html))
            {
                $mail->html($this->createView($html, $data));
            }

            // Send plain text body content

            if(isset($text))
            {
                $mail->text($this->createView($text, $data));
            }
        }
        else
        {
            // Set body content using raw value

            $mail->html($raw);
        }

        return $mail;
    }

    /**
     * Return rendered content using mako templates
     *
     * @access  protected
     * @param   string  $view  View path
     * @param   array   $data  View data
     * @return  string
     */

    protected function createView($view, array $data)
    {
        // Create view

        $view = $this->view->create($view, $data);

        // Return rendered view

        return $view->render();
    }

    /**
     * Call the provided message builder.
     *
     * @access  protected
     * @param   mixed           $callback
     * @param   \carteiro\Mail  $mail
     * @return  mixed
     *
     * @throws  \InvalidArgumentException
     */

    protected function callMailerBuilder($callback, $mail)
    {
        if($callback instanceof \Closure)
        {
            return call_user_func($callback, $mail);
        }

        throw new \InvalidArgumentException("Callback is not valid.");
    }
}