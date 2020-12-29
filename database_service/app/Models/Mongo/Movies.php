<?php

namespace Models\Mongo;

use RestAPI\Result;
use RestAPI\iRestObject;
use Models\Generic\Movie;
use MongoDB\BSON\ObjectId;
use Slim\Exception\HttpMethodNotAllowedException;
use function PHPUnit\Framework\containsEqual;


/**
 * Class MovieM extends Generic Movie Class
 * @package Models\Mongo
 */
class MovieM extends Movie
{
    public const COLL_NAME = "Movies";

    public function __construct($doc)
    {
        parent::__construct($doc);
    }

    /** Adds Movie to database
     * @param $obj Movie
     * @return Result Result object with success boolean and a message
     */
    public static function addOne($obj): Result
    {
        logger("Creating Movie... ");

        if ($obj instanceof Movie == false)
            return Result::withLogMsg(false, "Invalid Object argument given.");

        if (empty($obj->title))
            return Result::withLogMsg(false, "Title was empty.");

        if (empty($obj->cinema_name))
            return Result::withLogMsg(false, "Cinema Name was empty.");

        // Create New User
        $db = connect();
        $coll = $db->selectCollection(MovieM::COLL_NAME);
        $insertResult = $coll->insertOne($obj);

        if ($insertResult->isAcknowledged())
        {
            if ($insertResult->getInsertedCount() != 1)
            {
                return Result::withLogMsg(false, "Couldn't insert Movie with name: " . $obj->title);
            }
        }

        return Result::withLogMsg(true, "Movie ".$obj->title." at cinema ".$obj->cinema_name." successfully  created");
    }

    /**
     * Search for movies based on name.
     * @param string $name Name to base search on
     * @param bool $searchOne If set to true, returns only first result
     * @return array Returns array of Movie objects if found, empty array otherwise
     */
    public static function searchByName(string $name, bool $searchOne): array
    {
        $db = connect();
        $coll = $db->selectCollection(MovieM::COLL_NAME);
        $cursor = $coll->find(['name' => $name]);

        $movies = array();
        $i = 0;
        foreach($cursor as $movie_doc)
        {
            $movies[$i++] = new Movie($movie_doc);

            if ($searchOne)
                break;
        }

        logger("Found ${i} Movies with the name: ${name}");
        return $movies;
    }

    /**
     * Search for movies whose name is similar to the one given
     * @param string $name Name to base search on
     * @return array Returns array of Movie objects if found, empty array otherwise
     */
    public static function searchSimilarToName(string $name): array
    {
        $db = connect();
        $coll = $db->selectCollection(MovieM::COLL_NAME);
        $cursor = $coll->find(
            ['name' =>
                ['$regex' => $name ]
            ]);

        $movies = array();
        $i = 0;
        foreach($cursor as $movie_doc)
        {
            $movies[$i++] = new Movie($movie_doc);
        }

        logger("Found ${i} Movies with the name: ${name}");
        return $movies;
    }


    /**
     * Get a single Movie based on given id
     * @param string $id
     * @return Movie|false Returns Movie object on success, false othewise
     */
    public static function getOne(string $id): Movie|false
    {
        $db = connect();
        $coll = $db->selectCollection(MovieM::COLL_NAME);
        $movie_doc = $coll->findOne(['_id' => new ObjectId($id)]);

        if ($movie_doc == null )
        {
            logger("Couldn't find Movie with id: " . $id);
            return false;
        }

        return new Movie($movie_doc);
    }


    /**
     * Update a single Movie based on Movie object given
     * @param string $id Movie's ID
     * @param Movie $obj Movie's data
     * @return Result Result object with success boolean and a message
     */
    public static function updateOne(string $id, $obj): Result
    {
        if ($obj instanceof Movie == false)
            return Result::withLogMsg(false, "Invalid Object argument given.");

        logger("Editing Movie...");
        if (empty($id))
            return new Result("Empty id", false);

        logger("With id: ". $id);

        $db = connect();
        $coll = $db->selectCollection(MovieM::COLL_NAME);
        $updateResult = $coll->updateOne(
            ['_id' => new ObjectId($id)],
            ['$set'=> [
                'title' => $obj->title,
                'start_date' => $obj->start_date,
                'end_date' => $obj->end_date,
                'cinema_name' => $obj->cinema_name,
                'category' => $obj->category,
            ]]
        );

        logger("Movie to edit: ". var_export($obj, true));

        if ($updateResult->isAcknowledged())
        {
            logger("Matched Count: " . $updateResult->getMatchedCount());
            logger("Modified Count: " . $updateResult->getModifiedCount());
            if ($updateResult->getMatchedCount() != 1)
                return Result::withLogMsg(false, "Couldn't find Movie with id: " . $id);

            else if ($updateResult->getModifiedCount() != 1)
                return Result::withLogMsg(false, "Nothing to edit or couldn't edit Movie with id: " . $id);
        }

        return new Result("Success Editing Movie", true);

    }

    /**
     * Delete a single Movie with given id
     * @param string $id
     * @return Result Result object with success boolean and a message
     */
    public static function deleteOne(string $id): Result
    {
        if (empty($id))
            return new Result("Empty id", false);

        $db = connect();
        $coll = $db->selectCollection(MovieM::COLL_NAME);
        $deleteResult = $coll->deleteOne([
            '_id' => new ObjectId($id)
        ]);

        if ($deleteResult->isAcknowledged())
        {
            if ($deleteResult->getDeletedCount() != 1)
            {
                return Result::withLogMsg(false, "Couldn't find Movie with id: " . $id);
            }
        }

        return Result::withLogMsg(true, "");
    }

    /**
     * Get all Movies and tag the favorites of given user
     * @param $user_id string User's id of which we want the favorites
     * @return array An array with all movies as Movie objects
     */
    public static function getAll($user_id): array
    {
        $db = connect();
        $cursor = $db
            ->selectCollection(MovieM::COLL_NAME)
            ->aggregate([
                [
                    '$addFields' => [
                        'strID' => [
                            '$toString'=> '$_id'
                        ]
                    ]
                ],
                [
                    '$lookup' => [
                        'from' => UserM::COLL_NAME,
                        'let' => [ 'm_id' => '$_id'],
                        'pipeline' => [
                            [
                                '$match' => ['_id' => new ObjectId($user_id)]
                            ],
                            [
                                '$unwind' => '$favorites'
                            ],
                            [
                                '$project' => [
                                    'favorites' => 1
                                ]
                            ],
                            [
                                '$match' => [
                                    '$expr' => [
                                        '$eq' => [
                                            '$user.favorites',
                                            '$strID'
                                        ]
                                    ]
                                ]
                            ],
                        ],
                        'as' => 'user'
                    ]
                ],
                [
                    '$addFields' => [
                        'isFavorite' => [
                            '$cond' => [
                                'if' => [ '$in' => [ '$strID' , '$user.favorites' ]],
                                'then' => 'true',
                                'else' => 'false'
                            ]
                        ]
                    ]
                ]
            ]);

        $movies = array();
        $i = 0;
        foreach($cursor as $movie_doc)
        {
            $movies[$i++] = new Movie($movie_doc);
        }

        return $movies;
    }

    /**
     * Get all current onwer's Movies
     * @param string @owner Owner's id of whom we want to retrieve his movies
     * @return array An array with all cinemas as Movie objects
     */
    public static function getAllOwned(string $owner_id): array
    {
        $db = connect();
        $cursor = $db
            ->selectCollection(MovieM::COLL_NAME)
            ->aggregate([

                [
                    '$lookup' => [
                        'from' => CinemaM::COLL_NAME,
                        'let' => [ 'c_name' => '$cinema_name'],
                        'pipeline' => [
                            [
                                '$match' => [
                                    '$expr' => [
                                        '$and' => [
                                            ['$eq' => [ '$name', '$$c_name' ]]
                                        ]
                                    ]
                                ]
                            ],
                            [
                                '$project' => ['owner' => 1]
                            ]
                        ],
                        'as' => 'cinema_info',
                    ]
                ],
                [
                    '$match' => [
                        'cinema_info.owner' => $owner_id
                    ]
                ],
            ]);


        $movies = array();
        $i = 0;
        foreach($cursor as $movie_doc)
        {
            $movies[$i++] = new Movie($movie_doc);
        }

        return $movies;
    }

}