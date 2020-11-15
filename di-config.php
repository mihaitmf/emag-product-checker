<?php

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

return [
    ClientInterface::class => static function () {
        return new Client(
            [
                'verify' => false, // turn off SSL verification
            ]
        );
    },
];
