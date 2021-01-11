<?php

use KeyrockAPI as K_API;

/**
 * Helper class containing all functions needed to communicated with the orion api
 */
class Orion_API
{

    const WilmaMK = "123456";
    const MOVIE_TYPE = "Movie";

    /** How many days a subscription will stay active for, in seconds */
    const DAYS_TO_LIVE = 1728000;


    /**
     *  Entity CRUD functions
     */


    /** Create Movie entity with given parameters on the Orion service
     * @param string $id Movie's ID that was created in the Mongo database of the DB Service
     * @param string $title
     * @param string $date_start
     * @param string $date_end
     * @param bool $isLive
     * @return bool
     */
    static function CreateMovieEntity(string $id, string $title, string $date_start, string $date_end, bool $isLive)
    {
        logger("Inserting Movie Entity to orion with id {$id}");
        $ch = curl_init();

        $fields = [
            'id' => $id,
            'type' => self::MOVIE_TYPE,
            'title' => [
                'value' => $title,
                'type' => 'Title'
            ],
            'date_start_sec' => [
                'value' => strtotime($date_start),
                'type' => 'Date'
            ],
            'date_end_sec' => [
                'value' => strtotime($date_end),
                'type' => 'Date'
            ],
            'isLive' => [
                'value' => $isLive ? "true" : "false",
                'type' => 'bool'
            ]
        ];

        curl_setopt($ch, CURLOPT_URL, "http://orion-proxy:1027/v2/entities");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "X-Auth-token: " . self::WilmaMK
        ));

        $response = curl_exec($ch);

        // Retrieve HTTP status code
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        logger("HTTP code: ". $http_code);

        // In case of error
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        curl_close($ch);

        // Parse results
        if ($http_code == 201)
            return true;

        else if ($http_code >= 400)
            logger("Response error. Code: " . $http_code . "\nMessage: " . var_export($response ,true));

        else if ($errno == 6)
            logger("Could not connect to keyrock service.");

        else if ($errno != 0 )
            logger("An error occured with cURL.\n\tError: ". $err . " .. errcode: " . $errno);

        return false;
    }

    static function UpdateMovieEntity(string $id, string $title, string $date_start, string $date_end, bool $isLive)
    {
        logger("Updating Movie Entity to orion with id {$id}");
        $ch = curl_init();

        $fields = [
            'title' => [
                'value' => $title,
                'type' => 'Title'
            ],
            'date_start_sec' => [
                'value' => strtotime($date_start),
                'type' => 'Date'
            ],
            'date_end_sec' => [
                'value' => strtotime($date_end),
                'type' => 'Date'
            ],
            'isLive' => [
                'value' => $isLive ? "true" : "false",
                'type' => 'bool'
            ]
        ];

        curl_setopt($ch, CURLOPT_URL, "http://orion-proxy:1027/v2/entities/{$id}/attrs");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "X-Auth-token: " . self::WilmaMK
        ));

        $response = curl_exec($ch);

        // Retrieve HTTP status code
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        logger("HTTP code: ". $http_code);

        // In case of error
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        curl_close($ch);

        // Parse results
        if ($http_code == 204)
            return true;

        else if ($http_code >= 400)
            logger("Response error. Code: " . $http_code);

        else if ($errno == 6)
            logger("Could not connect to keyrock service.");

        else if ($errno != 0 )
            logger("An error occured with cURL.\n\tError: ". $err . " .. errcode: " . $errno);

        return false;
    }

    static function UpdateDate(string $id, string $date_start, string $date_end)
    {
        logger("Updating Movie Entity to orion with id {$id}");
        $ch = curl_init();

        $fields = [
            'date_start_sec' => [
                'value' => strtotime($date_start),
                'type' => 'Date'
            ],
            'date_end_sec' => [
                'value' => strtotime($date_end),
                'type' => 'Date'
            ]
        ];

        curl_setopt($ch, CURLOPT_URL, "http://orion-proxy:1027/v2/entities/{$id}/attrs");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "X-Auth-token: " . self::WilmaMK
        ));

        $response = curl_exec($ch);

        // Retrieve HTTP status code
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        logger("HTTP code: ". $http_code);

        // In case of error
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        curl_close($ch);

        // Parse results
        if ($http_code == 204)
            return true;

        else if ($http_code >= 400)
            logger("Response error. Code: " . $http_code);

        else if ($errno == 6)
            logger("Could not connect to keyrock service.");

        else if ($errno != 0 )
            logger("An error occured with cURL.\n\tError: ". $err . " .. errcode: " . $errno);

        return false;
    }

    static function UpdateMovieIsLive(string $id, string $isLive)
    {
        logger("Updating Movie Entity to orion with id {$id}");
        $ch = curl_init();

        $fields = [
            'isLive' => [
                'value' => $isLive ? "true" : "false",
                'type' => 'bool'
            ]
        ];

        curl_setopt($ch, CURLOPT_URL, "http://orion-proxy:1027/v2/entities/{$id}/attrs");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "X-Auth-token: " . self::WilmaMK
        ));

        $response = curl_exec($ch);

        // Retrieve HTTP status code
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        logger("HTTP code: ". $http_code);

        // In case of error
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        curl_close($ch);

        // Parse results
        if ($http_code == 204)
            return true;

        else if ($http_code >= 400)
            logger("Response error. Code: " . $http_code . "\nMessage: " . var_export($response ,true));

        else if ($errno == 6)
            logger("Could not connect to keyrock service.");

        else if ($errno != 0 )
            logger("An error occured with cURL.\n\tError: ". $err . " .. errcode: " . $errno);

        return false;
    }

    /** Get Movie entity based on its ID
     * @param string $id Movie's id that was generated in DB Service's Mongo database
     * @return array Document array containing entity's orion data
     */
    static function GetMovieEntity(string $id): array
    {
        logger("Getting Movie Entity with id {$id}");
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "http://orion-proxy:1027/v2/entities/{$id}?options=keyValues");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "X-Auth-token: " . self::WilmaMK
        ));

        $response = curl_exec($ch);

        // Retrieve HTTP status code
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        logger("HTTP code: ". $http_code);

        // In case of error
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        curl_close($ch);

        // Parse results
        if ($http_code >= 200 && $http_code < 300)
            return json_decode($response, true);

        else if ($http_code >= 400)
            logger("Response error. Code: " . $http_code . "\nMessage: " . var_export($response ,true));

        else if ($errno == 6)
            logger("Could not connect to keyrock service.");

        else if ($errno != 0 )
            logger("An error occured with cURL.\n\tError: ". $err . " .. errcode: " . $errno);

        return array();
    }

    static function DeleteMovieEntity(string $id): bool
    {
        logger("Deleting Movie Entity on orion with id {$id}");
        $ch = curl_init();


        curl_setopt($ch, CURLOPT_URL, "http://orion-proxy:1027/v2/entities/{$id}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array( "X-Auth-token: ".self::WilmaMK ));

        $response = curl_exec($ch);

        // Retrieve HTTP status code
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        logger("HTTP code: ". $http_code);

        // In case of error
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        curl_close($ch);

        // Parse results
        if ($http_code == 204)
            return true;

        else if ($http_code >= 400)
            logger("Response error. Code: " . $http_code . "\nMessage: " . var_export($response ,true));

        else if ($errno == 6)
            logger("Could not connect to keyrock service.");

        else if ($errno != 0 )
            logger("An error occured with cURL.\n\tError: ". $err . " .. errcode: " . $errno);

        return false;
    }


    /**
     *  Subscription Functions
     */

    /** Subscribes current user for updates on given movie and date
     * @param string $user_id User's Keystore ID
     * @param string $movie_id Movie's MongoDB ID
     * @param string $date_of_interest Movie Live date for which the user is interested
     * @return bool Success boolean
     */

    static function Subscribe(string $user_id, string $movie_id, string $date_of_interest): bool
    {
        logger("Creating subscription for {$movie_id}");

        $sub_date_sec = strtotime($date_of_interest);

        $url_receive= "http://main-app/async/orion_receive.php";

        // Subscription's expiration date. (Today + 20 days) in Orion compatible time-format
        $exp_date = date('Y-m-d', time() + self::DAYS_TO_LIVE)."T00:00:00.00Z";

        logger("exp: " . $exp_date);

        $fields_movie_date = [
            'description' => "Checks for when a Movie date change. Subscription for movie {$movie_id} and user {$user_id}",
            'subject' => [
                'entities' => [
                    [
                        'id' => $movie_id,
                        'type' => self::MOVIE_TYPE,
                    ]
                ],
                'condition' => [
                    'attrs' => [
                        'date_start_sec',
                        'date_end_sec'
                    ],
                    'expression' => [
                        'q' => "date_start_sec<=$sub_date_sec;date_end_sec>=$sub_date_sec"
                    ]
                ]
            ],
            'notification' => [
                'httpCustom' => [
                    'url' => $url_receive,
                    'qs' => [
                        'user' => "{$user_id}",
                        'reason' => 'date',
                        'date' => $date_of_interest
                    ]
                ],
                "attrsFormat" => "keyValues"
            ],
            'expires' => $exp_date,
            'throttling'=> 5
        ];

        $fields_movie_isLive = [
            'description' => "Checks for when a Movie is not live. Subscription for movie {$movie_id} and user {$user_id}",
            'subject' => [
                'entities' => [
                    [
                        'id' => $movie_id,
                        'type' => self::MOVIE_TYPE,
                    ]
                ],
                'condition' => [
                    'attrs' => [
                        'isLive'
                    ]
                ]
            ],
            'notification' => [
                'httpCustom' => [
                    'url' => $url_receive,
                    'qs' => [
                        'user' => "{$user_id}",
                        'reason' => 'isLive'
                    ]
                ],
                'attrs' => [
                    'isLive'
                ]
            ],
            'expires' => $exp_date,
            'throttling'=> 5
        ];

        logger($fields_movie_isLive['description']);

        /*
         * First API call -- For Dates
         */

        $url_orion = 'http://orion-proxy:1027/v2/subscriptions';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url_orion );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields_movie_date));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch,CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "X-Auth-token: " . self::WilmaMK
        ));

        $response = curl_exec($ch);
        logger(var_export($response, true));


        // Retrieve HTTP status code
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        logger("HTTP code: ". $http_code);

        // In case of error
        $errno = curl_errno($ch);
        $err = curl_error($ch);
//---        curl_close($ch);

        // Parse results
        $success_date = false;
        if ($http_code == 201)
        {
            // Save subscription to DB service
            $sub_location = self::GetHeaderFromResponse($response, 'location');
            $sub_id_1 = substr($sub_location, strrpos($sub_location, "/") + 1);

            $success_date = true;
        }
        else if ($http_code >= 400)
            logger("Response error. Code: " . $http_code);

        else if ($errno == 6)
            logger("Could not connect to keyrock service.");

        else if ($errno != 0 )
            logger("An error occured with cURL.\n\tError: ". $err . " .. errcode: " . $errno);


        /*
         *  Second API call -- For isLive
         */

        // Update handler's data
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields_movie_isLive));

        $response = curl_exec($ch);
        logger(var_export($response, true));

        // Retrieve HTTP status code
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        logger("HTTP code: ". $http_code);

        // In case of error
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        curl_close($ch);

        // Parse results
        $success_isLive= false;
        if ($http_code == 201)
        {
            // Save subscription to DB service
            $sub_location = self::GetHeaderFromResponse($response, 'location');
            $sub_id_2 = substr($sub_location, strrpos($sub_location, "/") + 1);

            $success_isLive = true;
        }

        else if ($http_code >= 400)
            logger("Response error. Code: " . $http_code);

        else if ($errno == 6)
            logger("Could not connect to keyrock service.");

        else if ($errno != 0 )
            logger("An error occured with cURL.\n\tError: ". $err . " .. errcode: " . $errno);


        // Save the two subscription IDs to the DB service along with the date of interest, movie id and title
        if ( $success_date && $success_isLive )
            self::SaveSubscriptionToDB($user_id, $movie_id, $sub_id_1, $sub_id_2, $date_of_interest);

        // Return success
        return $success_date && $success_isLive;

    }

    static function Unsubscribe(string $user_id, string $movie_id): bool
    {
        $succes_orion = false;

        $subscriptions = self::GetAllUserSubscriptionsFromDB($user_id);

        foreach ($subscriptions as $sub_doc)
        {
            if ($movie_id == $sub_doc['movie_id'])
            {
                logger("here");
                $succes_orion = self::DeleteSubscriptionFromOrion($sub_doc['sub_id_1']);
                $succes_orion = $succes_orion && self::DeleteSubscriptionFromOrion($sub_doc['sub_id_2']);
            }
        }

        if ($succes_orion == false)
        {
            logger("Could not find subscription IDs in DB service for movie_id $movie_id and user $user_id");
            return false;
        }

        return self::DeleteSubscriptionFromDB($user_id, $movie_id);
    }

    static function DeleteSubscriptionFromOrion(string $sub_id): bool
    {
        logger("Deleting Subscription on orion with id {$sub_id}");
        $ch = curl_init();


        curl_setopt($ch, CURLOPT_URL, "http://orion-proxy:1027/v2/subscriptions/{$sub_id}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array( "X-Auth-token: ".self::WilmaMK ));

        $response = curl_exec($ch);

        // Retrieve HTTP status code
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        logger("HTTP code: ". $http_code);

        // In case of error
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        curl_close($ch);

        // Parse results
        if ($http_code == 204)
            return true;

        else if ($http_code >= 400)
            logger("Response error. Code: " . $http_code . "\nMessage: " . var_export($response ,true));

        else if ($errno == 6)
            logger("Could not connect to keyrock service.");

        else if ($errno != 0 )
            logger("An error occured with cURL.\n\tError: ". $err . " .. errcode: " . $errno);

        return false;
    }

    static function SaveSubscriptionToDB(string $user_id, string $movie_id, string $sub_id_1,  string $sub_id_2, string $sub_date )
    {
        $ch = curl_init();
        $url = "http://db-proxy:9004/users/".$user_id."/subscriptions";
        $fields = [
            'movie_id'   => $movie_id,
            'sub_id_1'   => $sub_id_1,
            'sub_id_2'   => $sub_id_2,
            'sub_date'   => $sub_date,
        ];

        $fields_string = http_build_query($fields);
        logger("Fields String: " . $fields_string);

        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch,CURLOPT_HTTPHEADER, array( "X-Auth-token: ". K_API::WilmaMK ));

        // Execute post
        logger("Sending Request...");
        $result = curl_exec($ch);

        // Retrieve HTTP status code
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        logger("HTTP code: ". $http_code);
        curl_close($ch);

        if ($http_code == 201)
        {
            logger("Subscription succesfully saved!");
            return true;
        }
        else if ($http_code >= 400)
            logger("Subscription was not created.");

        else if (curl_errno($ch) == 6)
            logger("Could not connect to db-service.");

        else if (curl_errno($ch) != 0 )
            logger("An error occured with cURL.
                        Error: ". curl_error($ch) . " .. errcode: " . curl_errno($ch));


        return false;
    }

    static function GetAllUserSubscriptionsFromDB(string $user_id)
    {
        $ch = curl_init();
        $url = "http://db-proxy:9004/users/".$user_id."/subscriptions";

        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch,CURLOPT_HTTPHEADER, array( "X-Auth-token: ". K_API::WilmaMK ));

        // Execute post
        logger("Sending Request...");
        $result = curl_exec($ch);

        // Retrieve HTTP status code
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        logger("HTTP code: ". $http_code);
        curl_close($ch);


        if ($http_code == 200)
        {
            logger("Subscriptions succesfully retrieved!");
            return json_decode($result,true);
        }
        else if ($http_code >= 400)
            logger("Subscription was not deleted.");

        else if (curl_errno($ch) == 6)
            logger("Could not connect to db-service.");

        else if (curl_errno($ch) != 0 )
            logger("An error occured with cURL.
                        Error: ". curl_error($ch) . " .. errcode: " . curl_errno($ch));

        return array();
    }

    static function DeleteSubscriptionFromDB(string $user_id, string $sub_id)
    {
        $ch = curl_init();
        $url = "http://db-proxy:9004/users/".$user_id."/subscriptions/$sub_id";

        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch,CURLOPT_HTTPHEADER, array( "X-Auth-token: ". K_API::WilmaMK ));

        // Execute post
        logger("Sending Request...");
        $result = curl_exec($ch);

        // Retrieve HTTP status code
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        logger("HTTP code: ". $http_code);
        curl_close($ch);

        if ($http_code == 204)
        {
            logger("Subscription succesfully deleted!");
            return true;
        }
        else if ($http_code >= 400)
            logger("Subscription was not deleted.");

        else if (curl_errno($ch) == 6)
            logger("Could not connect to db-service.");

        else if (curl_errno($ch) != 0 )
            logger("An error occured with cURL.
                        Error: ". curl_error($ch) . " .. errcode: " . curl_errno($ch));

        return false;
    }


    static function GetHeaderFromResponse(string $response, string $header_name): string
    {
        if (!preg_match_all('/'.$header_name.': (.*)\\r/', $response, $matches)
            || !isset($matches[1]))
        {
            return false;
        }

        return $matches[1][0];
    }
}