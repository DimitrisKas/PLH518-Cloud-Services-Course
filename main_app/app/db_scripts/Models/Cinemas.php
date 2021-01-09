
<?php

use KeyrockAPI as K_API;

class Cinema
{
    public string $id;
    public string $owner;
    public string $name;

    public function __construct($owner, $name)
    {
        $this->owner = $owner;
        $this->name = $name;
    }

    /** Wraper function for creating Cinema objects through Document-like arrays.
     *  For Cinemas without ID.
     * @see CreateExistingCinemaObj
     * @see fromDocumentWithID
     * @param $doc 'Document object that contains all User data
     * @return Cinema Object with Cinema Data
     */
    public static function fromDocument($doc): Cinema
    {
        return new Cinema($doc['owner'], $doc['name']);
    }

    /** Wraper function for creating Cinema objects through Document-like arrays.
     *  For Cinemas with ID.
     * @see CreateExistingCinemaObj
     * @see fromDocumentWithID
     * @param $doc 'Document object that contains all User data
     * @return Cinema Object with Cinema Data
     */
    public static function fromDocumentWithID($doc): Cinema
    {
        return self::CreateExistingCinemaObj($doc['id'], $doc['owner'], $doc['name'] );
    }

    /** Create a Cinema object for User that already exists in Database. (i.e. has an ID)
     * @param $id
     * @param $owner
     * @param $name
     * @return Cinema Object of cinema with given data
     */
    public static function CreateExistingCinemaObj($id, $owner, $name): Cinema
    {
        $cinema = new Cinema($owner, $name);
        $cinema->id = $id;
        return $cinema;
    }


    /** Add self to database
     * @return bool Success boolean
     */
    public function addToDB():bool
    {
        if (empty($this->name))
        {
            logger("Cinema name was empty");
            return false;
        }

        $ch = curl_init();
        $url = "http://db-proxy:9004/users/".$this->owner."/cinemas";
        $fields = [
            'owner'   => $this->owner,
            'name'   => $this->name,
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

        if ($http_code == 201 || $http_code == 200)
        {
            logger("Cinema succesfully created!");
            curl_close($ch);
            return true;
        }
        else if ($http_code >= 400)
        {
            logger("Cinema was not created.");
        }
        else if (curl_errno($ch) == 6)
        {
            logger("Could not connect to db-service.");
        }
        else if (curl_errno($ch) != 0 )
        {
            logger("An error occured with cURL.");
            logger("Error: ". curl_error($ch) . " .. errcode: " . curl_errno($ch));
        }
        curl_close($ch);

        return false;
    }

    /** Retrieves all given user id owned Cinemas
     * @param string $owner_id Id of user's cinemas we want to retrieve
     * @return array Array of Cinema Object found for given user
     */
    public static function GetAllOwnerCinemas(string $owner_id):array
    {
        if (empty($owner_id))
        {
            logger("Owner id was empty");
            return array();
        }

        $ch = curl_init();
        $url = "http://db-proxy:9004/users/".$owner_id."/cinemas";

        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch,CURLOPT_HTTPHEADER, array( "X-Auth-token: ". K_API::WilmaMK ));

        // Execute post
        logger("Sending Request...");
        $result = curl_exec($ch);

        // Retrieve HTTP status code
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        logger("HTTP code: ". $http_code);

        // In case of error
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        curl_close($ch);


        // Parse results
        if ($http_code == 200)
        {
            logger("Retrieved all cinemas for user with id ".$owner_id);
            $result = json_decode($result, true);

            $cinemas = array();
            $i =0;
            foreach ($result as $cinema_doc)
            {
                $cinemas[$i++] =  Cinema::fromDocumentWithID($cinema_doc);
            }
            return $cinemas;
        }
        else if ($http_code >= 400)
        {
            logger("Cinemas could not be retrieved");
            return array();
        }
        else if ($errno == 6)
        {
            logger("Could not connect to db-service.");
            return array();
        }
        else if ($errno != 0 )
        {
            logger("An error occured with cURL.");
            logger("Error: ". $err . " .. errcode: " . $errno);
            return array();
        }

        return array();
    }


    /** Edit a Cinema
     * @param string $cinema_id Cinema's ID which we want to edit
     * @param string $name Cinema's Name
     * @param string $owner_id Cinema owner's ID
     * @return bool Success boolean
     */
    public static function EditCinema(string $cinema_id, string $name, string $owner_id):bool
    {
        if (empty($name) || empty($cinema_id))
        {
            logger("Cinema name or id was empty");
            return false;
        }

        $ch = curl_init();
        $url = "http://db-proxy:9004/users/".$owner_id."/cinemas/".$cinema_id;
        $fields = [
            'name'   => $name,
        ];

        $fields_string = http_build_query($fields);
        logger("Fields String: " . $fields_string);

        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch,CURLOPT_HTTPHEADER, array( "X-Auth-token: ". K_API::WilmaMK ));

        // Execute post
        logger("Sending Request...");
        $result = curl_exec($ch);


        // Retrieve HTTP status code
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        logger("HTTP code: ". $http_code);

        if ($http_code == 204 || $http_code == 200)
        {
            logger("Cinema succesfully created!");
            curl_close($ch);
            return true;
        }
        else if ($http_code >= 400)
        {
            logger("Cinema was not created.");
        }
        else if (curl_errno($ch) == 6)
        {
            logger("Could not connect to db-service.");
        }
        else if (curl_errno($ch) != 0 )
        {
            logger("An error occured with cURL.");
            logger("Error: ". curl_error($ch) . " .. errcode: " . curl_errno($ch));
        }
        curl_close($ch);

        return false;
    }

    /** Deletes Cinema from Database
     * @param string $cinema_id Id of cinema to delete
     * @param string $owner_id Owners ID
     * @return bool Success boolean
     */
    public static function DeleteCinema(string $cinema_id, string $owner_id): bool
    {
        if (empty($cinema_id))
        {
            logger("No cinema id given");
            return false;
        }

        if (empty($owner_id))
        {
            logger("No user id given");
            return false;
        }

        logger("Trying to delete cinema with id: " . $cinema_id);

        $ch = curl_init();
        $url = "http://db-proxy:9004/users/".$owner_id."/cinemas/".$cinema_id ;

        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch,CURLOPT_HTTPHEADER, array( "X-Auth-token: ". K_API::WilmaMK ));

        // Execute post
        logger("Sending Request... at ". $url);
        $result = curl_exec($ch);

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
            logger("Cinema could not be deleted.");

        else if ($errno == 6)
            logger("Could not connect to db-service.");

        else if ($errno != 0 )
            logger("An error occured with cURL. \n
                Error: ". $err . " .. errcode: " . $errno);

        return false;
    }


}
