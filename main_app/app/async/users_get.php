<?php
session_start();

include_once '../db_scripts/Models/Users.php';
include_once '../db_scripts/db_connection.php';
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

        $users = User::GetAllUsers();
        /* @var $user User (IDE type hint) */
        foreach ($users as $user)
        {
            ?>
            <tr id="user_<?php echo $user->id?>" onclick="toggleHighlight(this)">
                <td><div><input id="<?php echo $user->id?>_id"        type="text"  value="<?php echo $user->id?>"       class="disabled-input id-field" disabled/></div></td>
                <td><div><input id="<?php echo $user->id?>_username"  type="text"  value="<?php echo $user->username?>" class="custom-input"/></div></td>
                <td><div><input id="<?php echo $user->id?>_name"      type="text"  value="<?php echo $user->name?>"     class="custom-input"/></div></td>
                <td><div><input id="<?php echo $user->id?>_surname"   type="text"  value="<?php echo $user->surname?>"  class="custom-input"/></div></td>
                <td><div><input id="<?php echo $user->id?>_password"  type="text"  value="" placeholder="Enter new password..." class="custom-input"/></div></td>
                <td><div><input id="<?php echo $user->id?>_email"     type="text"  value="<?php echo $user->email?>"    class="custom-input"/></div></td>
                <td>
                    <div>
                        <select id="<?php echo $user->id?>_role" name="role">
                            <option value="ADMIN" <?php echo $user->role === User::ADMIN ? "selected" : "" ?>>Admin</option>
                            <option value="CINEMAOWNER" <?php echo $user->role === User::CINEMAOWNER ? "selected" : "" ?>>Cinema Owner</option>
                            <option value="USER" <?php echo $user->role === User::USER ? "selected" : "" ?>>User</option>
                        </select>
                    </div>
                </td>
                <td><div><input id="<?php echo $user->id?>_confirmed" type="checkbox" <?php echo $user->confirmed ? "checked" : ""?>/></div></td>
                <td class="action-td">
                    <div><button id="<?php echo $user->id?>_submit" class="btn-primary btn-success" onclick="submitUser('<?php echo $user->id?>')" >Save</button></div>
                </td>
                <td class="action-td">
                    <div><button id="<?php echo $user->id?>_delete" class="btn-primary btn-danger" onclick="deleteUser('<?php echo $user->id?>')" >Delete</button></div>
                </td>
            </tr>
            <?php
        }
        ?>
    </table>
    <?php
    exit(1);
}

// If failed for any reason...
echo json_encode(false);


