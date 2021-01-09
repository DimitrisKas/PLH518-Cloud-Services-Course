<?php

/**
 *  Helper file containing all functions needed for keyrock api communication
 */
class KeyrockAPI
{
    const CLIENT_ID = "09e67316-d0fa-490a-a2c9-58107a67cab8";
    const CLIENT_S = "85e7189b-e535-47d9-90c1-333a9d12b3c9";

    const WilmaMK = "123456";


    /**
     * Create Organization based on given name and descr
     */
    static function CreateOrganization(string $org_name, string $org_descr): bool
    {
        logger("Creating ${org_name} organization");
        $ch = curl_init();
        $url = "http://keyrock:3000/v1/organizations";
        $fields = [
            'organization' => [
                'name'  => $org_name,
                'description' => $org_descr,
            ]
        ];

        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POSTFIELDS, json_encode($fields));
        curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            'X-Auth-token: '. self::GetAdminToken()
        ));

        curl_exec($ch);

        // Retrieve HTTP status code
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        logger("HTTP code: ". $http_code);
        curl_close($ch);

        if ($http_code == 201)
        {
            logger("Organization ${org_name} successfully created!");
            return true;
        }
        else
        {
            logger("Couldn't create organization!");
            return false;
        }
    }

    static function AreOrgsInitialized()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://keyrock:3000/v1/organizations");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "X-Auth-token: ".self::GetAdminToken()
        ));

        $response = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($response, true);

        // Super simple check
        return count($response['organizations']) == 3;
    }

    static function AddUserToOrgByName(string $user_id, string $org_name, string $role): bool
    {
        logger("Adding user to org ${org_name}");

        $ch = curl_init();

        $org_id = self::GetOrgIDByName($org_name);

        // All admins are also owners to the organization ADMINS
        $url = "http://keyrock:3000/v1/organizations/${org_id}/users/${user_id}/organization_roles/${role}";

        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch,CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            'X-Auth-token: '. self::GetAdminToken()
        ));

        $result = curl_exec($ch);



        // Retrieve HTTP status code
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        logger($http_code);

        if ($http_code == 201)
        {
            logger("Added user ${user_id} to ${org_name}");
            return true;
        }
        else
        {
            logger("Couldn't add user to organization!");
            logger(var_export($result, true));
            return false;
        }
    }

    static function RemoveUserFromOrgByName(string $user_id, string $org_name, string $prev_role): bool
    {
        logger("Removing user from org ${org_name}");

        $ch = curl_init();

        $org_id = self::GetOrgIDByName($org_name);

        // All admins are also owners to the organization ADMINS
        $url = "http://keyrock:3000/v1/organizations/${org_id}/users/${user_id}/organization_roles/${prev_role}";

        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch,CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            'X-Auth-token: '. self::GetAdminToken()
        ));

        $result = curl_exec($ch);

        // Retrieve HTTP status code
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 204)
        {
            logger("Removed user ${user_id} from ${org_name}");
            return true;
        }
        else
        {
            logger("Couldn't remove user from organization!");
            logger(var_export($result, true));
            return false;
        }
    }

    static function GetUserRoleOnOrg(string $user_id, string $org_name): string
    {
        $ch = curl_init();

        $org_id = self::GetOrgIDByName($org_name);

        // All admins are also owners to the organization ADMINS
        $url = "http://keyrock:3000/v1/organizations/${org_id}/users/${user_id}/organization_roles/";

        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            'X-Auth-token: '. self::GetAdminToken()
        ));

        $result = curl_exec($ch);

        // Retrieve HTTP status code
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200)
        {
            $result = json_decode($result, true);
            return $result['organization_user']['role'];
        }
        else
        {
            return false;
        }
    }

    static function GetOrgIDByName(string $org_name): string
    {
        $ch = curl_init();
        $url = "http://keyrock:3000/v1/organizations";

        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            'X-Auth-token: '. self::GetAdminToken()
        ));

        $result = curl_exec($ch);

        // Retrieve HTTP status code
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200)
        {
            $result = json_decode($result, true);

            foreach ($result['organizations'] as $org)
            {
                if ($org['Organization']['name'] == $org_name)
                    return $org['Organization']['id'];
            }

            return false;
        }
        else
        {
            logger("Couldn't create organization!");
            logger(var_export($result, true));
            return false;
        }
    }

    /**
     * Retrieves user's access token based on his email and password
     * @param string $email User's e-mail
     * @param string $password User's password
     * @return string User's token if authentication was successful
     */
    static function GetUserToken(string $email, string $password): string
    {
        if ($email != "admin@test.com")
            logger("Getting user's ${email} token");

        $ch = curl_init();
        $url = "http://keyrock:3000/v1/auth/tokens";

        $fields = [
            'name'  => $email,
            'password'  => $password,
        ];

        $fields_string = json_encode($fields);

        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_HEADER, TRUE);
        curl_setopt($ch,CURLOPT_POST, TRUE);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, TRUE);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json"
        ));

        $response = curl_exec($ch);
        curl_close($ch);

        $token = self::GetHeaderFromResponse($response, 'X-Subject-Token');

        if (!empty($token))
            return $token;

        /* In case something went wrong */

        // Retrieve HTTP status code
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        logger("HTTP code: ". $http_code);

        // In case of error
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        curl_close($ch);

        // Parse results
        if ($http_code >= 400)
            logger("Response error. Code: " . $http_code);

        else if ($errno == 6)
            logger("Could not connect to keyrock service.");

        else if ($errno != 0 )
            logger("An error occured with cURL.
                    Error: ". $err . " .. errcode: " . $errno);

        return false;
    }

    static function GetAdminToken(): string
    {
        return self::GetUserToken('admin@test.com', '1234');
    }

    static function GetUserOAuthToken(string $email, string $password): string
    {
        logger("Getting user's ${email} token");

        $ch = curl_init();
        $url = "http://keyrock:3000/oauth2/token";

        $fields = [
            'username'  => $email,
            'password'  => $password,
            'grant_type' => 'password'
        ];

        $fields_string = http_build_query($fields);

        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POST, TRUE);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, TRUE);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/json',
            "Authorization: Basic ". base64_encode(self::CLIENT_ID.":".self::CLIENT_S)
        ));

        $response = curl_exec($ch);
        $response = json_decode($response);
        curl_close($ch);

        if (!empty($access_token))
        {
            $access_token = $response['access_token'];
            $refresh_token = $response['refresh_token'];
            $_SESSION['access_token'] = $access_token;
            $_SESSION['refresh_token'] = $refresh_token;

            return $access_token;
        }

        /* In case something went wrong */

        // Retrieve HTTP status code
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        logger("HTTP code: ". $http_code);

        // In case of error
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        curl_close($ch);

        // Parse results
        if ($http_code >= 400)
            logger("Response error. Code: " . $http_code);

        else if ($errno == 6)
            logger("Could not connect to keyrock service.");

        else if ($errno != 0 )
            logger("An error occured with cURL.
                    Error: ". $err . " .. errcode: " . $errno);

        return false;
    }

    static function GetAdminOAuthToken(): string
    {
        return self::GetUserOAuthToken('admin@test.com', '1234');
    }

    /** Get User Data from keyrock service based on his access token
     * @param $user_token
     * @return array
     */
    static function GetUserDataBasedOnToken($user_token): array
    {
        logger("Getting user's data from Keyrock");

        $ch = curl_init();
        $url = "http://keyrock:3000/v1/auth/tokens";

        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, TRUE);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'X-Auth-token: '. self::GetAdminToken(),
            'X-Subject-token: '. $user_token
        ));

        $response = curl_exec($ch);
        $response = json_decode($response, true);
        curl_close($ch);

        // Retrieve HTTP status code
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        logger("HTTP code: ". $http_code);

        // In case of error
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        curl_close($ch);

        // Parse results
        if ($http_code == 200)
            return $response['User'];

        else if ($http_code >= 400)
            logger("Response error. Code: " . $http_code);

        else if ($errno == 6)
            logger("Could not connect to keyrock service.");

        else if ($errno != 0 )
            logger("An error occured with cURL.\n\tError: ". $err . " .. errcode: " . $errno);

        return array();
    }

    /** Get User Data from keyrock service based on his keyrock id
     * @param $user_id
     * @return array
     */
    static function GetUserDataBasedOnID($user_id): array
    {
        logger("Getting user's data from Keyrock");

        $ch = curl_init();
        $url = "http://keyrock:3000/v1/users/{$user_id}";

        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, TRUE);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'X-Auth-token: '. self::GetAdminToken(),
        ));

        $response = curl_exec($ch);
        $response = json_decode($response, true);
        curl_close($ch);

        // Retrieve HTTP status code
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        logger("HTTP code: ". $http_code);

        // In case of error
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        curl_close($ch);

        // Parse results
        if ($http_code == 200)
            return $response['User'];

        else if ($http_code >= 400)
            logger("Response error. Code: " . $http_code);

        else if ($errno == 6)
            logger("Could not connect to keyrock service.");

        else if ($errno != 0 )
            logger("An error occured with cURL.\n\tError: ". $err . " .. errcode: " . $errno);

        return array();
    }

    static function GetAllUsers() {
        logger("Getting all users from Keyrock");

        $ch = curl_init();
        $url = "http://keyrock:3000/v1/users";

        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, TRUE);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'X-Auth-token: '. self::GetAdminToken(),
        ));

        $response = curl_exec($ch);
        $response = json_decode($response, true);
        curl_close($ch);

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
            logger("Retrieved all users from keyrock");
            return array(true,  $response['users'], "");
        }
        else if ($http_code >= 400)
        {
            logger("Users could not be retrieved");
            return array(false, array(), $response);
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
    }


    /** Update a Users username, email and/or password on Keyrock service. (Not his role)
     * @param User $new_user_data
     * @return bool Success bool
     */
    static function EditUser(User $new_user_data): bool {

        $ch = curl_init();
        $url = "http://keyrock:3000/v1/users/{$new_user_data->k_id}";

        $fields = [
            'user' => [
                'username'  => $new_user_data->username,
                'email'     => $new_user_data->email,
            ]
        ];

        if ( !empty($new_user_data->password) )
        {
            $fields['user']['password'] = $new_user_data->password;
        }

        $fields_string = json_encode($fields);

        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch,CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            'X-Auth-token: '. self::GetAdminToken()
        ));

        // Execute post
        logger("Sending Request...");
        $result = curl_exec($ch);

        logger("Response: ".var_export(json_decode($result),true));

        // Retrieve HTTP status code
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        logger("HTTP code: ". $http_code);

        if ($http_code == 200)
        {
            logger("User succesfully edited!");
            curl_close($ch);
            return true;

        }
        else if ($http_code >= 400)
        {
            logger("User was not edited.");
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

    /** Delete's user from keyrock service based on his id
     * @param $user_id User's Keyrock ID
     * @return bool
     */
    static function DeleteUser(string $user_id): bool
    {
        logger("Deleting user with id {$user_id} from keyrock");
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "http://keyrock:3000/v1/users/{$user_id}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "X-Auth-token: " . self::GetAdminToken()
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
