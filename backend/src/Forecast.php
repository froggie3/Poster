<?php

declare(strict_types=1);

namespace Iigau\Poster;

if (php_sapi_name() !== 'cli') {
    exit;
}

require __DIR__ . "/../vendor/autoload.php";


use Discord\Discord;
use Discord\WebSockets\Event;
use Discord\WebSockets\Intents;
use Iigau\Poster\Cli\CliParser;
use Iigau\Poster\Cli\CliParseResult;
use Iigau\Poster\Forecast\Place;
use Iigau\Poster\Forecast\Utils;
use Psr\Log\LoggerInterface;
use React\EventLoop\Loop;
use React\Promise\ExtendedPromiseInterface;


class Forecast
{
    readonly Place $forecast;
    readonly Discord $discord;
    readonly LoggerInterface $logger;
    readonly array $availableCommands;
    readonly array $channels;

    function __construct(array $cliArgs)
    {
        $opt = getopt("d:fvh", ['db:', 'force', 'verbose', 'help']);
        $cli = new CliParser($cliArgs, $opt);

        $logLevel = $cli->isVerbose ? \Monolog\Level::Debug : \Monolog\Level::Warning;
        $stream = Utils::prepareStreamHandler($logLevel);
        $pdo = Utils::preparePdo($cli->db);
        $botToken = Utils::getSettingValue($pdo, 'bot_token');
        $this->discord = new Discord([
            'token' => $botToken,
            'intents' => Intents::getDefaultIntents(),
            'loop' => Loop::get(),
            'logger' => new \Monolog\Logger("Forecast", [$stream])
        ]);
        $headers = [
            'Content-Type' => 'application/json'
        ];
        $client = Utils::prepareHttpClient($headers);

        $this->logger = $this->discord->getLogger();

        $this->channels = array_map(function ($item) {
            $channel = new \Discord\Parts\Channel\Channel(
                $this->discord,
                ["id" => $item,]
            );
            return $channel;
        }, Utils::getChannels($pdo));

        $this->forecast = new Place($client, $this->discord, $pdo, Utils::getPlaceId($pdo), Utils::getChannelId($pdo), $cli->isForced);
    }



    protected function declareListeners()
    {
        $this->discord->listenCommand("forecast", function (\Discord\Parts\Interactions\Interaction $interaction): ExtendedPromiseInterface {
            $builder = Place::buildMessage($this->forecast);
            return $interaction->respondWithMessage($builder);
        });
    }

    /**
     * グローバルコマンドを全削除する
     */
    protected function dropGlobalCommands(\Discord\Repository\Interaction\GlobalCommandRepository $commands)
    {
        $cmdFiltered = $commands->map(function ($e) {
            $ary = "id=$e->id, type=$e->type, /* guild_id=$e->guild_id,  */ name=$e->name, description=$e->description";
            return [$e->id => $ary];
        });

        foreach ($cmdFiltered as $id => $debugLine) {
            $this->logger->warning("global command is registered: ($debugLine), removing");
            $commands->delete($id);
        }
    }


    protected function updateGlobalCommands(\Discord\Repository\Interaction\GlobalCommandRepository $commands)
    {
        // $this->dropGlobalCommands($commands);
    }

    /**
     * Guild コマンドを更新する
     */
    protected function updateGuildCommands(\Discord\Repository\Guild\GuildCommandRepository $commands)
    {
        foreach ($this->availableCommands as $command) {
            // if ($command = $commands->get('name', 'guild_command')) $commands->delete($command->id);
            if (! $commands->get('name', 'guild_command')) {
                $commands->save($command);
            }
        }
    }

    /**
     * 実行関数
     * 
     * on('init') 部分の実装についてはリンク先を参照
     * @see https://discordapp.com/channels/115233111977099271/234582138740146176/1182697283364593766 
     */
    public function run(): void
    {
        $this->availableCommands =  [
            new \Discord\Parts\Interactions\Command\Command($this->discord, [
                'name' => "forecast",
                'description' => '天気予報をその場で取得します。'
            ]),
        ];

        $this->discord->on('init', function (Discord $discord) {
            $discord
                ->application
                ->commands
                ->freshen()
                ->done(function (\Discord\Repository\Interaction\GlobalCommandRepository $commands): void {
                    $this->updateGlobalCommands($commands);
                });

            // Guildsのコマンドは即時反映されるのでとりあえずこっちを使う方針で
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
                foreach ($this->channels as $channel) {
                    $builder = Place::buildMessage($this->forecast);
                    return $channel->sendMessage($builder);
                }
            }
            return false;
        });
    }
}
