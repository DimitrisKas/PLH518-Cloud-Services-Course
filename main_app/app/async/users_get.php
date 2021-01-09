<?php
session_start();

include_once '../db_scripts/Models/Users.php';
include_once '../db_scripts/keyrock_api.php';
include_once('../Utils/Random.php');
include_once('../Utils/Logs.php');

logger("-- In Get Users");

// Check if User is logged in AND is an Admin
if (isset($_SESSION['login'])
    && $_SESSION['login'] === true
    && isset($_SESSION['user_role'])
    && $_SESSION['user_role'] === User::ADMIN)
{
    // User already logged in...
    logger("User: " . $_SESSION['user_username']);
    logger("Role: " . $_SESSION['user_role']);

    list($isSuccessful, $users, $errmsg)= User::GetAllUsers();

    /* @var $user User (IDE type hint) */
    if ($isSuccessful == false)
    {
        ?>
        <div>
            <h5>Error retrieving Users:</h5>
            <p><?php echo $errmsg?></p>
        </div>
        <?php
    }
    else
    {
        ?>
        <table id="admin-table">
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Name</th>
                <th>Surname</th>
                <th>Password</th>
                <th>E-mail</th>
                <th>Role</th>
                <th>Confirmed</th>
                <th></th>
                <th></th>
            </tr>

        <?php
        foreach ($users as $user)
        {
            ?>
            <tr id="user_<?php echo $user->k_id?>" onclick="toggleHighlight(this)">
                <td><div><input id="<?php echo $user->k_id?>_id"        type="text"  value="<?php echo $user->k_id?>"       class="disabled-input id-field" disabled/></div></td>
                <td><div><input id="<?php echo $user->k_id?>_username"  type="text"  value="<?php echo $user->username?>" class="custom-input"/></div></td>
                <td><div><input id="<?php echo $user->k_id?>_name"      type="text"  value="<?php echo $user->name?>"     class="custom-input"/></div></td>
                <td><div><input id="<?php echo $user->k_id?>_surname"   type="text"  value="<?php echo $user->surname?>"  class="custom-input"/></div></td>
                <td><div><input id="<?php echo $user->k_id?>_password"  type="text"  value="" placeholder="Enter new password..." class="custom-input"/></div></td>
                <td><div><input id="<?php echo $user->k_id?>_email"     type="text"  value="<?php echo $user->email?>"    class="custom-input"/></div></td>
                <td>
                    <div>
                        <select id="<?php echo $user->k_id?>_role" name="role">
                            <option value="ADMIN" <?php echo $user->role === User::ADMIN ? "selected" : "" ?>>Admin</option>
                            <option value="CINEMAOWNER" <?php echo $user->role === User::CINEMAOWNER ? "selected" : "" ?>>Cinema Owner</option>
                            <option value="USER" <?php echo $user->role === User::USER ? "selected" : "" ?>>User</option>
                        </select>
                    </div>
                </td>
                <td><div><input id="<?php echo $user->k_id?>_confirmed" type="checkbox" <?php echo $user->confirmed ? "checked" : ""?>/></div></td>
                <td class="action-td">
                    <div><button id="<?php echo $user->k_id?>_submit" class="btn-primary btn-success" onclick="submitUser('<?php echo $user->k_id?>')" >Save</button></div>
                </td>
                <td class="action-td">
                    <div><button id="<?php echo $user->k_id?>_delete" class="btn-primary btn-danger" onclick="deleteUser('<?php echo $user->k_id?>')" >Delete</button></div>
                </td>
            </tr>
            <?php

        } // For loop

        echo "</table>";

    } // If Users Retrieved

    exit(1);
}

// If failed for any reason...
echo json_encode(false);


