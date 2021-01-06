<?php

/**
 *  Helper file containing all functions needed for keyrock api communication
 */
class KeyrockAPI
{
    /**
     * Create Organization based on given name and descr
     */
    function CreateOrganization(string $org_name, string $org_descr): bool
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

    function AreOrgsInitialized()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://keyrock:3000/v1/organizations");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "X-Auth-token: ".$this->GetAdminToken()
        ));

        $response = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($response, true);

        // Super simple check
        return count($response['organizations']) == 3;
    }

    function AddUserToOrgByName(string $user_id, string $org_name, string $role): bool
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

    function RemoveUserFromOrgByName(string $user_id, string $org_name, string $prev_role): bool
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

        if ($http_code == 201)
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

    function GetUserRoleOnOrg(string $user_id, string $org_name): string
    {
        logger("Getting user's role on org ${org_name}");

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

        if ($http_code == 201)
        {
            logger("Success");

            $result = json_decode($result, true);
            return $result['organization_user']['role'];
        }
        else
        {
            logger(var_export($result, true));
            return false;
        }
    }

    function GetOrgIDByName(string $org_name): string
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

    function GetAdminToken(): string
    {
        logger("Accessing admin's token");

        $ch = curl_init();
        $url = "http://keyrock:3000/v1/auth/tokens";

        $fields = [
            'name'  => 'admin@test.com',
            'password'  => '1234',
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


    //        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    //        $header = substr($response, 0, $header_size);
    //        $body = substr($response, $header_size);
    //
    //        logger(var_export("Header: \n" . $header,true));
    //        logger(var_export("Body: \n" . $body,true));
    //
    //        // Decode body
    //        $body = json_decode($body);
    //
    //

        // In case something went wrong

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

    function GetHeaderFromResponse(string $response, string $header_name): string
    {
        if (!preg_match_all('/'.$header_name.': (.*)\\r/', $response, $matches)
            || !isset($matches[1]))
        {
            return false;
        }

        return $matches[1][0];
    }
}
