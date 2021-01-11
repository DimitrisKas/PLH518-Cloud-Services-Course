<?php
use KeyrockAPI as K_API;

class Movie
{
    public string $id;

    public string $title;
    public string $start_date;
    public string $end_date;
    public string $cinema_name;
    public string $category;
    public bool $isFavorite = false;

    public function __construct($title, $start_date, $end_date, $cinema_name, $category, $isFavorite)
    {
        $this->title = $title;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->cinema_name = $cinema_name;
        $this->category = $category;
        $this->isFavorite = $isFavorite;
    }

    /** Wraper function for creating Movies objects through Document-like arrays.
     *  For Movies without ID.
     * @param $doc 'Document object that contains all User data
     * @return Movie Object with movie Data
     * @see CreateExistingMovieObj
     * @see FromDocumentWithID
     */
    public static function FromDocument($doc): Movie
    {
        return new Movie(
            $doc['title'],
            $doc['start_date'],
            $doc['end_date'],
            $doc['cinema_name'],
            $doc['category'],
            isset($doc['isFavorite']) ? $doc['isFavorite'] : false
        );
    }

    /** Wraper function for creating Movie objects through Document-like arrays.
     *  For Movies with ID.
     * @param $doc 'Document object that contains all User data
     * @return Movie Object with Movie Data
     * @see CreateExistingMovieObj
     * @see FromDocumentWithID
     */
    public static function FromDocumentWithID($doc): Movie
    {
        return self::CreateExistingMovieObj(
            $doc['id'],
            $doc['title'],
            $doc['start_date'],
            $doc['end_date'],
            $doc['cinema_name'],
            $doc['category'],
            isset($doc['isFavorite']) ? $doc['isFavorite'] : false
        );
    }

    /** Create a Movie object for Movie that already exists in Database. (i.e. has an ID)
     * @param $id
     * @param $title
     * @param $start_date
     * @param $end_date
     * @param $cinema_name
     * @param $category
     * @return Movie Object of Movie with given data
     */
    public static function CreateExistingMovieObj($id, $title, $start_date, $end_date, $cinema_name, $category, $isFavorite): Movie
    {
        $movie = new Movie($title, $start_date, $end_date, $cinema_name, $category, $isFavorite);
        $movie->id = $id;
        return $movie;
    }

    public function addToDB($user_id):bool
    {
        if (empty($this->title))
        {
            logger("Title was empty.");
            return false;
        }

        if (empty($this->cinema_name))
        {
            logger("Cinema name was empty.");
            return false;
        }

        $ch = curl_init();
        $url = "http://db-proxy:9004/users/".$user_id."/cinemas/".$this->cinema_name."/movies";
        $fields = [
            'title'   => $this->title,
            'start_date'   => $this->start_date,
            'end_date'   => $this->end_date,
            'cinema_name'   => $this->cinema_name,
            'category'   => $this->category,
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

            $result = json_decode($result, true);
            $id = $result['movie_id'];

            // If successful add to Orion too
            $success_orion = Orion_API::CreateMovieEntity($id, $this->title, $this->start_date, $this->end_date, true);

            if ($success_orion)
                logger("Added movie entity to Orion");

            return $success_orion;
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


    public static function EditMovie($user_id, $movie_id, $title, $start_date, $end_date, $cinema_name, $category):bool
    {
        if (empty($title))
        {
            logger("Title was empty.");
            return false;
        }

        if (empty($cinema_name))
        {
            logger("Cinema name was empty.");
            return false;
        }

        $ch = curl_init();
        $url = "http://db-proxy:9004/users/".$user_id."/movies/".$movie_id;
        $fields = [
            'title' => $title,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'cinema_name' => $cinema_name,
            'category' => $category,
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
            logger("Movie succesfully updated!");
            curl_close($ch);

            Orion_API::UpdateDate($movie_id, $start_date, $end_date);
            return true;
        }
        else if ($http_code >= 400)
        {
            logger("Movie was not updated.");
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

    public static function DeleteMovie(string $owner_id, string $movie_id,)
    {
        if (empty($movie_id))
        {
            logger("No movie id given");
            return false;
        }

        if (empty($owner_id))
        {
            logger("No user id given");
            return false;
        }

        logger("Trying to delete movie with id: " . $movie_id);

        $ch = curl_init();
        $url = "http://db-proxy:9004/users/".$owner_id."/movies/".$movie_id;

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
        {
            Orion_API::UpdateMovieIsLive($movie_id, false);
            return true;
        }
        else if ($http_code >= 400)
            logger("Movie could not be deleted.");

        else if ($errno == 6)
            logger("Could not connect to db-service.");

        else if ($errno != 0 )
            logger("An error occured with cURL. \n
                Error: ". $err . " .. errcode: " . $errno);

        return false;
    }



    public static function GetAllMovies($user_id):array {
        if (empty($user_id))
        {
            logger("Owner id was empty");
            return array();
        }

        $ch = curl_init();
        $url = "http://db-proxy:9004/users/".$user_id."/movies/all";

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
            logger("Retrieved all movies for user with id ".$user_id);
            $result = json_decode($result, true);

            $movies = array();
            $i =0;
            foreach ($result as $movie_doc)
            {
                $movies[$i++] =  Movie::FromDocumentWithID($movie_doc);
            }
            return $movies;
        }
        else if ($http_code >= 400)
        {
            logger("Movies could not be retrieved");
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

    public static function Search($user_id, $title, $date, $cinema_name, $category): array | bool
    {
        $ch = curl_init();
        $url = "http://db-proxy:9004/users/".$user_id."/movies/search";
        $fields = [
            'title'   => $title,
            'date'   => $date,
            'cin_name'   => $cinema_name,
            'cat'   => $category,
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

            logger("Search successful!");
            $result = json_decode($result, true);

            if ($result == null)
            {
                logger("Nothing found!");
                return array();
            }

            $movies = array();
            $i =0;
            foreach ($result as $movie_doc)
            {
                $movies[$i++] =  Movie::FromDocumentWithID($movie_doc);
            }
            return $movies;
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

    public static function GetAllOwnerMovies(string $user_id):array
    {
        if (empty($user_id))
        {
            logger("Owner id was empty");
            return array();
        }

        $ch = curl_init();
        $url = "http://db-proxy:9004/users/".$user_id."/movies/owned";

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
            logger("Retrieved all movies for user with id ".$user_id);
            $result = json_decode($result, true);

            $movies = array();
            $i =0;
            foreach ($result as $cinema_doc)
            {
                $movies[$i++] =  Movie::FromDocumentWithID($cinema_doc);
            }
            return $movies;
        }
        else if ($http_code >= 400)
        {
            logger("Movies could not be retrieved");
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
}
