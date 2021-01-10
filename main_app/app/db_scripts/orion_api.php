<?php

/**
 * Helper class containing all functions needed to communicated with the orion api
 */
class Orion_API
{

    const WilmaMK = "123456";
    const TYPE = "Movie";

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
            'type' => self::TYPE,
            'title' => [
                'value' => $title,
                'type' => 'Title'
            ],
            'date_start' => [
                'value' => $date_start,
                'type' => 'Date'
            ],
            'date_end' => [
                'value' => $date_end,
                'type' => 'Date'
            ],
            'isLive' => [
                'value' => $isLive,
                'type' => 'bool'
            ]
        ];

        curl_setopt($ch, CURLOPT_URL, "http://orion-proxy:1027/v2/entities");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

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
        if ($http_code == 201)
            return true;

        else if ($http_code >= 400)
            logger("Response error. Code: " . $http_code);

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
            'date_start' => [
                'value' => $date_start,
                'type' => 'Date'
            ],
            'date_end' => [
                'value' => $date_end,
                'type' => 'Date'
            ],
            'isLive' => [
                'value' => $isLive,
                'type' => 'bool'
            ]
        ];

        curl_setopt($ch, CURLOPT_URL, "http://orion-proxy:1027/v2/entities/{$id}/attrs");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');

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
            logger("Response error. Code: " . $http_code);

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
            logger("Response error. Code: " . $http_code);

        else if ($errno == 6)
            logger("Could not connect to keyrock service.");

        else if ($errno != 0 )
            logger("An error occured with cURL.\n\tError: ". $err . " .. errcode: " . $errno);

        return false;
    }
}