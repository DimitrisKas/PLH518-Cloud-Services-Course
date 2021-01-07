
<?php

use KeyrockAPI as K_API;

class User
{

    /** @var string Keyrock ID     */
    public string $id;
    public string $name;
    public string $surname;
    public string $username;
    public string $password;
    public string $email;
    public string $role;
    public bool $confirmed;

    const ADMIN = "ADMIN";
    const ORG_ADMIN = "ADMINS";

    const CINEMAOWNER = "CINEMAOWNER";
    const ORG_CINEMAOWNER = "CINEMAOWNERS";

    const USER = "USER";
    const ORG_USER = "USERS";

    public function __construct($name, $surname, $username, $password, $email, $role, $confirmed)
    {
        $this->name = $name;
        $this->surname = $surname;
        $this->username = $username;
        $this->password = $password;
        $this->email = $email;
        $this->role = $role;
        $this->confirmed = $confirmed;
    }

    /** Wraper function for creating User objects through Document-like arrays.
     *  For Users without ID.
     * @param $doc 'Document object that contains all User data
     * @return User Object with user Data
     * @see CreateExistingUserObj
     * @see FromDocumentWithID
     */
    public static function FromDocument($doc): User
    {
        return new User(
                $doc['name'], $doc['surname'], $doc['username'], $doc['password'],
                $doc['email'], $doc['role'], $doc['confirmed']
        );
    }

    /** Wraper function for creating User objects through Document-like arrays.
     *  For Users with ID.
     * @param $doc 'Document object that contains all User data
     * @return User Object with user Data
     * @see CreateExistingUserObj
     * @see FromDocument
     */
    public static function FromDocumentWithID($doc): User
    {
        return self::CreateExistingUserObj(
            $doc['id'], $doc['name'], $doc['surname'], $doc['username'], $doc['password'],
            $doc['email'], $doc['role'], $doc['confirmed']
        );
    }

    /** Create a User object for User that already exists in Database. (i.e. has an ID)
     * @param $id
     * @param $name
     * @param $surname
     * @param $username
     * @param $password
     * @param $email
     * @param $role
     * @param $confirmed
     * @return User Object of user with given data
     */
    public static function CreateExistingUserObj($id, $name, $surname, $username, $password, $email, $role, $confirmed):User
    {
        $user = new User($name, $surname, $username, $password, $email, $role, $confirmed);
        $user->id = $id;
        return $user;
    }

    /** Register current user object to various services
     * @return bool Success boolean
     */
    public function registerUser():bool
    {
        if (empty($this->username))
        {
            logger("Username was empty.");
            return false;
        }

        if (empty($this->password))
        {
            logger("Password was empty.");
            return false;
        }

        if (empty($this->email))
        {
            $this->email = $this->username . "@test.com";
        }

        $keyrockSuccess =  $this->registerToKeyrock();

        $mongoSuccess =  $this->registerToMongo();

        // Return success only if both registers where successful
        return $keyrockSuccess && $mongoSuccess;

    }


    private function registerToKeyrock(): bool
    {
        $KAPI = new KeyrockAPI();

        logger("Registering user to Keyrock");

        $ch = curl_init();
        $url = "http://keyrock:3000/v1/users";
        $fields = [
            'user' => [
                'username'  => $this->username,
                'email'     => $this->email,
                'password'  => $this->password,
            ]
        ];

        $fields_string = json_encode($fields);
        logger("Fields String: " . $fields_string);

        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            'X-Auth-token: '. $KAPI->GetAdminToken()
        ));

        // Execute post
        logger("Sending Request...");
        $result = curl_exec($ch);

        logger("Response: ".var_export(json_decode($result),true));

        // Retrieve HTTP status code
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        logger("HTTP code: ". $http_code);

        if ($http_code == 201)
        {
            logger("User succesfully created!");
            curl_close($ch);

            // Set role

            $result =  json_decode($result, true);
            $user_id = $result['user']['id'];

            logger("Role: ".$this->role);
            if ($this->role == self::ADMIN)
            {
                $success = $KAPI->AddUserToOrgByName($user_id, self::ORG_ADMIN, "owner");
                $success = $success && $KAPI->AddUserToOrgByName($user_id, self::ORG_CINEMAOWNER, "owner");
                $success = $success && $KAPI->AddUserToOrgByName($user_id, self::ORG_USER, "owner");
            }
            else if ($this->role == self::CINEMAOWNER)
            {
                $success = $KAPI->AddUserToOrgByName($user_id, self::ORG_CINEMAOWNER, "member");
            }
            else
            {
                $success = $KAPI->AddUserToOrgByName($user_id, self::ORG_USER, "member");
            }

            return $success;



            // Set as disabled - DEPRECATED
            // NOTE: For some reason, the api does not allow to set "enabled" to "false"
            //       but allows to set it to "true" if it was already "false"
//
//            $ch = curl_init();
//            $url = "http://keyrock:3000/v1/users/" . $id . "/enable";
//
//            curl_setopt($ch,CURLOPT_URL, $url);
//            curl_setopt($ch,CURLOPT_POSTFIELDS, json_encode($fields));
//            curl_setopt($ch,CURLOPT_CUSTOMREQUEST, 'PUT');
//            curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
//            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
//                "Content-Type: application/json",
//                'X-Auth-token: '. self::GetAdminToken()
//            ));
//
//            $result = curl_exec($ch);
//
//            logger(var_export($result,true));
//
//            // Retrieve HTTP status code
//            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//
//            if ($http_code == 200)
//            {
//                logger("User set to disabled");
//                return true;
//            }
//            else
//            {
//                logger("Failed to disable user");
//                return false;
//            }


        }
        else if ($http_code >= 400)
        {
            logger("User was not created.");
        }
        else if (curl_errno($ch) == 6)
        {
            logger("Could not connect to keyrock service.");
        }
        else if (curl_errno($ch) != 0 )
        {
            logger("An error occured with cURL.");
            logger("Error: ". curl_error($ch) . " .. errcode: " . curl_errno($ch));
        }

        curl_close($ch);

        return false;
    }

    private function registerToMongo(): bool
    {
        logger("Registering user to MongoDB");

        $ch = curl_init();
        $url = "http://db-proxy:1027/users";
        $fields = [
            'k_id' => $this->id,
            'username'  => $this->username,
            'email'   => $this->email,
            'name'   => $this->name,
            'surname'   => $this->surname,
            'role'   => $this->role,
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
            logger("User succesfully created!");
            curl_close($ch);
            return true;
        }
        else if ($http_code >= 400)
        {
            logger("User was not created.");
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


    /** Gets all Users from the DB-Service
     * @return array(bool $success, User $user, string $errorMsg)
     */
    public static function GetAllUsers():array
    {
        logger("Getting all users");
        $ch = curl_init();
        $url = "http://db-proxy:1027/users";

        $token = K_API::GetAdminToken();

        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Accept: application/json",
            "Content-Type: application/json",
            "X-Auth-Token: " . $token
        ));

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
            logger("Retrieved all users");
            $result = json_decode($result, true);

            $users = array();
            $i =0;
            foreach ($result as $user_doc)
            {
                $users[$i++] =  User::FromDocumentWithID($user_doc);
            }
            return array(true, $users, "");
        }
        else if ($http_code >= 400)
        {
            logger("Users could not be retrieved");
            return array(false, array(), $result);
        }
        else if ($errno == 6)
        {
            logger("Could not connect to db-service.");
            return array(false, array(), "Could not connect to Database Service");
        }
        else if ($errno != 0 )
        {
            logger("An error occured with cURL.");
            logger("Error: ". $err . " .. errcode: " . $errno);
            return array(false, array(), "Internal Error: " . $err);
        }

        return array(false, array(), "Undefined Error");
    }

    /** Gets all Users from the DB-Service
     * @param $data object Document object that should contain properly defined keys with edit data
     * @return bool Success boolean
     */
    public static function EditUser($data): bool
    {
        logger("Trying to edit user with id: " . $data['user_id']);

        // Check if Role is set
        if ($data['user_role'] !== USER::ADMIN && $data['user_role'] !== USER::CINEMAOWNER && $data['user_role'] !== USER::USER)
            return false;

        // Validate IsConfirmed:
        if ( !empty($data['user_confirmed']) && $data['user_confirmed'] === "true")
            $isConfirmed = true;
        else
            $isConfirmed = false;


        $username = $data['user_username'];
        $password= $data['user_password'];
        $name = $data['user_name'];
        $surname = $data['user_surname'];
        $email = $data['user_email'];
        $role = $data['user_role'];
        $id = $data['user_id'];

        $ch = curl_init();
        $url = "http://db-proxy:1027/users/" . $id;
        $fields = [
            'name'   => $name,
            'surname'   => $surname,
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

        // In case of error
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        curl_close($ch);

        // Parse results
        if ($http_code == 204)
            return true;

        else if ($http_code >= 400)
            logger("User could not be edited.");

        else if ($errno == 6)
            logger("Could not connect to db-service.");

        else if ($errno != 0 )
            logger("An error occured with cURL. \n
                Error: ". $err . " .. errcode: " . $errno);

        return false;

    }

    /** Deletes User from Database
     * @param $id User's id to be deleted
     * @return bool Success boolean
     */
    public static function DeleteUser(string $id):bool
    {
        logger("Trying to delete user with id: " . $id);

        $ch = curl_init();
        $url = "http://db-proxy:1027/users/" . $id;

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
            logger("User could not be deleted.");

        else if ($errno == 6)
            logger("Could not connect to db-service.");

        else if ($errno != 0 )
            logger("An error occured with cURL. \n
                Error: ". $err . " .. errcode: " . $errno);

        return false;
    }


    /** Tries to login a user based on given Username and Password.
     * On Success, returns User model.
     * On Failure, returns NULL user model, along with reason of failure.
     * @param $email string
     * @param $password string
     * @return array(bool $success, User $user, string $errorMsg)
     */
    public static function LoginUser(string $email, string $password): array
    {
        $token = K_API::GetUserToken($email, $password);

        if ( empty($token) )
        {
            $msg = "Couldn't authenticate user";
            logger($msg);
            return array(false, null, $msg);
        }
        else
        {
            logger("User succesfully logged in!");

            // Get basic info from Keyrock
            $ud = K_API::GetUserData($token);
            $role = User::GetUserRole($ud['id']);

            // Get extra info from DB service
            $user = User::CreateExistingUserObj($ud['id'], "", "", $ud['username'], $password, $ud['email'], $role, true);

            return array(true, $user, "");
        }


//        /// Deprecated
//
//        $ch = curl_init();
//        $url = "http://db-proxy:1027/login";
//        $fields = [
//            'username'  => $username,
//            'password'   => $password,
//        ];
//
//        $fields_string = http_build_query($fields);
//        logger("Fields String: " . $fields_string);
//
//        curl_setopt($ch,CURLOPT_URL, $url);
//        curl_setopt($ch,CURLOPT_POST, true);
//        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
//        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
//
//        // Execute post
//        logger("Sending Request...");
//        $result = curl_exec($ch);
//
//        // Retrieve HTTP status code
//        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//        logger("HTTP code: ". $http_code);
//
//        // In case of error
//        $errno = curl_errno($ch);
//        $err = curl_error($ch);
//        curl_close($ch);
//
//        // Parse results
//        if ($http_code == 200)
//        {
//            logger("User succesfully logged in!");
//            $user = User::FromDocumentWithID(json_decode($result, true));
//            return array(true, $user, "");
//        }
//        else if ($http_code >= 400)
//        {
//            logger("User was not created.");
//            return array(false, null, $result);
//        }
//        else if ($errno == 6)
//        {
//            logger("Could not connect to db-service.");
//            return array(false, null, "Internal error");
//        }
//        else if ($errno != 0 )
//        {
//            logger("An error occured with cURL.");
//            logger("Error: ". $err . " .. errcode: " . $errno);
//            return array(false, null, "Internal error");
//        }
//
//        return array(false, array(), "Undefined error");
    }

    public static function AddFavorite(string $user_id, string $movie_id)
    {
        logger("Trying to add favorite movie for user with id: " . $user_id);

        $ch = curl_init();
        $url = "http://db-proxy:1027/users/".$user_id."/favorites";
        $fields = [
            'movie_id'  => $movie_id,
        ];

        $fields_string = http_build_query($fields);
        logger("Fields String: " . $fields_string);

        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
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
        if ($http_code == 201)
            return true;

        else if ($http_code >= 400)
            logger("Favorite movie could not be added.");

        else if ($errno == 6)
            logger("Could not connect to db-service.");

        else if ($errno != 0 )
            logger("An error occured with cURL. \n
                Error: ". $err . " .. errcode: " . $errno);

        return false;
    }

    public static function DeleteFavorite(string $user_id, string $movie_id)
    {
        logger("Trying to delete favorite movie for user with id: " . $user_id);

        $ch = curl_init();
        $url = "http://db-proxy:1027/users/".$user_id."/favorites/".$movie_id;

        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_CUSTOMREQUEST, 'DELETE');
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
            logger("Favorite movie could not be deleted.");

        else if ($errno == 6)
            logger("Could not connect to db-service.");

        else if ($errno != 0 )
            logger("An error occured with cURL. \n
                Error: ". $err . " .. errcode: " . $errno);

        return false;
    }


    public static function GetUserRole(string $user_id): string
    {
        if (K_API::GetUserRoleOnOrg($user_id, User::ORG_ADMIN) == "owner")
        {
            return User::ADMIN;
        }
        else if (K_API::GetUserRoleOnOrg($user_id, User::ORG_CINEMAOWNER) == "member")
        {
            return User::CINEMAOWNER;
        }
        else if (K_API::GetUserRoleOnOrg($user_id, User::ORG_USER) == "member")
        {
            return User::USER;
        }

        logger("Couldn't determine user's role");
        // Fallback value in case of error
        return User::USER;
    }

}
