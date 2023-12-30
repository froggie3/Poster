<?php

namespace App\DB;

class PlaceManager extends Database
{
    public function add(string $placeId): bool
    {
        // if already exists, return error
        return false;
    }

    public function modify(string $placeId): bool
    {
        //if placeId is not found, return error
        return false;
    }

    public function remove(string $placeId): bool
    {
        // see what is inside in the database
        return false;
    }

    public function print(): void
    {
        //see what is inside in the database
    }
}
