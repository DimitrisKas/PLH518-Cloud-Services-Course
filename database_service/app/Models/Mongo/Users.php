<?php

namespace Models\Mongo;

use RestAPI\Result;
use RestAPI\iRestObject;
use Models\Generic\User;
use MongoDB\BSON\ObjectId;

/**
 * Class User
 * @package Models
 * @implements User
 */
class UserM extends User implements iRestObject {

    public function __construct($doc) {
        parent::__construct($doc);
    }

    /** Adds user to database if username is unique
     * @param $obj User
     * @return Result Result object with success boolean and a message
     */
    public static function addOne($obj): Result
    {
        if (empty($obj->username))
            return Result::withLogMsg("Username was empty.", false);

        if (empty($obj->password))
            return Result::withLogMsg("Password was empty.", false);

        // If username already exists
        if (self::searchByUsername($obj->username) == true)
            return Result::withLogMsg("Username already exists", false);


        // Create New User
        $db = connect();
        $coll = $db->selectCollection("Users");
        $insertResult = $coll->insertOne($obj);

        if ($insertResult->getInsertedCount() != 1)
        {
            return Result::withLogMsg("Couldn't insert user with username: " . $obj->username, false);
        }

        return Result::withLogMsg("User ".$obj->username." successfully  created", true);
    }

    /**
     * Search for a single user based on username
     * @param string $username Username to base search on
     * @return User|false Returns user object if found, false othewise
     */
    public static function searchByUsername(string $username): User|false
    {
        $db = connect();
        $coll = $db->selectCollection("Users");
        $user_doc = $coll->findOne(['username' => $username]);

        if ($user_doc == null )
        {
            logger("Couldn't find user with username: " . $username);
            return false;
        }

        logger("User found!");
        return new User($user_doc);
    }

    /**
     * Get a single user based on given id
     * @param string $id
     * @return User|false Returns user object on succes, false othewise
     */
    public static function getOne(string $id): User|false
    {
        $db = connect();
        $coll = $db->selectCollection("Users");
        $user_doc = $coll->findOne(['_id' => new ObjectId($id)]);

        if ($user_doc == null )
        {
            logger("Couldn't find user with id: " . $id);
            return false;
        }

        return new User($user_doc);
    }

    /**
     * Update a single user based on User object given
     * @param string $id
     * @param User $obj
     * @return Result Result object with success boolean and a message
     */
    public static function updateOne(string $id, $obj): Result
    {
        $resultMain = new Result("", true);
        $resultPass = new Result("", true);

        logger("Editing user...");
        if (empty($id))
            return new Result("Empty id", false);

        logger("With id: ". $id);

        $db = connect();
        $coll = $db->selectCollection("Users");
        $updateResult = $coll->updateOne(
            ['_id' => new ObjectId($id)],
            ['$set'=> [
                'username' => $obj->username,
                'name' => $obj->name,
                'surname' => $obj->surname,
                'email' => $obj->email,
                'role' => $obj->role,
                'confirmed' => $obj->confirmed
            ]]
        );

        logger("User to edit: ". var_export($obj, true));

        if ($updateResult->isAcknowledged())
        {
            logger("Matched Count: " . $updateResult->getMatchedCount());
            logger("Modified Count: " . $updateResult->getModifiedCount());
            if ($updateResult->getMatchedCount() != 1)
                $resultMain = Result::withLogMsg("Couldn't find user with id: " . $id, false);

            else if ($updateResult->getModifiedCount() != 1)
                $resultMain =  Result::withLogMsg("Nothing to edit or couldn't edit user with id: " . $id, false);
        }

        if (!empty($obj->password)) {
            logger("Changing password...");
            $updateResult = $coll->updateOne(
                ['_id' => new ObjectId($id)],
                ['$set' => [
                    'password' => $obj->password
                ]]
            );

            if ($updateResult->isAcknowledged())
            {
                if ($updateResult->getMatchedCount() != 1)
                    $resultPass = Result::withLogMsg("Couldn't find user with id: " . $id, false);

                else if ($updateResult->getModifiedCount() != 1)
                    $resultPass = Result::withLogMsg("Couldn't edit password for user with id: " . $id, false);
            }
        }

        if ( !$resultMain->success && !$resultPass->success)
        {
            // Concat Messages for the part that failed
            $mainMsg = !$resultMain->success ? $resultMain->msg : "";
            $passMsg = !$resultPass->success ? $resultPass->msg : "";

            return new Result($mainMsg . $passMsg, false);
        }

        return new Result("Success Editing User", true);

    }

    /**
     * Delete a single user with given id
     * @param string $id
     * @return Result Result object with success boolean and a message
     */
    public static function deleteOne(string $id): Result
    {
        if (empty($id))
            return new Result("Empty id", false);

        $db = connect();
        $coll = $db->selectCollection("Users");
        $deleteResult = $coll->deleteOne([
            '_id' => new ObjectId($id)
        ]);

        if ($deleteResult->getDeletedCount() != 1)
        {
            return Result::withLogMsg("Couldn't find user with id: " . $id, false);
        }
        else
            return Result::withLogMsg("" . $id, true);

    }

    /**
     * Get all users
     * @return array An array with all users as User objects
     */
    public static function getAll(): array
    {
        $db = connect();
        $cursor = $db->selectCollection("Users")->find();

        $users = array();
        $i = 0;
        foreach($cursor as $user_doc)
        {
            $users[$i] = new User($user_doc);
            $i++;
        }

        return $users;
    }

    /**
     * Login a user based on given username and password
     * @param string $username
     * @param string $password
     * @return User|Result User object on success, Result object on failure with error msg
     */
    public static function Login(string $username, string $password): User|Result {
        $db = connect();
        $coll = $db->selectCollection("Users");
        $user_doc = $coll->findOne([
            'username' => $username,
            'password' => $password
        ]);

        if ($user_doc == null )
            return Result::withLogMsg("Wrong credentials for user " . $username, false);

        logger("Successfully logged in user: " . $username);
        logger("User id: ", $user_doc['_id']->__toSTring());
        return new User($user_doc);
    }
}