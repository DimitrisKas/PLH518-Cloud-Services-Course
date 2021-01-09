<?php

/**
 * Check if user has been inactive for more than 2 hours. If yes, then logs out and redirects to index
 */
function LogoutIfInactive() {

    // Check if logged in but inactive for more than 2 hours (= 7200 sec)
    // If login_timestamp IS NOT SET, also log out
    if ( !isset($_SESSION['login_timestamp']) || time() - $_SESSION['login_timestamp'] > 7200)
    {
        // Clear SESSION array
        $_SESSION['login'] = false;

        unset(
            $_SESSION['user_id'],
            $_SESSION['user_username'],
            $_SESSION['user_role'],
            $_SESSION['user_email'],
            $_SESSION['user_name'],
            $_SESSION['user_surname'],
            $_SESSION['login_timestamp']
        );

        $feedback = "true";
        $f_title = "Logged out due to inactivity";
        $f_msg_count = 0;
        $f_color = "f-error";
        ?>
        <form id="toIndex" action="./index.php?logout" method="post">
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
        // Refresh timer
        $_SESSION['login_timestamp'] = time();
    }
}

/** Redirect to Index page with message
 * @param null $msg Message to display for feedback
 * @param int $severity 0: info, 1: warning, 2: error
 */
function RedirectToIndex($msg = null, int $severity = 1)
{
    if (!empty($null))
    {
        $f_title = "You do not have access to that page.";
    }
    else
    {
        $f_title = "";
    }

    switch ($severity)
    {
        case (0): $f_color = "f-info"; break;
        case (2): $f_color = "f-error"; break;
        default: $f_color = "f-warning"; break;
    }

    // Redirect to index
    $feedback = "true";
    $f_msg_count = 0;

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