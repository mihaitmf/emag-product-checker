<?php

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

return [
    ClientInterface::class => function () {
        return new Client([
            'verify' => false,
        ]);
    },
];
