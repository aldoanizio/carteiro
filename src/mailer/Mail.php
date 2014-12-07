<?php

/**
 * @copyright  Aldo Anizio Lugão Camacho
 * @license    http://www.makoframework.com/license
 */

namespace aldoanizio\carteiro\mailer;


// Mako

use \mako\config\Config;
use \mako\utility\Arr;


// Monolog

use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;
use \Psr\Log\LoggerInterface;


/**
 * Mail.
 *
 * @author  Aldo Anizio Lugão Camacho
 */

class Mail
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
     * Logger Interface.
     *
     * @var \Psr\Log\LoggerInterface
     */

    protected $logger;

    /**
     * Connection response
     *
     * @var array
     */

    protected $connection = [];

    /**
     * Server address used to send in HELO / EHLO request
     *
     * @var string
     */

    protected $localhost;

    /**
     * Connection error number
     *
     * @var int
     */

    protected $errno = 0;

    /**
     * Connection error message
     *
     * @var int
     */

    protected $errstr = '';

    /**
     * Timeout time
     *
     * @var int
     */

    protected $timeout = 5;

    /**
     * Set class as Debug mode
     *
     * @var boolean
     */

    protected $debug = false;

    /**
     * Debug lines to to write in log file
     *
     * @var array
     */

    protected $debugLines = [];

    /**
     * STMP server address
     *
     * @var string
     */

    protected $host;

    /**
     * STMP server port
     *
     * @var string
     */

    protected $port;

    /**
     * Use security in connection (null|'ssl'|'tls')
     *
     * @var string
     */

    protected $secure;

    /**
     * Set true if authorization required
     *
     * @var string
     */

    protected $auth;

    /**
     * STMP username auth
     *
     * @var string
     */

    protected $user;

    /**
     * STMP password auth
     *
     * @var string
     */

    protected $pass;

    /**
     * Email From address / name
     *
     * @var string
     */

    protected $from;

    /**
     * Set where to reply address / name
     *
     * @var string
     */

    protected $reply;

    /**
     * Email receiver addresses
     *
     * @var array
     */

    protected $to = [];

    /**
     * Email Carbon Copy addresses
     *
     * @var array
     */

    protected $cc = [];

    /**
     * Email Blind Carbon Copy addresses
     *
     * @var array
     */

    protected $bcc = [];

    /**
     * Message body content in HTML format
     *
     * @var string
     */

    protected $html = '';

    /**
     * Message body content in plain text format
     *
     * @var string
     */

    protected $text = '';

    /**
     * Email Subject
     *
     * @var string
     */

    protected $subject = '';

    /**
     * Email Attachments
     *
     * @var string
     */

    protected $attachments = [];

    /**
     * Set Message charset
     *
     * @var string
     */

    protected $charset = 'UTF-8';

    /**
     * Set Message line break format
     *
     * @var string
     */

    protected $newline = "\r\n";

    /**
     * Set Message encoding type
     *
     * @var string
     */

    protected $encoding = '8bit';

    /**
     * Set Message wordwrap length
     *
     * @var string
     */

    protected $wordwrap = 70;

    //---------------------------------------------
    // Class constructor, destructor etc ...
    //---------------------------------------------

    /**
     * Constructor.
     *
     * @access  public
     * @param   string  $connection  Set an alternative connection to use
     */

    public function __construct(Config $config, LoggerInterface $logger)
    {
        // Config Instance

        $this->config = $config;

        // Logger Interface

        $this->logger = $logger;

        // Set localhost address

        $this->setLocalhost($this->config->get('carteiro::config.localhost'));

        // Load connection parameters config

        $this->useConn($this->config->get('carteiro::config.default'));
    }

    //---------------------------------------------
    // Class methods
    //---------------------------------------------

    /**
     * Load connection parameters from config file.
     *
     * @access  public
     * @param   string  $connection  Connection group name
     */

    public function useConn($config)
    {
        if($this->config->get('carteiro::config.connections.' . $config) === null)
        {
            throw new \InvalidArgumentException(vsprintf("%s(): A valid config name is required to load connection settings.", [__METHOD__]));
        }

        $connection = $this->config->get('carteiro::config.connections.' . $config);

        $this->host = $connection['host'];

        $this->port = $connection['port'];

        $this->secure = $connection['secure'];

        $this->auth = $connection['auth'];

        $this->user = $connection['user'];

        $this->pass = $connection['pass'];

        $this->from($connection['from'], $connection['sender']);

        $this->reply($connection['reply'], $connection['sender']);

        return $this;
    }

    /**
     * Set connection parameters without using package config.
     *
     * @access  public
     * @param   string          $host    STMP server address
     * @param   string          $port    STMP server port
     * @param   string          $secure  Use security in connection
     * @param   string          $auth    Set true if authorization required
     * @param   string          $user    STMP username auth
     * @param   string          $pass    STMP password auth
     * @return  \carteiro\Mail
     * @return
     */

    public function setConn($host, $port, $secure, $auth, $user, $pass)
    {
        $this->host = $host;

        $this->port = $port;

        $this->secure = $secure;

        $this->auth = $auth;

        $this->user = $user;

        $this->pass = $pass;

        return $this;
    }

    /**
     * Get instance "From" address / name
     *
     * @access  public
     * @return  array
     */

    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Set debug mode.
     *
     * @access  public
     * @param   bollean         $debug  Set debug mode (true|false)
     * @return  \carteiro\Mail
     */

    public function debug($debug)
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * Set different debug file location.
     *
     * @access  public
     * @param   string          $path  File path
     * @return  \carteiro\Mail
     */

    public function debugFilePath($path)
    {
        $this->debug(true);

        $this->logger->popHandler();

        $this->logger->pushHandler(new StreamHandler($path, Logger::DEBUG));

        return $this;
    }

    /**
     * Set email "From" address / name
     *
     * @access  public
     * @param   string          $email  A valid email address
     * @param   string          $name   (optional) Set a sender name
     * @return  \carteiro\Mail
     */

    public function from($email, $name = null)
    {
        if(is_array($email))
        {
            // Set convention

            $this->from = ['email' => Arr::get($email, 0), 'name' => Arr::get($email, 1)];
        }
        else
        {
            // Set normal

            $this->from = ['email' => $email, 'name' => $name];
        }

        return $this;
    }

    /**
     * Set email "Reply To" address / name
     *
     * @access  public
     * @param   string          $email  A valid email address
     * @param   string          $name   (optional) Set a reply name
     * @return  \carteiro\Mail
     */

    public function reply($email, $name = null)
    {
        if(is_array($email))
        {
            // Set convention

            $this->reply = ['email' => Arr::get($email, 0), 'name'  => Arr::get($email, 1)];
        }
        else
        {
            // Set normal

            $this->reply = ['email' => $email, 'name'  => $name];
        }

        return $this;
    }

    /**
     * Set email receiver addresses / names
     *
     * @access  public
     * @param   string          $email  A unique or array of valid emails addresses
     * @param   string          $name   (optional) Set a receiver name
     * @return  \carteiro\Mail
     */

    public function to($email, $name = null)
    {
        if(is_array($email))
        {
            foreach($email as $address)
            {
                // Fix array

                if(!is_array($address))
                {
                    $address = array($address);
                }

                // Set convention

                $this->to[] = ['email' => Arr::get($address, 0), 'name'  => Arr::get($address, 1)];
            }
        }
        else
        {
            // Set normal

            $this->to[] = ['email' => $email, 'name' => $name];
        }

        return $this;
    }

    /**
     * Set email carbon copy addresses / names
     *
     * @access  public
     * @param   string          $email  A unique or array of valid emails addresses
     * @param   string          $name   (optional) Set a receiver name
     * @return  \carteiro\Mail
     */

    public function cc($email, $name = null)
    {
        if(is_array($email))
        {
            foreach($email as $address)
            {
                // Fix array

                if(!is_array($address))
                {
                    $address = array($address);
                }

                // Set convention

                $this->cc[] = ['email' => Arr::get($address, 0), 'name'  => Arr::get($address, 1)];
            }
        }
        else
        {
            // Set normal

            $this->cc[] = ['email' => $email, 'name'  => $name];
        }

        return $this;
    }

    /**
     * Set email blind carbon copy addresses / names
     *
     * @access  public
     * @param   string          $email  A unique or array of valid emails addresses
     * @param   string          $name   (optional) Set a receiver name
     * @return  \carteiro\Mail
     */

    public function bcc($email, $name = null)
    {
        if(is_array($email))
        {
            foreach($email as $address)
            {
                // Fix array

                if(!is_array($address))
                {
                    $address = array($address);
                }

                // Set convention

                $this->bcc[] = ['email' => Arr::get($address, 0), 'name'  => Arr::get($address, 1)];
            }
        }
        else
        {
            // Set normal

            $this->bcc[] = ['email' => $email, 'name'  => $name];
        }

        return $this;
    }

    /**
     * Set email body content in html format
     *
     * @access  public
     * @param   string          $html  HTML message
     * @return  \carteiro\Mail
     */

    public function html($html)
    {
        $this->html = $html;

        return $this;
    }

    /**
     * Set email body content in plain text format
     *
     * @access  public
     * @param   string          $text  Text message
     * @return  \carteiro\Mail
     */

    public function text($text)
    {
        $this->text = wordwrap(strip_tags($text), $this->wordwrap);

        return $this;
    }

    /**
     * Set email subject
     *
     * @access  public
     * @param   string          $subject  Email subject
     * @return  \carteiro\Mail
     */

    public function subject($subject)
    {
        $this->subject = '=?' . $this->charset . '?B?' . base64_encode($subject) . '?=';

        return $this;
    }

    /**
     * Attach files to email
     *
     * @access  public
     * @param   string          $path  Unique or Array of Files Real Paths
     * @return  \carteiro\Mail
     */

    public function attach($path)
    {
        if(is_array($path))
        {
            foreach($path as $p)
            {
                $this->attachments[] = $p;
            }
        }
        else
        {
            $this->attachments[] = $path;
        }

        return $this;
    }

    /**
     * Send email after request connection
     *
     * @access  public
     * @return  boolean
     */

    public function send()
    {
        // Connect to server

        if($this->connect())
        {
            // Deliver the email

            $result = $this->deliver() ? true : false;
        }
        else
        {
            $result = false;
        }

        // Disconnect

        $this->disconnect();

        // Write debug

        if($this->debug)
        {
            // Spin debug lines

            foreach($this->debugLines as $line)
            {
                $this->logger->debug($line);
            }
        }

        // Result

        return $result;
    }

    /**
     * Send STMP server connection request
     *
     * @access  private
     * @return  boolean
     */

    private function connect()
    {
        // Modify URL to SSL

        if($this->secure === 'ssl')
        {
            $this->host = 'ssl://' . $this->host;
        }

        // Open connection

        $this->connection = fsockopen($this->host, $this->port, $this->errno, $this->errstr, $this->timeout);

        // Response

        if($this->code() !== 220)
        {
            return false;
        }

        // Request

        $this->request(($this->auth ? 'EHLO ' : 'HELO ') . $this->getLocalhost() . $this->newline);

        // Response

        $this->response();

        // TLS required

        if($this->secure === 'tls')
        {
            // Request

            $this->request('STARTTLS' . $this->newline);

            // Response

            if($this->code() !== 220)
            {
                return false;
            }

            // Enable crypto

            stream_socket_enable_crypto($this->connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

            // Request

            $this->request(($this->auth ? 'EHLO ' : 'HELO ') . $this->getLocalhost() . $this->newline);

            // Response

            if($this->code() !== 220)
            {
                return false;
            }
        }

        // Auth required

        if($this->auth)
        {
            // Request

            $this->request('EHLO ' . $this->getLocalhost() . $this->newline);

            // Response

            if($this->code() !== 250)
            {
                return false;
            }

            // Request

            $this->request('AUTH LOGIN' . $this->newline);

            // Response

            if($this->code() !== 334)
            {
                return false;
            }

            // Request

            $this->request(base64_encode($this->user) . $this->newline);

            // Response

            if($this->code() !== 334)
            {
                return false;
            }

            // Request

            $this->request(base64_encode($this->pass) . $this->newline);

            // Response

            if($this->code() !== 235)
            {
                return false;
            }
        }

        return true;
    }

    /**
     * Construct email header and body parameters
     *
     * @access  private
     * @return  string
     */

    private function construct()
    {
        // Set unique boundary

        $boundary = md5(uniqid(time()));

        // Add "from" info

        $headers[] = 'From: ' . $this->format($this->from);
        $headers[] = 'Reply-To: ' . $this->format($this->reply ? $this->reply : $this->from);
        $headers[] = 'Subject: ' . $this->subject;
        $headers[] = 'Date: ' . date('r');

        // Add "to" receipients

        if(!empty($this->to))
        {
            $string = '';
            foreach($this->to as $recipient)
            {
                $string .= $this->format($recipient) . ', ';
            }

            $string = substr($string, 0, -2);

            $headers[] = 'To: ' . $string;
        }

        // Add "cc" recipients

        if(!empty($this->cc))
        {
            $string = '';
            foreach($this->cc as $recipient)
            {
                $string .= $this->format($recipient) . ', ';
            }

            $string = substr($string, 0, -2);

            $headers[] = 'CC: ' . $string;
        }

        // Build email contents

        if(empty($this->attachments))
        {
            if(empty($this->html))
            {
                // Add text

                $headers[] = 'Content-Type: text/plain; charset="' . $this->charset . '"';
                $headers[] = 'Content-Transfer-Encoding: ' . $this->encoding;
                $headers[] = '';
                $headers[] = $this->text;
            }
            else
            {
                // Add multipart

                $headers[] = 'MIME-Version: 1.0';
                $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
                $headers[] = '';
                $headers[] = 'This is a multi-part message in MIME format.';
                $headers[] = '--' . $boundary;

                // Add text

                $headers[] = 'Content-Type: text/plain; charset="' . $this->charset . '"';
                $headers[] = 'Content-Transfer-Encoding: ' . $this->encoding;
                $headers[] = '';
                $headers[] = $this->text;
                $headers[] = '--' . $boundary;

                // Add html

                $headers[] = 'Content-Type: text/html; charset="' . $this->charset . '"';
                $headers[] = 'Content-Transfer-Encoding: ' . $this->encoding;
                $headers[] = '';
                $headers[] = $this->html;
                $headers[] = '--' . $boundary . '--';
            }
        }
        else
        {
            // Add multipart

            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';
            $headers[] = '';
            $headers[] = 'This is a multi-part message in MIME format.';
            $headers[] = '--' . $boundary;

            // Add text

            $headers[] = 'Content-Type: text/plain; charset="' . $this->charset . '"';
            $headers[] = 'Content-Transfer-Encoding: ' . $this->encoding;
            $headers[] = '';
            $headers[] = $this->text;
            $headers[] = '--' . $boundary;

            if(!empty($this->html))
            {
                // Add html

                $headers[] = 'Content-Type: text/html; charset="' . $this->charset . '"';
                $headers[] = 'Content-Transfer-Encoding: ' . $this->encoding;
                $headers[] = '';
                $headers[] = $this->html;
                $headers[] = '--' . $boundary;
            }

            // Spin thru attachments...

            foreach($this->attachments as $path)
            {
                // File exists

                if(file_exists($path))
                {
                    // Open file

                    $contents = @file_get_contents($path);

                    // If accessible...

                    if($contents)
                    {
                        // Encode file contents

                        $contents = chunk_split(base64_encode($contents));

                        // Add attachment

                        $headers[] = 'Content-Type: application/octet-stream; name="' . basename($path) . '"'; // use different content types here
                        $headers[] = 'Content-Transfer-Encoding: base64';
                        $headers[] = 'Content-Disposition: attachment';
                        $headers[] = '';
                        $headers[] = $contents;
                        $headers[] = '--' . $boundary;
                    }
                }
            }

            // Add last "--"

            $headers[sizeof($headers) - 1] .= '--';
        }

        // Final period

        $headers[] = '.';

        // Build headers string

        $email = '';
        foreach($headers as $header)
        {
            $email .= $header . $this->newline;
        }

        // Return

        return $email;
    }

    /**
     * Deliver SMTP message
     *
     * @access  private
     */

    private function deliver()
    {
        // Request

        $this->request('MAIL FROM: <' . $this->from['email'] . '>' . $this->newline);

        // Response

        $this->response();

        // Merge recipients...

        $recipients = array_merge($this->to, $this->cc, $this->bcc);

        // Spin recipients...

        foreach($recipients as $recipient)
        {
            // Request

            $this->request('RCPT TO: <' . $recipient['email'] . '>' . $this->newline);

            // Response

            $this->response();
        }

        // Request

        $this->request('DATA' . $this->newline);

        // Response

        $this->response();

        // Request

        $this->request($this->construct());

        // Response

        return ($this->code() === 250) ? true : false;
    }

    /**
     * Disconnect from SMTP server after request is done
     *
     * @access  private
     */

    private function disconnect()
    {
        // Request

        $this->request('QUIT' . $this->newline);

        // Response

        $this->response();

        // Close connection

        fclose($this->connection);
    }

    /**
     * Return response code
     *
     * @access  private
     * @return  string
     */

    private function code()
    {
        // Filter code from response

        return (int) substr($this->response(), 0, 3);
    }

    /**
     * Put a SMTP request parameter
     *
     * @access  private
     * @param   string    $string  SMTP Parameter
     */

    private function request($string)
    {
        // Report

        if($this->debug)
        {
            $this->debugLines[] = '<code><strong>Request: ' . $string . '</strong></code><br/>';
        }

        // Send

        fputs($this->connection, $string);
    }

    /**
     * Return a SMTP response
     *
     * @access  private
     * @return  string
     */

    private function response()
    {
        // Get response

        $response = '';

        // Spin SMTP response

        while ($str = fgets($this->connection, 4096))
        {
            $response .= $str;

            if(substr($str, 3, 1) === ' ')
            {
                break;
            }
        }

        // Report

        if($this->debug)
        {
            $this->debugLines[] = '<code>Response: ' . $response . '</code><br/>';
        }

        // Return

        return $response;
    }

    /**
     * Format a recipient in "Name <email>" format
     *
     * @access  private
     * @param   array   $recipient  Array of email and name
     * @return  string
     */

    private function format($recipient)
    {
        // Format "name <email>"

        if($recipient['name'])
        {
            return $recipient['name'] .' <'.$recipient['email'].'>';
        }
        else
        {
            return '<' . $recipient['email'] . '>';
        }
    }

    /**
     * Generate 'localhost' used in HELO / EHLO requests
     *
     * @access  private
     * @return  string
     */

    private function getLocalhost()
    {
        return $this->localhost <> '' ? $this->localhost : getenv('SERVER_NAME');
    }

    /**
     * Set 'localhost' used in HELO / EHLO requests
     *
     * @access  public
     * @param   array   $localhost  Localhost address
     * @return  string
     */

    public function setLocalhost($localhost)
    {
        return $this->localhost = $localhost;
    }
}
