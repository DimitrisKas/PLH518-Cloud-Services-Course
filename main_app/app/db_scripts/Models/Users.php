
<?php

use KeyrockAPI as K_API;

class User
{

    /** @var string Keyrock ID     */
    public string $k_id;
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
     * @param $k_id User's keyrock id
     * @param $name
     * @param $surname
     * @param $username
     * @param $password
     * @param $email
     * @param $role
     * @param $confirmed
     * @return User Object of user with given data
     */
    public static function CreateExistingUserObj($k_id, $name, $surname, $username, $password, $email, $role, $confirmed):User
    {
        $user = new User($name, $surname, $username, $password, $email, $role, $confirmed);
        $user->k_id = $k_id;
        return $user;
    }

    /** Returns all user data associated with given Keyrock access token
     * @param $token User's Access token from Keyrock
     * @return User | bool Returns User model with data. False on error
     */
    public static function GetFullUserDataBasedOnToken($token): User | bool
    {
        // User data directly from keyrock
        $u_keyrk = K_API::GetUserDataBasedOnToken($token);

        if (empty($u_keyrk))
            return false;

        // Extract user role from Keyrock
        $role = User::GetUserRole($u_keyrk['id']);

        // Get extra info from DB service (mongo database)
        $u_mongo = User::GetUserFromDBService($u_keyrk['id']);

        return User::CreateExistingUserObj(
            $u_keyrk['id'],
            $u_mongo['name'],
            $u_mongo['surname'],
            $u_keyrk['username'],
            "",
            $u_keyrk['email'],
            $role,
            true);
    }

    /** Returns all user data associated with given Keyrock Retrieved User Data.
     * @param $user_keyrock_data User's Data that was retrieved from Keyrock Service
     * @return User | bool Returns User model with data. False on error
     */
    public static function GetFullUserDataBasedOnKeyrockData($user_keyrock_data): User | bool
    {
        // Use User data that has already been retrieved from keyrock
        $u_keyrk = $user_keyrock_data;

        if (empty($u_keyrk))
            return false;

        // Extract user role from Keyrock
        $role = User::GetUserRole($u_keyrk['id']);

        // Get extra info from DB service (mongo database)
        $u_mongo = User::GetUserFromDBService($u_keyrk['id']);

        return User::CreateExistingUserObj(
            $u_keyrk['id'],
            $u_mongo['name'],
            $u_mongo['surname'],
            $u_keyrk['username'],
            "",
            $u_keyrk['email'],
            $role,
            true);
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

        $keyrockSuccess = $this->registerToKeyrock();
        $mongoSuccess = $this->registerToMongo();

        // Return success only if both registers where successful
        return $keyrockSuccess && $mongoSuccess;

    }

    /** Registers User to Keyrock service
     * @return bool Returns true on success, False on failure
     */
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
            $this->k_id = $result['user']['id'];

            logger("Role: ".$this->role);
            if ($this->role == self::ADMIN)
            {
                $success = $KAPI->AddUserToOrgByName($this->k_id, self::ORG_ADMIN, "owner");
                $success = $success && $KAPI->AddUserToOrgByName($this->k_id, self::ORG_CINEMAOWNER, "owner");
                $success = $success && $KAPI->AddUserToOrgByName($this->k_id, self::ORG_USER, "owner");
            }
            else if ($this->role == self::CINEMAOWNER)
            {
                $success = $KAPI->AddUserToOrgByName($this->k_id, self::ORG_CINEMAOWNER, "member");
            }
            else
            {
                $success = $KAPI->AddUserToOrgByName($this->k_id, self::ORG_USER, "member");
            }

            return $success;

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

    /** Registers User to Database service (mongoDB)
     * @return bool Returns true on success, False on failure
     */
    private function registerToMongo(): bool
    {
        logger("Registering user to MongoDB");

        $ch = curl_init();
        $url = "http://db-proxy:1027/users";
        $fields = [
            'k_id' => $this->k_id,
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
        curl_setopt($ch, CURLOPT_HTTPHEADER, array( "X-Auth-token: ". K_API::WilmaMK ));

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

    /** Queries the Database service to retrieve available data for given User's Keyrock id
     * @param string $user_k_id User keyrock ID
     * @return array Returns document array containing User's data
     */
    public static function GetUserFromDBService(string $user_k_id): array
    {
        logger("Getting user's data from DB Service based on token.");
        $ch = curl_init();
        $url = "http://db-proxy:1027/users/{$user_k_id}";

        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array( "X-Auth-token: ". K_API::WilmaMK ));

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
            logger("Retrieved user");
            $result = json_decode($result, true);
            return $result;
        }
        else if ($http_code >= 400)
        {
            logger("User could not be retrieved");
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

        // Undefined error
        return array();
    }

    /** Gets all Users from the DB-Service
     * @return array(bool $success, User $user, string $errorMsg)
     */
    public static function GetAllUsers():array
    {

        list($success, $users_kstr, $msg) = K_API::GetAllUsers();

        if ($success)
        {
            $users = array();
            $i =0;
            foreach ($users_kstr as $user_keyrock_doc)
            {
                $users[$i++] = User::GetFullUserDataBasedOnKeyrockData($user_keyrock_doc);
            }

            return array($success, $users, "");
        }
        else
        {
            return array($success, $users_kstr, $msg);
        }

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

        $success_keyrock = self::EditUserOnKeyrock($data);

        $success_dbservice = self::EditUserOnDBService($data);

        return $success_keyrock && $success_dbservice;


    }

    /** Edit user's data on keyrock service
     * @param $data 'Document array with user data
     * @return bool
     */
    public static function EditUserOnKeyrock($data): bool
    {
        $new_user = self::CreateExistingUserObj(
            $data['user_id'], "", "", $data['user_username'],
            $data['user_password'], $data["user_email"], $data["user_role"], true);

        // Edit username, password, email
        $succcess_mainEdit = K_API::EditUser($new_user);

        $curr_role = self::GetUserRole($new_user->k_id);

        // Edit role
        $success_Role = true;
        if ($curr_role != $new_user->role)
        {
            /*
             * Remove user from appropriate organization based on his current role
             */
            if ($curr_role == self::ADMIN)
            {
                $success_removeOrg = K_API::RemoveUserFromOrgByName($new_user->k_id, self::ORG_ADMIN, "owner");
                $success_removeOrg = $success_removeOrg && K_API::RemoveUserFromOrgByName($new_user->k_id, self::ORG_CINEMAOWNER, "owner");
                $success_removeOrg = $success_removeOrg && K_API::RemoveUserFromOrgByName($new_user->k_id, self::ORG_USER, "owner");
            }
            else if ($curr_role == self::CINEMAOWNER)
            {
                $success_removeOrg = K_API::RemoveUserFromOrgByName($new_user->k_id, self::ORG_CINEMAOWNER, "member");
            }
            else
            {
                $success_removeOrg = K_API::RemoveUserFromOrgByName($new_user->k_id, self::ORG_USER, "member");
            }

            /*
             * Add user to corresponding organizations based on his new role
             */
            if ($new_user->role == self::ADMIN)
            {
                $success_addOrg = K_API::AddUserToOrgByName($new_user->k_id, self::ORG_ADMIN, "owner");
                $success_addOrg = $success_addOrg && K_API::AddUserToOrgByName($new_user->k_id, self::ORG_CINEMAOWNER, "owner");
                $success_addOrg = $success_addOrg && K_API::AddUserToOrgByName($new_user->k_id, self::ORG_USER, "owner");
            }
            else if ($new_user->role == self::CINEMAOWNER)
            {
                $success_addOrg = K_API::AddUserToOrgByName($new_user->k_id, self::ORG_CINEMAOWNER, "member");
            }
            else
            {
                $success_addOrg = K_API::AddUserToOrgByName($new_user->k_id, self::ORG_USER, "member");
            }

            // Check if role change was successful
            $success_Role = $success_removeOrg && $success_addOrg;
        }

        // Check if all edits were succesful
        return $succcess_mainEdit && $success_Role;
    }

    /** Edit user's data on the Database Service (MongoDB)
     * @param $data 'Document array with user data
     * @return bool Success boolean
     */
    public static function EditUserOnDBService($data): bool
    {
        $ch = curl_init();
        $url = "http://db-proxy:1027/users/{$data['user_id']}";
        $fields = [
            'username' => $data['user_username'],
            'email' => $data['user_email'],
            'name'   => $data['user_name'],
            'surname'   => $data['user_surname'],
            'role' => $data['user_role'],
        ];

        $fields_string = http_build_query($fields);
        logger("Fields String: " . $fields_string);

        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array( "X-Auth-token: ". K_API::WilmaMK ));

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
     * @param $user_id User's id to be deleted
     * @return bool Success boolean
     */
    public static function DeleteUser(string $user_id):bool
    {
        logger("Trying to delete user with id: " . $user_id);

        $success_keyrock = K_API::DeleteUser($user_id);
        $success_dbserv = self::DeleteUserOnDBService($user_id);

        return $success_keyrock && $success_dbserv;
    }

    public static function DeleteUserOnDBService(string $user_id): bool
    {
        $ch = curl_init();
        $url = "http://db-proxy:1027/users/" . $user_id;

        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array( "X-Auth-token: ". K_API::WilmaMK ));

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
            $user = User::GetFullUserDataBasedOnToken($token);
            return array(true, $user, "");
        }

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
        curl_setopt($ch, CURLOPT_HTTPHEADER, array( "X-Auth-token: ". K_API::WilmaMK));

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

    public static function DeleteFavorite(string $user_k_id, string $movie_id)
    {
        logger("Trying to delete favorite movie for user with id: " . $user_k_id);

        $ch = curl_init();
        $url = "http://db-proxy:1027/users/".$user_k_id."/favorites/".$movie_id;


        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array( "X-Auth-token: ". K_API::WilmaMK));


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

    /** Extract User's role (ADMIN/CINEMAOWNER/USER) based on Keyrock organizations he is a member/owner of.
     * @param string $user_k_id User's Keyrock id
     * @return string User's role
     */
    public static function GetUserRole(string $user_k_id): string
    {
        if (K_API::GetUserRoleOnOrg($user_k_id, User::ORG_ADMIN) == "owner")
            return User::ADMIN;

        else if (K_API::GetUserRoleOnOrg($user_k_id, User::ORG_CINEMAOWNER) == "member")
            return User::CINEMAOWNER;

        else if (K_API::GetUserRoleOnOrg($user_k_id, User::ORG_USER) == "member")
            return User::USER;

        logger("Couldn't determine user's role");
        // Fallback value in case of error
        return User::USER;
    }

}
