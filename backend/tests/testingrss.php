
<?php

require __DIR__ . '/vendor/autoload.php';


use App\Utils\ClientFactory;
use Monolog\Logger;
use App\Domain\Feed\FeedFetcher;

use GuzzleHttp\RequestOptions;


$logHandlers = [new ErrorLogHandler()];
$logger = new Logger("Forecast", $logHandlers);

#$cf = new ClientFactory($logger, []);
#$client = $cf->create(); 
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

$onRedirect = function (
    RequestInterface $request,
    ResponseInterface $response,
    UriInterface $uri
) {
    echo 'Redirecting! ' . $request->getUri() . ' to ' . $uri . "\n";
};
$client = new \GuzzleHttp\Client([
    'allow_redirects' => [
        'max'             => 10,        // allow at most 10 redirects.
        'strict'          => true,      // use "strict" RFC compliant redirects.
        'referer'         => true,      // add a Referer header
        'protocols'       => ['https'], // only allow https URLs
        'on_redirect'     => $onRedirect,
        'track_redirects' => true
    ]
]);

$response = $client->get("https://bedroomproducersblog.com/rss");

$feedIo = new \FeedIo\FeedIo(new \App\FeedIo\Adapter\Http\Client($client), $logger);
$fetcher = new FeedFetcher($logger, $feedIo);
$response = $fetcher->fetch("https://bedroomproducersblog.com/rss");

print_r($response);

