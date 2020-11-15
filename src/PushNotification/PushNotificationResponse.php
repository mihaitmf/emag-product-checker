<?php

namespace Notifier\PushNotification;

class PushNotificationResponse
{
    /** @var bool */
    private $isSuccessful;

    /** @var string */
    private $error;

    private function __construct()
    {
    }

    /**
     * @return PushNotificationResponse
     */
    public static function success()
    {
        $response = new self();
        $response->isSuccessful = true;

        return $response;
    }

    /**
     * @param string $errorString
     *
     * @return PushNotificationResponse
     */
    public static function error($errorString)
    {
        $response = new self();
        $response->isSuccessful = false;
        $response->error = $errorString;

        return $response;
    }

    /**
     * @return bool
     */
    public function isSuccessful()
    {
        return $this->isSuccessful;
    }

    /**
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }
}
