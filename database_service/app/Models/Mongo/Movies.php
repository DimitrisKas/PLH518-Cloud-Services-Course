<?php

namespace Models\Mongo;

use RestAPI\Result;
use Models\Generic\Movie;
use Models\Generic\User;
use MongoDB\BSON\ObjectId;


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

    /** Adds Movie to database. Returns the Movie's new ID on success as the Result->msg
     * @param $obj Movie
     * @return Result Result object with success boolean and a message. On success $msg is the new ID
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
            else
            {
                $new_id = $insertResult->getInsertedId()->__toString();
                return Result::withLogMsg(true, $new_id);
            }
        }

        return Result::withLogMsg(false, "Undefined error");

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
     * @param string $user_k_id User's keystore id of which we want the favorites
     * @param array $params Search parameters to base search on
     * @return array Returns array of Movie objects if found, empty array otherwise
     */
    public static function searchSimilarToName(string $user_k_id, array $params): array
    {
        return self::getAll($user_k_id, $params);
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
     * Get all Movies and tag the favorites of given user
     * @param string $user_k_id  User's id of which we want the favorites
     * @param array | null $search_term Optional parameter based on which to search for movies
     * @return array An array with all movies as Movie objects
     */
    public static function getAll(string $user_k_id, array $search_params = null): array
    {

        $db = connect();

        $op_1 = ['$addFields' => [
                    'strID' => [
                        '$toString'=> '$_id'
                    ]
                ]
            ];

        $op_2 = ['$lookup' => [
                    'from' => UserM::COLL_NAME,
                    'let' => [ 'm_id' => '$_id'],
                    'pipeline' => [
                        [ '$match' => [
                                'k_id' => $user_k_id
                            ]
                        ],
                        ['$unwind' => '$favorites' ],
                        ['$project' => [
                                'favorites' => 1
                            ]
                        ],
                        ['$match' => [
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
            ];

        $op_3 = ['$addFields' => [
                    'isFavorite' => [
                        '$cond' => [
                            'if' => [ '$in' => [ '$strID' , '$user.favorites' ]],
                            'then' => 'true',
                            'else' => 'false'
                        ]
                    ]
                ]
            ];

        $coll = $db->selectCollection(MovieM::COLL_NAME);

        if ( $search_params == null )
        {
            $cursor = $coll->aggregate([$op_1, $op_2, $op_3]);
        }
        else
        {
            logger("Searching!");
            // Also searching for certain movies
            $date = self::isSetAndNonEmpty($search_params, 'date');

            if (empty($date))
            {
                $date = '0001-01-01';
                $noDateSearch = true;
            }
            else
            {
                $noDateSearch = false;
            }

            $add_date_op = [
                '$addFields' => [
                    'dt_start' => [
                        '$dateFromString'=> [
                            'dateString' => '$start_date',
                        ]
                    ],
                    'dt_end' =>[
                        '$dateFromString'=> [
                            'dateString' => '$end_date',
                        ]
                    ],
                    'dt_search' =>[
                        '$dateFromString'=> [
                            'dateString' => $date
                        ]
                    ],
                ]
            ];

            $search_op = [
                '$match' => [
                    'title' => ['$regex' => self::isSetAndNonEmpty($search_params, 'title')],
                    'cinema_name' => ['$regex' => self::isSetAndNonEmpty($search_params, 'cin_name')],
                    'category' => ['$regex' => self::isSetAndNonEmpty($search_params, 'cat')],
                    '$expr' => [
                        '$or' => [
                            ['$eq' => [ ['$toBool' => 'true'], ['$toBool' => $noDateSearch] ]],
                            [ '$and' => [
                                ['$lte' => [ '$dt_start', '$dt_search']],
                                ['$gte' => [ '$dt_end', '$dt_search']],
                            ]]
                        ]
                    ]
                ]
            ];

            $cursor = $coll->aggregate([$op_1, $op_2, $op_3, $add_date_op, $search_op]);
        }


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
     * @param string $owner_k_id Owner's keystore id of whom we want to retrieve his movies
     * @return array An array with all cinemas as Movie objects
     */
    public static function getAllOwned(string $owner_k_id): array
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
                        'cinema_info.owner' => $owner_k_id
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
     * @param string $movie_id
     * @return Result Result object with success boolean and a message
     */
    public static function deleteOne(string $movie_id): Result
    {
        if (empty($movie_id))
            return new Result("Empty id", false);

        $db = connect();
        $coll = $db->selectCollection(MovieM::COLL_NAME);
        $deleteResult = $coll->deleteOne([
            '_id' => new ObjectId($movie_id)
        ]);

        if ($deleteResult->isAcknowledged())
        {
            if ($deleteResult->getDeletedCount() != 1)
            {
                return Result::withLogMsg(false, "Couldn't find Movie with id: " . $movie_id);
            }
        }

        // Delete any favorite movies
        $users = UserM::getAll();

        /** @var User $user */
        foreach ($users as $user)
        {
            UserM::removeFavorite($user->k_id, $movie_id);
        }

        return Result::withLogMsg(true, "");
    }


    /** Checks if $key in $arr is set and has non empty value
     * @param array $arr Array to check key against
     * @param string $key Key to check
     * @return object|false Returns $arr[$key] value if it exists, false otherwise
     */
    public static function isSetAndNonEmpty(array $arr, string $key): mixed
    {
            if (isset($arr[$key]))
            {
                $value = $arr[$key];
                if ( !empty($value) )
                {
                    return $value;
                }
            }

            return "";
    }
}