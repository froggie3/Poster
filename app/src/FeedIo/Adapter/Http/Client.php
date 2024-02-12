<?php

declare(strict_types=1);

namespace App\FeedIo\Adapter\Http;

use FeedIo\Adapter\ResponseInterface;
use FeedIo\Adapter\ServerErrorException;

// https://github.com/alexdebril/feed-io/issues/406#issuecomment-1511371152

class Client extends \FeedIo\Adapter\Http\Client
{
    protected function request(string $method, string $url, \DateTime $modifiedSince = null): ResponseInterface
    {
        try {
            return parent::request($method, $url, $modifiedSince);
        } catch (ServerErrorException $e) {
            $psrResponse = $e->getResponse();
            if (in_array($psrResponse->getStatusCode(), [301, 302], true)) {
                return parent::request($method, $psrResponse->getHeader("location")[0], $modifiedSince);
            }

            throw $e;
        }
    }
}
