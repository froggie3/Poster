<?php

namespace App\DB;

use App\Utils\Others;

class InstanceManager
{
    public static $choices = [
        0 => "add",
        1 => "modify",
        2 => "remove",
        3 => "print",
        4 => "q"
    ];

    public function __construct()
    {
    }

    public function open(callable $callback): void
    {
        $callback();
    }

    public static function add()
    {
        [$url] = sscanf(trim(fgets(STDIN)), "%s\n");
        [$name] = sscanf(trim(fgets(STDIN)), "%s\n");
        [$type] = sscanf(trim(fgets(STDIN)), "%d\n");
        return [$url, $name, $type];
    }

    public static function modify()
    {
        [$uuid] = sscanf(trim(fgets(STDIN)), "%s\n");
        [$url] = sscanf(trim(fgets(STDIN)), "%s\n");
        [$type] = sscanf(trim(fgets(STDIN)), "%d\n");
        return [$uuid, $url, $type];
    }

    public static function remove()
    {
        [$uuid] = sscanf(trim(fgets(STDIN)), "%s\n");
        return [$uuid];
    }

    public function webhook(): callable
    {
        return function (): void {
            // opens an instance of webhook manager
            $webHookManager = new WebHookManager();

            echo "choose one from available command below!" . PHP_EOL;
            Others::displayChoices(self::$choices);

            // gets user input
            $input = Others::getInputInRange(0, count(self::$choices));

            switch ($input) {
                case 0:
                    echo "Enter a places you like to add." . PHP_EOL;
                    $tuple = self::add();
                    $webHookManager->add(...$tuple);
                    break;

                case 1:
                    echo "Enter a places you like to edit." . PHP_EOL;
                    $tuple = self::modify();
                    $webHookManager->modify(...$tuple);
                    break;

                case 2:
                    echo "Enter a places you like to remove." . PHP_EOL;
                    $tuple = self::remove();
                    $webHookManager->remove(...$tuple);
                    break;

                case 3:
                    $webHookManager->print();
                    break;

                default:
                    echo "Undefined error" . PHP_EOL;
                    exit(1);
                    break;
            }
        };
    }
}
