<?php

namespace Notifier\PushNotification;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Notifier\Common\ConfigParser;
use RuntimeException;

class PushNotificationService
{
    /** @var ClientInterface */
    private $httpClient;

    /** @var ConfigParser */
    private $config;

    public function __construct(ClientInterface $httpClient, ConfigParser $config)
    {
        $this->httpClient = $httpClient;
        $this->config = $config;
    }

    /**
     * @param string $message
     * @param string $linkUrl
     *
     * @return PushNotificationResponse
     */
    public function notify($message, $linkUrl = '')
    {
        $request = $this->buildRequest($message, $linkUrl);

        try {
            $response = $this->httpClient->send($request);
        } catch (GuzzleException $exception) {
            return PushNotificationResponse::error(
                sprintf(
                    'An error occurred while sending the push notification: %s',
                    $exception->getMessage()
                )
            );
        }

        $responseBody = (string)$response->getBody();
        if (strpos($responseBody, 'Congratulations') === false) {
            return PushNotificationResponse::error(
                sprintf(
                    'Unsuccessful response for sending the push notification: %s',
                    $responseBody
                )
            );
        }

        return PushNotificationResponse::success();
    }

    /**
     * @param string $message
     * @param string $linkUrl
     *
     * @return Request
     */
    private function buildRequest($message, $linkUrl)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.183 Safari/537.36',
        ];

        $requestData = [
            'value1' => $message,
        ];
        if ($linkUrl !== '') {
            $requestData['value2'] = $linkUrl;
        }

        $body = json_encode($requestData);

        return new Request('POST', $this->getWebhookUrl(), $headers, $body);
    }

    /**
     * @return string
     */
    private function getWebhookUrl()
    {
        $webhookUrl = (string)$this->config->push_notification->webhook_url;

        if (filter_var($webhookUrl, FILTER_VALIDATE_URL) === false) {
            throw new RuntimeException(
                sprintf('The webhook URL provided in the config ini file is not valid: %s', $webhookUrl)
            );
        }

        return $webhookUrl;
    }
}
