<?php
session_start();

include_once('db_scripts/Models/Users.php');
include_once('db_scripts/keyrock_api.php');
include_once('Utils/Random.php');
include_once('Utils/Logs.php');
include_once('Utils/util_funcs.php');

logger("-- In Welcome.php");

if (isset($_SESSION['login']) && $_SESSION['login'] === true)
{
    LogoutIfInactive();

    logger("User already logged in");
    logger("Logged in User: " . $_SESSION['user_username']);
    logger("Role: " . $_SESSION['user_role']);
}
else
{
    $error = false;
    if (empty($_POST['email'])){
        logger("Empty email...");
        $error = true;
    }

    if (empty($_POST['password'])) {
        logger("Empty password...");
        $error = true;
    }

    if ( $error ) // Not enough credentials
    {
        $feedback = "true";
        $f_title = "Email or Password was empty";
        $f_msg_count = 0;
        $f_color = "f-error";
        ?>
        <form id="toIndex" action="./index.php" method="post">
            <input type="hidden" name="feedback" value="<?php echo $feedback?>">
            <input type="hidden" name="f_color" value="<?php echo $f_color?>">
            <input type="hidden" name="f_title" value="<?php echo $f_title?>">
            <input type="hidden" name="f_msg_count" value="<?php echo $f_msg_count?>">
        </form>
        <script type="text/javascript">
            document.getElementById("toIndex").submit();
        </script>
        <?php
        exit();
    }
    else // Try to login
    {
        list($wasSuccessful, $currentUser, $errorMsg) = User::LoginUser($_POST['email'], $_POST['password']);

        if ($wasSuccessful == false)
        {
            logger("Redirecting to index");
            $feedback = "true";
            $f_title = "Error: " . $errorMsg;
            $f_msg_count = 0;
            $f_color = "f-error";
            ?>
                <form id="toIndex" action="./index.php" method="post">
                    <input type="hidden" name="feedback" value="<?php echo $feedback?>">
                    <input type="hidden" name="f_color" value="<?php echo $f_color?>">
                    <input type="hidden" name="f_title" value="<?php echo $f_title?>">
                    <input type="hidden" name="f_msg_count" value="<?php echo $f_msg_count?>">
                </form>
                <script type="text/javascript">
                    document.getElementById("toIndex").submit();
               </script>
            <?php
            exit();
        }
        else
        {
            $_SESSION['user_id'] = $currentUser->k_id;
            $_SESSION['user_username'] = $currentUser->username;
            $_SESSION['user_role'] = $currentUser->role;
            $_SESSION['user_email'] = $currentUser->email;
            $_SESSION['user_name'] = $currentUser->name;
            $_SESSION['user_surname'] = $currentUser->surname;

            $_SESSION['login_timestamp'] = time();
            $_SESSION['login'] = true;
            logger("Logged in User: " . $currentUser->username);
            logger("Role: " . $currentUser->role);
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - CineMania</title>
    <link rel='stylesheet' type='text/css' href='CSS/main.css' />
    <link rel='stylesheet' type='text/css' href='CSS/welcome.css' />
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

    <div class="main-content">

        <d id="welcome-options">
            <h3>Welcome back, <?php echo $_SESSION['user_username']?>.</h3>
            <hr/>
            <div class="card welcome-option" onclick="location.href='movies.php';">
                <h5>Browse Movies</h5>
                <p>View a list of all available Movies</p>
            </div>
            <?php
            if ($_SESSION['user_role'] === USER::CINEMAOWNER)
                echo '
                    <div class="card welcome-option" onclick="location.href=\'owner.php\';">
                        <h5>Manage your Movies</h5>
                        <p>View and Edit your registered Movies</p>
                    </div>
                    ';
            if ($_SESSION['user_role'] === USER::ADMIN)
                echo '
                    <div class="card welcome-option" onclick="location.href=\'administration.php\';">
                        <h5>Manage Users</h5>
                        <p>View and Edit all registered Users.</p>
                    </div>
                    ';
            ?>
            <div id="feed-card" class="card">
                <h5 id="feed-title">Your Feed</h5>
                <div id="feed-container"></div>
            </div>
        </div>

    </div>
</body>
<script>

    let isFeedEmpty = true;

    function getNotifications()
    {
        fetch('async/notifications_get.php', {
            method: 'GET',
        })
            .then( response => {
                response.text()
                    .then( text => {
                        let container = document.getElementById("feed-container");
                        if (text !== "")
                        {
                            if (isFeedEmpty === false)
                            {
                                container.innerHTML = container.innerHTML + text;
                            }
                            else
                            {
                                container.innerHTML = text;
                            }
                            isFeedEmpty = false;
                        }
                        checkIfFeedEmpty();
                    });
            });
        console.log('Called API');
    }

    function dismissNotification(id)
    {
        document.getElementById(id).remove();
        isFeedEmpty();
    }

    function checkIfFeedEmpty()
    {
        let container = document.getElementById("feed-container");
        if (container.innerText === "")
        {
            container.innerHTML = "<p>You have no new notifications</p>";
        }
    }

    getNotifications();
    setInterval(getNotifications, 3000);
</script>
</html>
