<?php

declare(strict_types=1);

namespace Iigau\Poster;

if (php_sapi_name() !== 'cli') {
    exit;
}

require __DIR__ . "/../vendor/autoload.php";


use Discord\Builders\CommandBuilder;
use Discord\Discord;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Interactions\Command\Command;
use Discord\Parts\Interactions\Interaction;
use Discord\WebSockets\Event;
use Discord\WebSockets\Intents;
use Iigau\Poster\Forecast\Place;
use Iigau\Poster\Forecast\Utils;
use Monolog\Logger;
use React\EventLoop\Loop;
use React\Promise\ExtendedPromiseInterface;

use function Iigau\Poster\Forecast\buildMessage;

class Forecast
{
    readonly Place $forecast;

    function __construct(array $cliArgs)
    {
        $prog = $cliArgs[0];

        $help = <<<EOM
        Usage: $prog -d|--db DATABASE [-f|--force] [-v|--verbose] [-h|--help]

          -f, --force     Invalidate cache and force update
          -d, --db        Command line flag to specify database
          -v, --verbose   Show debug message
          -h, --help      Show this message and exit

        EOM;

        $opt = getopt("d:fvh", ['db:', 'force', 'verbose', 'help']);

        // var_dump($opt);
        $isHelp = isset($opt['h']) || isset($opt['help']);
        if ($isHelp) {
            echo "$help";
            exit(0);
        }

        $isForced = isset($opt['f']) || isset($opt['force']);
        $db = $opt['d'] ?? $opt['db'] ?? null;
        if (is_null($db)) {
            echo "$help";
            die("Please specify --db option.\n");
        }
        $isVerbose = isset($opt['v']) || isset($opt['verbose']);

        if ($isForced) {
            echo "force update enabled.\n";
        }

        $logLevel = $isVerbose ? \Monolog\Level::Debug : \Monolog\Level::Warning;
        $stream = Utils::prepareStreamHandler($logLevel);
        $logger = new Logger("Forecast", [$stream]);

        $pdo = Utils::preparePdo($db);

        $botToken = Utils::getSettingValue($pdo, 'bot_token');
        $discord = new Discord([
            'token' => $botToken,
            'intents' => Intents::getDefaultIntents()
                | Intents::GUILD_MEMBERS
                | Intents::MESSAGE_CONTENT,
            'loop' => Loop::get(),
            'logger' => $logger
        ]);

        $headers = [
            'Content-Type' => 'application/json'
        ];
        $client = Utils::prepareHttpClient($headers);

        $this->forecast = Place::create($client, $discord, $logger, $pdo, $isForced);
    }

    function run()
    {
        $discord = $this->forecast->getDiscord();
        $forecast = $this->forecast;

        $discord->on('init', function (Discord $discord) use ($forecast) {
            $loop = $discord->getLoop();

            $commands = [
                ['name' => "forecast", 'description' => '天気予報をその場で取得します。',],
                ['name' => "ping", 'description' => 'Bot に ping を送信します。',],
            ];
            foreach ($commands as $command) {
                $discord->application->commands->save(new Command($discord, $command));
            }

            $loop->addPeriodicTimer(1.0, function () use ($discord, $forecast): ExtendedPromiseInterface | false {
                if (Utils::isCurrentHour(6) || Utils::isCurrentHour(18)) {
                    $builder = buildMessage($forecast);
                    $attributes = [
                        "id" => $forecast->channelId,
                    ];
                    $channel = new Channel($discord, $attributes);

                    return $channel->sendMessage($builder);
                }
                return false;
            });

            $discord->on(Event::MESSAGE_CREATE, function (Message $message) use ($forecast) {
                $forecast->getLogger()->debug("{$message->author->username}: {$message->content}");
            });

            $discord->listenCommand("forecast", function (Interaction $interaction) use ($forecast): ExtendedPromiseInterface {
                $builder = buildMessage($forecast);

                return $interaction->respondWithMessage($builder);
            });

            // $discord->listenCommand("ping", function (Interaction $interaction) use ($forecast): ExtendedPromiseInterface {
            //     return $interaction->respondWithMessage($builder);
            // });
        });

        $discord->run();
    }
}
