<?php

namespace Models\Mongo;

use RestAPI\iRestObject;
use Models\Generic\User;

/**
 * Class User
 * @package Models
 * @implements User
 */
class UserM extends User implements iRestObject {

    public function __construct($obj) {
        parent::__construct($obj);
    }

    /**
     * @param $obj User
     * @return bool TRUE on success, FALSE otherwise
     */
    public static function addOne($obj): bool
    {
        if (empty($obj->username))
        {
            logger("[USER_DB] Username was empty.");
            return false;
        }
        if (empty($obj->password))
        {
            logger("[USER_DB] Password was empty.");
            return false;
        }

        $db = connect();
        $coll = $db->selectCollection("Users");
        $coll->insertOne($obj);
        return true;
    }

    public static function getOne(string $id): User
    {

    }

    public static function updateOne(string $id): bool
    {

    }


    public static function deleteOne(string $id): bool
    {

    }

    public static function getAll(): array
    {

    }
}