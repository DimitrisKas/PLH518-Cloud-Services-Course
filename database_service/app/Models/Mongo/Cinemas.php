<?php

namespace Models\Mongo;

use http\Exception;
use RestAPI\Result;
use RestAPI\iRestObject;
use Models\Generic\Cinema;
use MongoDB\BSON\ObjectId;

/**
 * Class User
 * @package Models
 * @implements User
 */
class CinemaM extends Cinema implements iRestObject
{
    private const COLL_NAME = "Cinemas";

    public function __construct($doc, $owner)
    {
        parent::__construct($doc, $owner);
    }

    /** Adds user to database if username is unique
     * @param $obj Cinema
     * @return Result Result object with success boolean and a message
     */
    public static function addOne($obj): Result
    {
        logger("Creating Cinema... ");

        if ($obj instanceof Cinema == false)
            return Result::withLogMsg("Invalid Object argument given.", false);

        if (empty($obj->name))
            return Result::withLogMsg("Name was empty.", false);

        if (empty($obj->owner))
            return Result::withLogMsg("Owner was empty.", false);

        // Create New User
        $db = connect();
        $coll = $db->selectCollection(CinemaM::COLL_NAME);
        $insertResult = $coll->insertOne($obj);

        if ($insertResult->isAcknowledged())
        {
            if ($insertResult->getInsertedCount() != 1)
            {
                return Result::withLogMsg("Couldn't insert cinema with name: " . $obj->name, false);
            }
        }

        return Result::withLogMsg("Cinema ".$obj->name." successfully  created", true);
    }

    /**
     * Search for a single cinema based on name.
     * Since cinema names are not unique, the first match will be returned
     * @param string $name Name to base search on
     * @return Cinema|false Returns Cinema object if found, false othewise
     */
    public static function searchByName(string $name): Cinema|false
    {
        $db = connect();
        $coll = $db->selectCollection(CinemaM::COLL_NAME);
        $cinema_doc = $coll->findOne(['name' => $name]);

        if ($cinema_doc == null )
        {
            logger("Couldn't find cinema with name: " . $name);
            return false;
        }

        logger("Cinema found!");
        return new Cinema($cinema_doc, $cinema_doc['owner']);
    }



    /**
     * Get a single user based on given id
     * @param string $id
     * @return Cinema|false Returns Cinema object on success, false othewise
     */
    public static function getOne(string $id): Cinema|false
    {
        $db = connect();
        $coll = $db->selectCollection(CinemaM::COLL_NAME);
        $cinema_doc = $coll->findOne(['_id' => new ObjectId($id)]);

        if ($cinema_doc == null )
        {
            logger("Couldn't find Cinema with id: " . $id);
            return false;
        }

        return new Cinema($cinema_doc);
    }

    /**
     * Update a single Cinema based on Cinema object given
     * @param string $id
     * @param Cinema $obj
     * @return Result Result object with success boolean and a message
     */
    public static function updateOne(string $id, $obj): Result
    {
        if ($obj instanceof Cinema == false)
            return Result::withLogMsg("Invalid Object argument given.", false);

        logger("Editing Cinema...");
        if (empty($id))
            return new Result("Empty id", false);

        logger("With id: ". $id);

        $db = connect();
        $coll = $db->selectCollection(CinemaM::COLL_NAME);
        $updateResult = $coll->updateOne(
            ['_id' => new ObjectId($id)],
            ['$set'=> [
                'username' => $obj->owner,
                'name' => $obj->name,
            ]]
        );

        logger("Cinema to edit: ". var_export($obj, true));

        if ($updateResult->isAcknowledged())
        {
            logger("Matched Count: " . $updateResult->getMatchedCount());
            logger("Modified Count: " . $updateResult->getModifiedCount());
            if ($updateResult->getMatchedCount() != 1)
                return Result::withLogMsg("Couldn't find Cinema with id: " . $id, false);

            else if ($updateResult->getModifiedCount() != 1)
                return Result::withLogMsg("Nothing to edit or couldn't edit Cinema with id: " . $id, false);
        }

        return new Result("Success Editing User", true);

    }

    /**
     * Delete a single Cinema with given id
     * @param string $id
     * @return Result Result object with success boolean and a message
     */
    public static function deleteOne(string $id): Result
    {
        if (empty($id))
            return new Result("Empty id", false);

        $db = connect();
        $coll = $db->selectCollection(CinemaM::COLL_NAME);
        $deleteResult = $coll->deleteOne([
            '_id' => new ObjectId($id)
        ]);

        if ($deleteResult->getDeletedCount() != 1)
        {
            return Result::withLogMsg("Couldn't find Cinema with id: " . $id, false);
        }
        else
            return Result::withLogMsg("", true);

    }

    /**
     * Get all cinemas
     * @return array An array with all cinemas as Cinema objects
     */
    public static function getAll(): array
    {
        $db = connect();
        $cursor = $db
            ->selectCollection(CinemaM::COLL_NAME)
            ->find();

        $cinemas = array();
        $i = 0;
        foreach($cursor as $cinema_doc)
        {
            $cinemas[$i++] = new Cinema($cinema_doc);
        }

        return $cinemas;
    }

    /**
     * Get all current onwers cinemas
     * @return array An array with all cinemas as Cinema objects
     */
    public static function getAllOwned(string $owner): array
    {
        $db = connect();
        $cursor = $db
            ->selectCollection(CinemaM::COLL_NAME)
            ->find([
                'owner' => $owner
            ]);

        $cinemas = array();
        $i = 0;
        foreach($cursor as $cinema_doc)
        {
            $cinemas[$i++] = new Cinema($cinema_doc, $owner);
        }

        return $cinemas;
    }

}