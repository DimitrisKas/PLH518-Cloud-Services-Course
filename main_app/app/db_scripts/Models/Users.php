
<?php

class User
{
    public string $id;
    public string $name;
    public string $surname;
    public string $username;
    public string $password;
    public string $email;
    public string $role;
    public bool $confirmed;

    const ADMIN = "ADMIN";
    const CINEMAOWNER = "CINEMAOWNER";
    const USER = "USER";

    const ID_PREFIX = "u";

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

    public static function fromDocument($doc): User
    {
        return new User(
                $doc['name'], $doc['surname'], $doc['username'], $doc['password'],
                $doc['email'], $doc['role'], $doc['confirmed']
        );
    }

    public static function fromDocumentWithID($doc): User
    {
        return self::CreateExistingUserObj(
            $doc['id'], $doc['name'], $doc['surname'], $doc['username'], $doc['password'],
            $doc['email'], $doc['role'], $doc['confirmed']
        );
    }


    public function addToDB():bool
    {
        if (empty($this->username))
        {
            logger("[USER_DB] Username was empty.");
            return false;
        }
        if (empty($this->password))
        {
            logger("[USER_DB] Password was empty.");
            return false;
        }

        $ch = curl_init();
        $url = "http://db-service/users";
        $fields = [
            'username'  => $this->username,
            'name'   => $this->name,
            'surname'   => $this->surname,
            'password'   => $this->password,
            'email'   => $this->email,
            'role'   => $this->role,
            'confirmed'   => FALSE,
        ];


        $fields_string = http_build_query($fields);
        logger("Fields String: " . $fields_string);

        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

//            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
//                'Content-Type: application/x-www-form-urlencoded',
//            ));


        // Execute post
        logger("Sending Request...");
        $result = curl_exec($ch);


        // Retrieve HTTP status code
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        logger("HTTP code: ". $http_code);

        if ($http_code == 203 || $http_code == 200)
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


        // Debugging
//        logger("Raw result: ". $result);
//        $res = json_decode($result, true);
//        $data = $res['response']['data'][0];
//        logger("Data:" . var_export($data, true));

        curl_close($ch);

        return false;
    }

    private function generateID()
    {
        do {
            $this->id = getRandomString(9, $this::ID_PREFIX);
        } while($this->checkIfUniqueID() === false);
    }

    public function checkIfUniqueID():bool
    {
        $conn = OpenCon(true);

        $sql_str = "SELECT ID FROM Users WHERE id=?";
        $stmt = $conn->prepare($sql_str);
        $stmt->bind_param("s",$id);
        $id = $this->id;

        if (!$stmt->execute())
            logger("[USER_DB] Check UID failed " . $stmt->error);

        if ($stmt->affected_rows === 1)
            return false;
        else
            return true;
    }

    public function checkIfUniqueUsername():bool
    {
        $conn = OpenCon(true);

        $sql_str = "SELECT ID FROM Users WHERE username=?";
        $stmt = $conn->prepare($sql_str);
        $stmt->bind_param("s",$this->username);

        if (!$stmt->execute())
            logger("[USER_DB] Check Username failed " . $stmt->error);

        if ($stmt->affected_rows === 1)
            return false;
        else
            return true;
    }

    // static functions
    #[Pure] public static function CreateExistingUserObj($id, $name, $surname, $username, $password, $email, $role, $confirmed):User
    {
        $user = new User($name, $surname, $username, $password, $email, $role, $confirmed);
        $user->id = $id;
        return $user;
    }

    public static function DeleteUser(string $id):bool
    {
        logger("[USER_DB] Trying to delete user with id: " . $id);
        $success = false;

        $conn = OpenCon(true);

        $sql_str = "DELETE FROM Users WHERE id=?";
        $stmt = $conn->prepare($sql_str);
        $stmt->bind_param("s",$id);

        if (!$stmt->execute())
        {
            logger("[USER_DB] Remove User failed " . $stmt->error);
            $success = false;
        }
        else
        {
            logger("[USER_DB] Removed user successfully!");
            $success = true;
        }

        // Clean up
        $stmt->close();
        CloseCon($conn);

        return  $success;
    }

    public static function EditUser($data):bool
    {
        logger("[USER_DB] Trying to edit user with id: " . $data['user_id']);

        $conn = OpenCon(true);

        if ($data['user_role'] !== USER::ADMIN && $data['user_role'] !== USER::CINEMAOWNER && $data['user_role'] !== USER::USER)
            return false;

        $sql_str = "UPDATE Users SET USERNAME=? , NAME=?, SURNAME=?, EMAIL=?, ROLE=?, CONFIRMED=? WHERE id=?";
        $stmt = $conn->prepare($sql_str);
        $stmt->bind_param("sssssis",$username, $name, $surname, $email, $role, $confirmed, $id);

        // Validate IsConfirmed:
        if ( !empty($data['user_confirmed']) && $data['user_confirmed'] === "true")
            $isConfirmed = true;
        else
            $isConfirmed = false;


        $username = $data['user_username'];
        $name = $data['user_name'];
        $surname = $data['user_surname'];
        $email = $data['user_email'];
        $role = $data['user_role'];
        $confirmed = $isConfirmed;
        $id = $data['user_id'];

        if (!$stmt->execute())
        {
            logger("[USER_DB] Edit User failed " . $stmt->error);
            $success = false;
        }
        else
        {
            logger("[USER_DB] Edited user successfully!");
            $success = true;
        }
        $stmt->close();

        // Check if user wants to change password
        $success_pass = true;
        if ( !empty($data['user_password']) )
        {
            $sql_str = "UPDATE Users SET PASSWORD=? WHERE id=?";
            $stmt = $conn->prepare($sql_str);
            $stmt->bind_param("ss",$password, $id);

            $password = $data['user_password'];
            $id = $data['user_id'];

            if (!$stmt->execute())
            {
                logger("[USER_DB] Password change failed: " . $stmt->error);
                $success_pass = false;
            }
            else
            {
                logger("[USER_DB] Changed User Password successfully!");
                $success_pass = true;
            }
            $stmt->close();
        }

        // Clean up
        CloseCon($conn);

        // If everything was successful
        return  $success && $success_pass;
    }

    public static function GetAllUsers():array
    {
        logger("Getting all users");
        $ch = curl_init();
        $url = "http://db-service/users";


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
            logger("Retrieved all users");
            logger($result);
            $result = json_decode($result, true);

            $users = array();
            $i =0;
            foreach ($result as $user_doc)
            {
                $users[$i++] =  User::fromDocumentWithID($user_doc);
            }
            return $users;
        }
        else if ($http_code >= 400)
        {
            logger("Users could not be retrieved");
            return array(false, array(), $result);
        }
        else if ($errno == 6)
        {
            logger("Could not connect to db-service.");
            return array(false, array(), "Internal error");
        }
        else if ($errno != 0 )
        {
            logger("An error occured with cURL.");
            logger("Error: ". $err . " .. errcode: " . $errno);
            return array(false, array(), "Internal error");
        }
    }

    /** Tries to login a user based on given Username and Password.
     * On Success, returns User model.
     * On Failure, returns NULL user model, along with reason of failure.
     * @param $username string
     * @param $password string
     * @return array(bool $success, User $user, string $errorMsg)
     */
    public static function LoginUser(string $username, string $password): array
    {

        $ch = curl_init();
        $url = "http://db-service/login";
        $fields = [
            'username'  => $username,
            'password'   => $password,
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

        // In case of error
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        curl_close($ch);


        // Parse results
        if ($http_code == 200)
        {
            logger("User succesfully logged in!");
            $user = User::fromDocumentWithID(json_decode($result, true));
            return array(true, $user, "");
        }
        else if ($http_code >= 400)
        {
            logger("User was not created.");
            return array(false, null, $result);
        }
        else if ($errno == 6)
        {
            logger("Could not connect to db-service.");
            return array(false, null, "Internal error");
        }
        else if ($errno != 0 )
        {
            logger("An error occured with cURL.");
            logger("Error: ". $err . " .. errcode: " . $errno);
            return array(false, null, "Internal error");
        }
    }

}
