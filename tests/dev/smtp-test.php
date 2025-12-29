<?php

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

$transport = Transport::fromDsn('smtp://mailpit:1025');
$mailer = new Mailer($transport);

$email = (new Email())
    ->from('test@example.com')
    ->to('test@example.com')
    ->subject('TEST SMTP OK FROM FILE')
    ->text('Hello from standalone test');

$mailer->send($email);

echo "MAIL SENT\n";
