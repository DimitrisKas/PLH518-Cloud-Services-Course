<?php

namespace Models\Mongo;

use RestAPI\Result;
use RestAPI\iRestObject;
use Models\Generic\Cinema;
use MongoDB\BSON\ObjectId;

/**
 * Class CinemaM extends Generic Cinema Class
 * @package Models\Mongo
 */
class CinemaM extends Cinema implements iRestObject
{
    public const COLL_NAME = "Cinemas";

    public function __construct($doc, $owner)
    {
        parent::__construct($doc, $owner);
    }

    /** Adds Movie to database if name is unique
     * @param $obj Cinema
     * @return Result Result object with success boolean and a message
     */
    public static function addOne($obj): Result
    {
        logger("Creating Cinema... ");

        if ($obj instanceof Cinema == false)
            return Result::withLogMsg(false, "Invalid Object argument given.");

        if (empty($obj->name))
            return Result::withLogMsg(false, "Name was empty.");

        if (empty($obj->owner))
            return Result::withLogMsg(false, "Owner was empty.");

        // If Cinema Name already exists
        if (self::searchByName($obj->name) == true)
            return Result::withLogMsg(false, "Cinema with same name already exists");

        // Create New Cinema
        $db = connect();
        $coll = $db->selectCollection(CinemaM::COLL_NAME);
        $insertResult = $coll->insertOne($obj);

        if ($insertResult->isAcknowledged())
        {
            if ($insertResult->getInsertedCount() != 1)
            {
                return Result::withLogMsg(false, "Couldn't insert cinema with name: " . $obj->name);
            }
        }

        return Result::withLogMsg(true, "Cinema ".$obj->name." successfully  created");
    }

    /**
     * Search for a single cinema based on name.
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
     * Get a single cinema based on given id
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
            return Result::withLogMsg(false, "Invalid Object argument given.");

        logger("Editing Cinema...");
        if (empty($id))
            return new Result("Empty id", false);

        logger("With id: ". $id);

        $db = connect();
        $coll = $db->selectCollection(CinemaM::COLL_NAME);
        $updateResult = $coll->updateOne(
            ['_id' => new ObjectId($id)],
            ['$set'=> [
                'name' => $obj->name,
            ]]
        );

        logger("Cinema to edit: ". var_export($obj, true));

        if ($updateResult->isAcknowledged())
        {
            logger("Matched Count: " . $updateResult->getMatchedCount());
            logger("Modified Count: " . $updateResult->getModifiedCount());
            if ($updateResult->getMatchedCount() != 1)
                return Result::withLogMsg(false, "Couldn't find Cinema with id: " . $id);

            else if ($updateResult->getModifiedCount() != 1)
                return Result::withLogMsg(false, "Nothing to edit or couldn't edit Cinema with id: " . $id);
        }

        return new Result("Success Editing Cinema", true);

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

        // Keep for cascading deletions later
        $cinema_name = self::getone($id)->name;

        $db = connect();
        $coll = $db->selectCollection(CinemaM::COLL_NAME);
        $deleteResult = $coll->deleteOne([
            '_id' => new ObjectId($id)
        ]);

        if ($deleteResult->getDeletedCount() != 1)
        {
            return Result::withLogMsg(false, "Couldn't find Cinema with id: " . $id);
        }

        // Delete movies from this cinema
        $cursor = $db
                ->selectCollection(MovieM::COLL_NAME)
                ->find(['cinema_name' => $cinema_name]);

        /* Delete each one through the deleteOne function of its class
           Note: We could alternatively immediately delete the corresponding
                 movie documents from the above query, but this way we can allow
                 the MovieM class to cascade the deletion of corresponding favorites
                 on each movie.
        */
        foreach($cursor as $movie_doc)
        {
            MovieM::deleteOne($movie_doc['_id']->__toString());
        }


        return Result::withLogMsg(true, "");
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