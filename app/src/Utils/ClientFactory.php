<?php

declare(strict_types=1);

namespace App\Utils;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use Psr\Http\Message\RequestInterface;
use Monolog\Logger;
use GuzzleHttp\Client;
use App\Config;

class ClientFactory
{
    private array $headers;
    private Logger $logger;

    public function __construct(Logger $logger, array $headers = [])
    {
        $this->logger = $logger;
        $this->headers = $headers;
    }

    // Reference: https://docs.guzzlephp.org/en/stable/handlers-and-middleware.html#middleware
    public function create(): Client
    {
        $this->logger->debug('Configuring client');
        $stack = new HandlerStack();
        $stack->setHandler(new CurlHandler());

        foreach ($this->headers as $header => $value) {
            $stack->push($this->addHeader($header, $value));
            $this->logger->debug('Client header set', [$header => $value]);
        }

        return new Client([
            'timeout' => Config::CONNECTION_TIMEOUT_SECONDS,
            'handler' => $stack,
            'track_redirects' => true,
            'allow_redirects' => true,
        ]);
    }

    private function addHeader($header, $value)
    {
        return function (callable $handler) use ($header, $value) {
            return function (
                RequestInterface $request,
                array $options
            ) use ($handler, $header, $value) {
                $request = $request->withHeader($header, $value);
                return $handler($request, $options);
            };
        };
    }
}
