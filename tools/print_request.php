<?php

function getRawHttpRequest()
{
    $request = "{$_SERVER['REQUEST_METHOD']} {$_SERVER['REQUEST_URI']} {$_SERVER['SERVER_PROTOCOL']}\r\n";

    foreach (getallheaders() as $name => $value) {
        $request .= "$name: $value\r\n";
    }

    $request .= "\r\n" . file_get_contents('php://input');

    return $request;
}

file_put_contents('raw_request.txt', getRawHttpRequest());

echo 'PRINTED REQUEST!';
