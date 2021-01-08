<?php
session_start();

include_once '../db_scripts/Models/Users.php';
include_once '../db_scripts/Models/Cinemas.php';
include_once '../db_scripts/Models/Movies.php';
include_once('../db_scripts/keyrock_api.php');
include_once('../Utils/Random.php');
include_once('../Utils/Logs.php');

logger("-- In Get Movies");

// Check if User is logged in AND is an Admin
if (isset($_SESSION['login'])
    && $_SESSION['login'] === true
    && isset($_SESSION['user_role'])
    && $_SESSION['user_role'] === User::CINEMAOWNER)
{
    // User already logged in...
    logger("User: " . $_SESSION['user_username']);
    logger("Role: " . $_SESSION['user_role']);

    $cinemas = Cinema::GetAllOwnerCinemas($_SESSION['user_id']);

    ?>
    <table id="movies-table">
        <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Cinema Name</th>
            <th>Category</th>
            <th></th>
            <th></th>
        </tr>

        <?php

        $movies = Movie::GetAllOwnerMovies($_SESSION['user_id']);
        /* @var $movie Movie (IDE type hint) */
        foreach ($movies as $movie)
        {
            ?>
            <tr id="user_<?php echo $movie->id?>">
                <td><div><input id="<?php echo $movie->id?>_id"          type="text" value="<?php echo $movie->id?>"          class="disabled-input" disabled/></div></td>
                <td><div><input id="<?php echo $movie->id?>_title"       type="text" value="<?php echo $movie->title?>"       class="custom-input"/></div></td>
                <td><div><input id="<?php echo $movie->id?>_start_date"  type="date"  min="1997-01-01" max="2030-12-31" value="<?php echo $movie->start_date?>"  class="custom-input"/></div></td>
                <td><div><input id="<?php echo $movie->id?>_end_date"    type="date"  min="1997-01-01" max="2030-12-31" value="<?php echo $movie->end_date?>"    class="custom-input"/></div></td>
                <td>
                    <div>
                        <select id="<?php echo $movie->id?>_cinema_name">
                            <?php
                            foreach($cinemas as $cinema)
                            {
                                echo '<option value="'.$cinema->name.'"' . (($movie->cinema_name === $cinema->name) ? "selected" : "") . '>' .$cinema->name.'</option>';
                            }
                            ?>
                        </select>
                    </div>
                </td>
                <td><div><input id="<?php echo $movie->id?>_category"    type="text" value="<?php echo $movie->category?>"    class="custom-input"/></div></td>
                <td class="action-td">
                    <div><button id="<?php echo $movie->id?>_submit" class="btn-primary btn-success" onclick="submitMovie('<?php echo $movie->id?>')" >Save</button></div>
                </td>
                <td class="action-td">
                    <div><button id="<?php echo $movie->id?>_delete" class="btn-primary btn-danger" onclick="deleteMovie('<?php echo $movie->id?>')" >Delete</button></div>
                </td>
            </tr>
            <?php
        }
        ?>


        <tr class="no-hover-row title-row">
            <td><h5>Add new Movie</h5></td>
        </tr>
        <tr id="movie_new" class="no-hover-row">
            <td><div><input id="new_movie_id"           class="disabled-input" type="text"  value="Auto Generated" disabled/></div></td>
            <td><div><input id="new_movie_title"        class="custom-input"   type="text"  value=""  placeholder="Enter Movie Title"/></div></td>
            <td><div><input id="new_movie_start_date"   class="custom-input"   type="date"  min="1997-01-01" max="2030-12-31"  value=""  placeholder="Enter Start Date"/></div></td>
            <td><div><input id="new_movie_end_date"     class="custom-input"   type="date"  min="1997-01-01" max="2030-12-31"  value=""  placeholder="Enter End Date"/></div></td>
            <td>
                <div>
                    <select id="new_movie_cinema_name">
                        <option value="" disabled selected>Select a Cinema</option>
                        <?php
                        foreach($cinemas as $cinema)
                        {
                            echo '<option value="'.$cinema->name.'">'.$cinema->name.'</option>';
                        }
                        ?>
                    </select>
                </div>
            </td>
            <td><div><input id="new_movie_category"     class="custom-input"   type="text"  value=""  placeholder="Enter Category"/></div></td>
            <td class="action-td">
                <div><button id="new_movie_submit" class="btn-primary btn-success" onclick="addMovie()" >Add</button></div>
            </td>
        </tr>
    </table>
    <?php
    exit(1);
}

// If failed for any reason...
echo json_encode(false);


