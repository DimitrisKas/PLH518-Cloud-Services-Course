<?php
session_start();

include_once 'db_scripts/Models/Users.php';
include_once 'db_scripts/Models/Movies.php';
include_once 'db_scripts/keyrock_api.php';
include_once('db_scripts/orion_api.php');
include_once('Utils/Random.php');
include_once('Utils/Logs.php');
include_once('Utils/util_funcs.php');

logger("-- In Movies");

// Check if User is logged in
if (isset($_SESSION['login'])
    && $_SESSION['login'] === true)
{
    LogoutIfInactive();

    // User already logged in...
    logger("User: " . $_SESSION['user_username']);
    logger("Role: " . $_SESSION['user_role']);

}
else
{
    // Redirect to index
    $feedback = "true";
    $f_title = "You must be logged in to view that page.";
    $f_msg_count = 0;
    $f_color = "f-error";
    ?>
    <form id="redirect-form" action="./index.php" method="post">
        <input type="hidden" name="feedback" value="<?php echo $feedback?>">
        <input type="hidden" name="f_color" value="<?php echo $f_color?>">
        <input type="hidden" name="f_title" value="<?php echo $f_title?>">
        <input type="hidden" name="f_msg_count" value="<?php echo $f_msg_count?>">
    </form>
    <script type="text/javascript">
        document.getElementById("redirect-form").submit();
    </script>
    <?php
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movies - CineMania</title>
    <link rel='stylesheet' type='text/css' href='CSS/main.css' />
    <link rel='stylesheet' type='text/css' href='CSS/movies.css' />
</head>

<body class="no-overflow">
<?php // ---- Navigation Panel - START ----?>
<div class="top-nav">
    <div class="nav-items">
        <h5 id="top-nav-title">CineMania</h5>
        <a href="welcome.php">Home</a>
        <a href="movies.php">Movies</a>
        <?php
        if ($_SESSION['user_role'] === USER::CINEMAOWNER)
            echo '<a href="owner.php">Owner Panel</a> ';

        if ($_SESSION['user_role'] === USER::ADMIN)
            echo '<a href="administration.php">Admin Panel</a>';
        ?>
    </div>
    <form id="logout-form" method="post" action="./index.php?logout" class="fl-row">
        <span id="username-span"><?php echo $_SESSION['user_username'] ?></span>
        <button type="submit" class="btn-primary">Logout</button>
    </form>
</div>
<?php // ---- Navigation Panel - END ----?>

<div class="main-content" id="movies_content">
    <div class="card">
        <h4>View available Movies</h4>
        <hr/>
        <div id="popup-box-cont" class="f-success" hidden>
            <p id="popup-box-text" ></p>
        </div>
        <div  class="table-container">
            <div id="movies-container">
                <table id="movies-table">
                    <tr>
                        <th>Favorite</th>
                        <th>Title</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Cinema Name</th>
                        <th>Category</th>
                        <th>Subscribe</th>
                    </tr>

                    <?php
                    if (isset($_GET['search']))
                    {
                        $movies = Movie::Search($_SESSION['user_id'], $_GET['title'], $_GET['date'], $_GET['cin_name'], $_GET['cat']);
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
                            <td class="td-movie-title"><div><span id="<?php echo $movie->id?>_title"       ><?php echo $movie->title?></span></div></td>
                            <td><div><span id="<?php echo $movie->id?>_start_date"  ><?php echo $movie->start_date?></span></div></td>
                            <td><div><span id="<?php echo $movie->id?>_end_date"    ><?php echo $movie->end_date?></span></div></td>
                            <td><div><span id="<?php echo $movie->id?>_cinema_name" ><?php echo $movie->cinema_name?></span></div></td>
                            <td><div><span id="<?php echo $movie->id?>_category"    ><?php echo $movie->category?></span></div></td>
                            <td>
                                <div>
                                    <input id="<?php echo $movie->id?>_sub_date" name="date" class="custom-input" type="date" value="" placeholder="Date"/>
                                    <button class="btn-primary btn-success" onclick="subToMovie('<?php echo $movie->id?>')">Submit</button>
                                </div>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </table>
            </div>
            <div class="search-cont title-row fl-col">
                <h5>Search: </h5>
                <form method="GET" action="./movies.php" >
                    <input name="search" class="custom-input" type="hidden"  value="1"/>
                    <input id="search-title" name="title" class="custom-input" type="text"  value="" placeholder="Title" oninput="getMovies(true)"/>
                    <input id="search-date" name="date" class="custom-input" type="date"  value="" placeholder="Date" oninput="getMovies(true)"/>
                    <input id="search-cin_name" name="cin_name" class="custom-input" type="text"  value="" placeholder="Cinema Name" oninput="getMovies(true)"/>
                    <input id="search-cat" name="cat" class="custom-input" type="text"  value="" placeholder="Category" oninput="getMovies(true)"/>
                    <input class="btn-primary" type="submit" value="Search" onclick="getMovies(true)"/>
                </form>
            </div>
        </div>

    </div>

    <div class="card">
        <h4>Manage your Subscriptions</h4>
        <hr/>
        <div  class="table-container">
            <div id="subs-container">
                <table id="subs-table">
                    <tr>
                        <th>Movie Title</th>
                        <th>Date of Interest</th>
                        <th>Delete</th>
                    </tr>

                    <?php
                    $subscriptions = Orion_API::GetAllUserSubscriptionsFromDB($_SESSION['user_id']);
                    foreach ($subscriptions as $sub)
                    {
                        $movie_id = $sub['movie_id'];
                        ?>
                        <tr id="sub_<?php echo $movie_id?>">
                            <td><div><span id="sub_<?php echo $movie_id?>_title" ><?php echo $sub['movie_title']?></span></div></td>
                            <td><div><span id="sub_<?php echo $movie_id?>_date"  ><?php echo $sub['date']?></span></div></td>
                            <td class="action-td">
                                <div><button id="sub_<?php echo $movie_id?>_delete" class="btn-primary btn-danger" onclick="deleteSub('<?php echo $movie_id?>')" >Delete</button></div>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </table>
            </div>
        </div>

    </div>

</div>
</body>
<script type="text/javascript">

    document.getElementById("popup-box-cont").hidden = false;

    function subToMovie(movie_id)
    {
        let date = document.getElementById(movie_id + '_sub_date').value;

        fetch('async/movie_subscribe.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                'movie_id' : movie_id,
                'date' : date
            })
        })
            .then( response => {
                return response.json();
            })
            .then( success =>{
                showModal(success);
                getSubs();
            });
    }

    function deleteSub(movie_id)
    {
        fetch('async/subscription_delete.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                'movie_id' :movie_id
            })
        })
            .then( response => {
                return response.json();
            })
            .then( success =>{
                showModal(success);
                getSubs();
            });
    }

    function getSubs()
    {
        fetch('async/subscription_get.php', {
            method: 'POST',
        })
            .then( response => {
                response.text()
                    .then( text => {
                        let container = document.getElementById("subs-container");
                        container.innerHTML = text;
                    });
            });
    }

    function getMovies(isSearching)
    {
        let data = {}
        if(isSearching)
        {
            this.event.stopPropagation();
            data = {
                search: true,
                title: document.getElementById("search-title").value,
                date: document.getElementById("search-date").value,
                cin_name: document.getElementById("search-cin_name").value,
                cat: document.getElementById("search-cat").value,
            }
        }
        fetch('async/movie_user_get.php', {
            method: 'POST',
            body: JSON.stringify(data)
        })
            .then( response => {
                response.text()
                    .then( text => {
                        let container = document.getElementById("movies-container");
                        container.innerHTML = text;
                    });
            });
    }

    function toggleFavorite(movie_id, checkbox)
    {
        fetch('async/favorite_toggle.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                'addFavorite': checkbox.checked ? "true" : "false",
                'movie_id' : movie_id
            })
        })
            .then( response => {
                return response.json();
            })
            .then( success =>{
                showModal(success);
                getMovies();
            });
    }

    function showModal(isSuccessful)
    {
        let text_obj = document.getElementById("popup-box-text");
        let cont_obj = document.getElementById("popup-box-cont");
        // document.getElementById("popup-box-cont").classList.remove("popup-hidden");

        document.getElementById("popup-box-cont").classList.add("popup-show");

        if (isSuccessful)
        {
            text_obj.innerText = "Success!";
            cont_obj.classList.remove("f-warning");
            cont_obj.classList.add("f-success");
        }
        else
        {
            text_obj.innerText = "An error occured!";
            cont_obj.classList.remove("f-success");
            cont_obj.classList.add("f-error");

        }

        setTimeout(function () { document.getElementById("popup-box-cont").classList.remove("popup-show");}, 2500);
    }
</script>
</html>












