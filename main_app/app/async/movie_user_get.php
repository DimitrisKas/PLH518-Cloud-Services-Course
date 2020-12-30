<?php
session_start();

include_once '../db_scripts/Models/Users.php';
include_once '../db_scripts/Models/Cinemas.php';
include_once '../db_scripts/Models/Movies.php';
include_once '../db_scripts/db_connection.php';
include_once('../Utils/Random.php');
include_once('../Utils/Logs.php');

logger("-- In Get Movies");

// Check if User is logged in AND is an Admin
if (isset($_SESSION['login'])
    && $_SESSION['login'] === true)
{
    // User already logged in...
    logger("User: " . $_SESSION['user_username']);
    logger("Role: " . $_SESSION['user_role']);
    $_POST = json_decode(file_get_contents('php://input'), true);
    ?>
    <table id="movies-table">
        <tr>
            <th>Favorite</th>
            <th>Title</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Cinema Name</th>
            <th>Category</th>
        </tr>

        <?php
        if (isset($_POST['search']))
        {
            logger("Searching...");
            $movies = Movie::Search($_SESSION['user_id'], $_POST['title'], $_POST['date'], $_POST['cin_name'], $_POST['cat']);
            if ( !isset($movies))
            {
                echo "<h5>Error while searching</h5>";
                exit(1);
            }
        }
        else
        {
            $movies = Movie::GetAllMovies($_SESSION['user_id']);
        }
        /* @var $movie Movie (IDE type hint) */
        foreach ($movies as $movie)
        {
            ?>
            <tr id="movie_<?php echo $movie->id?>">
                <td><div><input id="<?php echo $movie->id?>_favorite"   type="checkbox" <?php echo $movie->isFavorite ? "checked" : ""?> onclick="toggleFavorite('<?php echo $movie->id?>', this)"/></div></td>
                <td class="td-movie-title"><div><span id="<?php echo $movie->id?>_title"><?php echo $movie->title?></span></div></td>
                <td><div><span id="<?php echo $movie->id?>_start_date"  ><?php echo $movie->start_date?></span></div></td>
                <td><div><span id="<?php echo $movie->id?>_end_date"    ><?php echo $movie->end_date?></span></div></td>
                <td><div><span id="<?php echo $movie->id?>_cinema_name" ><?php echo $movie->cinema_name?></span></div></td>
                <td><div><span id="<?php echo $movie->id?>_category"    ><?php echo $movie->category?></span></div></td>
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


