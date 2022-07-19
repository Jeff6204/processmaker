<?php

namespace ProcessMaker\Package;

use Illuminate\Mail\TransportManager;
use Swift_SmtpTransport;
use ProcessMaker\Managers\GmailTransportManager;

class Office365Driver
{

    public function __invoke(TransportManager $manager)
    {
        return function ($app) {
            $config = $app['config']->get('services.office365', []);
            return new GmailTransportManager($config);
        };
    }

}