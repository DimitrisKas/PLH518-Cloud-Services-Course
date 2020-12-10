<?php
session_start();

include_once '../db_scripts/Models/Users.php';
include_once '../db_scripts/Models/Cinemas.php';
include_once '../db_scripts/db_connection.php';
include_once('../Utils/Random.php');
include_once('../Utils/Logs.php');

logger("-- In Get Cinema");

// Check if User is logged in AND is an Admin
if (isset($_SESSION['login'])
    && $_SESSION['login'] === true
    && isset($_SESSION['user_role'])
    && $_SESSION['user_role'] === User::CINEMAOWNER)
{
    // User already logged in...
    logger("User: " . $_SESSION['user_username']);
    logger("Role: " . $_SESSION['user_role']);

    ?>
    <table id="cinemas-table">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Owner</th>
                    <th></th>
                    <th></th>
                </tr>

                <?php

                $cinemas = Cinema::GetAllOwnerCinemas($_SESSION['user_id']);
                /* @var $cinema Cinema (IDE type hint) */
                foreach ($cinemas as $cinema)
                {
                    ?>
                    <tr id="cinema_<?php echo $cinema->id?>">
                        <td><div><input id="<?php echo $cinema->id?>_id"      type="text"  value="<?php echo $cinema->id?>"     class="disabled-input" disabled/></div></td>
                        <td><div><input id="<?php echo $cinema->id?>_name"    type="text"  value="<?php echo $cinema->name?>"   class="custom-input"/></div></td>
                        <td><div><input id="<?php echo $cinema->id?>_owner"   type="text"  value="<?php echo $cinema->owner." (".$_SESSION['user_username'].")" ?>"  class="disabled-input" disabled/></div></td>
                        <td class="action-td">
                            <div><button id="<?php echo $cinema->id?>_submit" class="btn-primary btn-success" onclick="submitCinema('<?php echo $cinema->id?>')" >Save</button></div>
                        </td>
                        <td class="action-td">
                            <div><button id="<?php echo $cinema->id?>_delete" class="btn-primary btn-danger" onclick="deleteCinema('<?php echo $cinema->id?>')" >Delete</button></div>
                        </td>
                    </tr>
                    <?php
                }
                ?>
                <tr class="no-hover-row title-row">
                    <td><h5>Add new Cinema</h5></td>
                </tr>
                <tr id="cinema_new" class="no-hover-row">
                    <td><div><input id="new_cinema_id"     class="disabled-input" type="text"  value="Auto Generated" disabled/></div></td>
                    <td><div><input id="new_cinema_name"   class="custom-input"   type="text"  value=""  placeholder="Enter Name"/></div></td>
                    <td><div><input id="new_cinema_owner"  class="disabled-input" type="text"  value="<?php echo $_SESSION['user_id']." (".$_SESSION['user_username'].")" ?>" disabled/></div></td>
                    <td class="action-td">
                        <div><button id="new_cinema_submit" class="btn-primary btn-success" onclick="addCinema()" >Add</button></div>
                    </td>
                </tr>
            </table>
    <?php
    exit(1);
}

// If failed for any reason...
echo json_encode(false);


