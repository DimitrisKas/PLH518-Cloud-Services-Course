<?php

class Movie
{
    public string $id;

    public string $title;
    public string $start_date;
    public string $end_date;
    public string $cinema_name;
    public string $category;
    public bool $favorite = false;

    public function __construct($title, $start_date, $end_date, $cinema_name, $category)
    {
        $this->title = $title;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->cinema_name = $cinema_name;
        $this->category = $category;
    }

    /** Wraper function for creating Movies objects through Document-like arrays.
     *  For Movies without ID.
     * @see CreateExistingMovieObj
     * @see fromDocumentWithID
     * @param $doc 'Document object that contains all User data
     * @return Movie Object with movie Data
     */
    public static function fromDocument($doc): Movie
    {
        return new Movie(
            $doc['title'],
            $doc['start_date'],
            $doc['end_date'],
            $doc['cinema_name'],
            $doc['category']
        );
    }

    /** Wraper function for creating Movie objects through Document-like arrays.
     *  For Movies with ID.
     * @see CreateExistingMovieObj
     * @see fromDocumentWithID
     * @param $doc 'Document object that contains all User data
     * @return Movie Object with Movie Data
     */
    public static function fromDocumentWithID($doc): Movie
    {
        return self::CreateExistingMovieObj(
            $doc['id'],
            $doc['title'],
            $doc['start_date'],
            $doc['end_date'],
            $doc['cinema_name'],
            $doc['category']
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
    public static function CreateExistingMovieObj($id, $title, $start_date, $end_date, $cinema_name, $category): Movie
    {
        $movie = new Movie($title, $start_date, $end_date, $cinema_name, $category);
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
        $url = "http://db-service/users/".$user_id."/cinemas/".$this->cinema_name."/movies";
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


    public static function EditMovie($user_id, $id, $title, $start_date, $end_date, $cinema_name, $category):bool
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
        $url = "http://db-service/users/".$user_id."/movies/".$id;
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
        $url = "http://db-service/users/".$owner_id."/movies/".$movie_id;

        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

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
            logger("Movie could not be deleted.");

        else if ($errno == 6)
            logger("Could not connect to db-service.");

        else if ($errno != 0 )
            logger("An error occured with cURL. \n
                Error: ". $err . " .. errcode: " . $errno);

        return false;
    }

    public static function AddToFavorites(string $movie_id, string $user_id)
    {

    }

    public static function GetAllMovies($user_id):array {
        if (empty($user_id))
        {
            logger("Owner id was empty");
            return array();
        }

        $ch = curl_init();
        $url = "http://db-service/users/".$user_id."/movies/all";

        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

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
                $movies[$i++] =  Movie::fromDocumentWithID($cinema_doc);
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

    public static function Search($current_user_id, $title, $date, $cinema_name, $category):array
    {
        $conn = OpenCon(true);

        // Validate search input
        if (empty($title))
            $title = "%";
        else
            $title = "%".$title."%";

        if (empty($cinema_name))
            $cinema_name = "%";
        else
            $cinema_name = "%".$cinema_name."%";

        if (empty($category))
            $category = "%";
        else
            $category = "%".$category."%";

        $doDateSearch = true;
        if (empty($date))
        {
            $date = "0000-00-00";
            $doDateSearch = false;
        }

        logger("Date: " . $date);
        logger("doDateSearch: " . $doDateSearch);

        $sql_str = "SELECT m.ID as m_ID,  m.TITLE, m.STARTDATE, m.ENDDATE, m.CINEMANAME, m.CATEGORY, f.ID as f_ID 
                    FROM Movies m 
                        LEFT JOIN Favorites f ON f.USERID = ? AND f.MOVIEID = m.ID
                    WHERE m.TITLE LIKE ? AND m.CINEMANAME LIKE ? AND m.CATEGORY LIKE ? AND ( ?=FALSE OR  (DATEDIFF(m.STARTDATE, ?) >= 0 AND  DATEDIFF(?, m.ENDDATE) >= 0));";
        $stmt = $conn->prepare($sql_str);
        $stmt->bind_param("ssssiss", $current_user_id, $title, $cinema_name, $category, $doDateSearch, $date, $date);

        if (!$stmt->execute())
            logger("Get all movies failed " . $stmt->error);

        $result = $stmt->get_result();

        $num_of_rows = $result->num_rows;
        logger("Found " . $num_of_rows . " movies.");

        $ret_array = array();
        while ($row = $result->fetch_assoc()) {

            // Create object and append to return array
            $movie = Movie::CreateExistingMovieObj(
                $row['m_ID'], $row['TITLE'], $row['STARTDATE'], $row['ENDDATE'],
                $row['CINEMANAME'], $row['CATEGORY']);

            $movie->favorite = isset($row['f_ID']);
            $ret_array[] = $movie;
        }

        $stmt->free_result();
        $stmt->close();

        CloseCon($conn);

        return $ret_array;
    }

    public static function GetAllOwnerMovies(string $user_id):array
    {
        if (empty($user_id))
        {
            logger("Owner id was empty");
            return array();
        }

        $ch = curl_init();
        $url = "http://db-service/users/".$user_id."/movies/owned";

        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

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
                $movies[$i++] =  Movie::fromDocumentWithID($cinema_doc);
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
