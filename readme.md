# Carteiro Mailer

This is a lightweight SMTP mailer package for Mako Framework 4.0.

## Install

Use composer to install. Simply add package to your project.

```php
composer require aldoanizio/carteiro:*
```

So now you can update your project and install package with a single command.

```php
composer update
```


### Register Service

After installing you'll have to register a new service in your ``app/config/application.php`` file.

```php
    /**
     * Services to register in the dependecy injection container.
     */

    'services' =>
    [
        ....
        'carteiro\service\MailFactoryService',
    ],
```

### Configuring

There are two ways to configure your package. The first is editing config file directly in packages folder: ``app/packages/carteiro/config/config.php`` and input the necessary information.

If you like you can copy the package's config file ``app/packages/carteiro/config/config.php`` into ``app/config/packages/carteiro`` folder and the application will load that file instead of the one located in the package. This makes it possible to update the package while keeping your custom settings.

## Basic Usage

To send emails just use the ``create`` method parsing some data.

```php
$this->carteiro->create('my.email.view', $data, function($mail)
{
    $mail->subject('Hello World');

    $mail->to('foo@bar.com', 'John Doe');
});
```

The first argument passed to the ``create`` method is the name of the view that should be used as the e-mail body. If passed as string will set mail body to use ``html`` format.

The second is the ``$data`` that should be passed to the view.

The third is a Closure allowing you to specify various options on the e-mail message. Also using closure you can access other variables.

#### Message format

You can also specify a plain text view to use in addition to an ``html`` view. To do this you need to use an array to define wich view to use in each format.

```php
$this->carteiro->create(['text' => 'my.text.view', 'html' => 'my.html.view'], $data, function($mail) use ($newUser)
{
    $mail->subject('Welcome New User');

    $mail->to($newUser->email, $newUser->name);
}
```

Or just use ``text`` key to send email as a plain text only.

```php
$this->carteiro->create(['text' => 'my.text.view'], $data, function($mail) use ($newUser)
{
    $mail->subject('Welcome New User');

    $mail->to($newUser->email, $newUser->name);
}
```

#### Raw content data
Sometimes you need to send emails using a content that do not depends of a view file. For example if you would like to send a custom message to one of your customers stored in database.

To do this you can make use of ``raw`` key to parse a custom content. Notice in this case you don't need to use a view data so pass an empty array to second parameter.

```php
// Your form post data
$postData = $this->request->post();

// ORM
$customer = Custommer::get($postData['customer_id']);

// Send message
$this->carteiro->create(['raw' => $postData['message']], [], function($mail) use ($postData, $customer)
{
    $mail->subject($postData['subject']);

    $mail->to($customer->email, $customer->name);
});
```

## Mail options

You may specify other options on the e-mail message such as any carbon copies or attachments as well.

### Set email subject

```php
$mail->subject('Welcome User');
```

### Set email "From" and "Reply-To" address / name

Instead use information stored in config file you can use array or string to set 'from' and 'reply-to' addresses / name.

```php
$address = ['webmaster@domain.tld', 'System Webmaster'];

// From
$mail->from($address);
// or
$mail->from('webmaster@domain.tld', 'System Webmaster');

// Reply To
$mail->reply($address);
// or
$mail->reply('webmaster@domain.tld', 'System Webmaster');
```

### Set email receiver addresses / names.

You can define multiples recipients (name is optional).

```php
// Add to
$mail->to('receiver1@domain.tld');
$mail->to('receiver2@domain.tld');
```
You can add multiples using array.

```php
// Build list
$list = [];
$list[] = 'receiver1@domain.tld';
$list[] = 'receiver2@domain.tld';

// List with names
$list = [];
$list[] = ['receiver1@domain.tld', 'Receiver 1'];
$list[] = ['receiver1@domain.tld', 'Receiver 2'];

// Add to
$mail->to($list);
```

### Add carbon copy addresses / names

The ``cc`` option works like ``to``option even using multiples recipients.

```php
$mail->cc('carboncopy1@domain.tld', 'Copy Receiver 1');

// or

$mail->cc($list);
```

### Add blind carbon copy addresses / names

The ``bcc`` option works like ``to``option even using multiples recipients.

```php
$mail->bcc('blindcarboncopy1@domain.tld', 'Blind Copy Receiver 1');

// or

$mail->bcc($list);
```

### Attach files to email

You can add attachments.

```php
$mail->attach('/path/to/file1.png');
$mail->attach('/path/to/file2.png');
```

### Debug Mode

In config file you can flag ``'debug_mode' = true;``, which can be helpful in testing your SMTP connections.  It will write log files with server responses from each step in the email sending process.

Or you can enable/disable debug mode just in some cases without edit config file.

```php
$mail->debug(true);

$mail->debug(false);
```

### Change server parameters

In config file you can define multiple ``connections``, each of them with their own settings. The default connection settings used is defined in ``''default' => 'primary'`` flag.

Sometimes you may want to change which connection will be used. You can do this 'on-the-fly'.

```php
$mail->useConn('my_other_connection');
```

Also, you can define server parameters that are not included in config file.

```php
$mail->setConn('server_host_addr', 'server_conn_port', (null|'ssl'|'starttls'), (true|false), 'my_auth_user_name', 'my_auth_password');
```

## Credits

This package was build in top of Laravel SMTP class by swt83 - (https://github.com/swt83/php-laravel-smtp)

## Limitations

Just like original class, this package has some limitations. Please feel free to contribute to this project.

* Does not support encryption.
* Does not support priority level.
* Does not keep connection open for spooling email sends.
