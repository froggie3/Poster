<?php

declare(strict_types=1);

namespace Iigau\Poster;

if (php_sapi_name() !== 'cli') {
    exit;
}

require __DIR__ . "/../vendor/autoload.php";


use Discord\Discord;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Interactions\Interaction;
use Discord\WebSockets\Event;
use Discord\WebSockets\Intents;
use Iigau\Poster\Forecast\Place;
use Iigau\Poster\Forecast\Utils;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use React\EventLoop\Loop;
use React\Promise\ExtendedPromiseInterface;


class Forecast
{
    readonly Place $forecast;
    readonly Discord $discord;
    readonly LoggerInterface $logger;
    readonly array $availableCommands;

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
        $this->discord = new Discord([
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

        $this->logger = $logger;
        $this->forecast = new Place($client, $this->discord, $logger, $pdo, Utils::getPlaceId($pdo), Utils::getChannelId($pdo), $isForced);
    }

    protected function declareListeners()
    {
        $this->discord->listenCommand("forecast", function (Interaction $interaction): ExtendedPromiseInterface {
            $builder = Place::buildMessage($this->forecast);
            return $interaction->respondWithMessage($builder);
        });

        // $this->discord->listenCommand("ping", function (Interaction $interaction) : ExtendedPromiseInterface {
        //     return $interaction->respondWithMessage();
        // });
    }

    protected function updateGlobalCommands(\Discord\Repository\Interaction\GlobalCommandRepository $commands)
    {
        $cmdFiltered = $commands->map(function ($e) {
            $ary = ["id=$e->id", "type=$e->type", /* "guild_id=$e->guild_id",  */ "name=$e->name", "description=$e->description"];
            return implode(", ", $ary);
        });

        foreach ($cmdFiltered as $debugLine) {
            $this->logger->warning("global command is registered: ($debugLine)");
        }

        if ($bye = $commands->get('name', 'command')) {
            $commands->delete($bye->id);
        }
    }

    protected function updateGuildCommands(\Discord\Repository\Guild\GuildCommandRepository $commands)
    {
        foreach ($this->availableCommands as $command) {
            // if ($command = $commands->get('name', 'guild_command')) $commands->delete($command->id);
            if (! $commands->get('name', 'guild_command')) {
                $commands->save($command);
            }
        }
    }

    public function run(): void
    {
        $this->availableCommands =  [
            new \Discord\Parts\Interactions\Command\Command($this->discord, [
                'name' => "forecast",
                'description' => '天気予報をその場で取得します。'
            ]),
            new \Discord\Parts\Interactions\Command\Command($this->discord, [
                'name' => "ping",
                'description' => 'Bot に ping を送信します。'
            ]),
        ];

        $this->discord->on('init', function (Discord $discord) {
            /**
             * @see https://discordapp.com/channels/115233111977099271/234582138740146176/1182697283364593766 
             */
            $discord
                ->application
                ->commands
                ->freshen()
                ->done(function (\Discord\Repository\Interaction\GlobalCommandRepository $commands): void {
                    $this->updateGlobalCommands($commands);
                });


            // Guildsのコマンドは即時反映されるので
            $guildIds = $this->discord->guilds->map(fn($item) => $item->id);

            foreach ($guildIds as $guildId) {
                $discord
                    ->guilds
                    ->get('id', $guildId)
                    ->commands
                    ->freshen()
                    ->done(function (\Discord\Repository\Guild\GuildCommandRepository $commands): void {
                        $this->updateGuildCommands($commands);
                    });
                $this->logger->debug("registered commands for the guild $guildId");
            }

            $this->declareListeners();
        });

        $this->discord->run();

        $loop = $this->discord->getLoop();

        $loop->addPeriodicTimer(1.0, function (): ExtendedPromiseInterface | false {
            if (Utils::isCurrentHour(6) || Utils::isCurrentHour(18)) {
                $builder = Place::buildMessage($this->forecast);
                $attributes = [
                    "id" => $this->forecast->channelId,
                ];
                $channel = new Channel($this->discord, $attributes);

                return $channel->sendMessage($builder);
            }
            return false;
        });

        $this->discord->on(Event::MESSAGE_CREATE, function (Message $message) {
            $msg = "{$message->author->username}: {$message->content}";
            $this->logger->debug($msg);
        });
    }
}
