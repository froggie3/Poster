<?php

namespace App\DB;

use App\DB\Database;

class WebHookManager extends Database
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Regist a Webhook URL with a desired name to the database
     * Returns error, if type is invalid, 
     * else returns $uuid
     */
    public function add(string $url, string $name, int $type): bool
    {
        if ($type != 1) {
            return false;
        }
        $uuid = uniqid();
        return $uuid;
    }

    /**
     * Change the previous URL assigned to the place (managed by UUID) in the database to $url
     * if $uuid does not exist in the database, return error
     */
    public function modify(string $uuid, string $url, int $type): bool
    {
        return false;
    }

    /**
     * if $uuid is not found, return error 
     */
    public function remove(string $uuid): bool
    {
        return false;
    }

    /**
     * look up the key-value pair; name & UUID in the database: 
     * if found, return its $uuid
     * else, return error
     */
    private function lookupUuidByName(string $name): string
    {
        return "";
    }

    /**
     * see what is inside in the database
     */
    public function print(): void
    {
    }
}
