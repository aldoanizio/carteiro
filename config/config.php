<?php

//--------------------------------------------
// Configuration - Used on \carteiro\Mail
//--------------------------------------------

return
[
    /**
     * Default connection to use.
     */

    'default' => 'primary',

    /**
     * You may want to change the origin of the HELO / EHLO request.
     * Setting default value as "localhost" may cause email to be considered spam.
     * http://stackoverflow.com/questions/5294478/significance-of-localhost-in-helo-localhost
     */

    'localhost' => '',

    /**
     * You can define as many mailer connections as you want.
     */

    'connections' =>
    [
        'primary' =>
        [
            'host'   => '',
            'port'   => '',
            'secure' => null, // null, 'ssl', or 'tls'
            'auth'   => true, // true if authorization required
            'user'   => '',
            'pass'   => '',
            'reply'  => '', // default replyto
            'from'   => '', // default email from
            'sender' => '', // default email sender name
        ],
    ],
];