<?php if (!defined('POST_COUNT')) define('POST_COUNT', TRUE);

// Prevent the script from continuing without a valid user ID.
if (!isset($_GET['userid'])) {
    die('FAILED: The script cannot run without a user id.');
} elseif (!is_numeric($_GET['userid'])) {
    die('FAILED: The passed user id is not a numerical value.');
} else {
    // Get the user id and store it in a constant.
    defined('USER_ID') ? NULL : define('USER_ID', intval($_GET['userid']));
}

// Define a couple of global constants to make things easier.
defined('DS') ? NULL : define('DS', DIRECTORY_SEPARATOR);
defined('EXT') ? NULL : define('EXT', '.php');
defined('ROOT') ? NULL : define('ROOT', str_replace(array('/', '\\'), DS, realpath(dirname(__FILE__)).DS));

if (!file_exists(ROOT.'inc'.DS.'postcount.lib'.EXT) || !require_once(ROOT.'inc'.DS.'postcount.lib'.EXT)) {
    _Kill("ERROR: Uable to include the post-count script.");
}

// Define some constants to make it easier to change the source of static resources.
defined('JQUERY_JS') ? NULL : define('JQUERY_JS', '//code.jquery.com/jquery-1.11.0.min.js');
defined('BOOTSTRAP_JS') ? NULL : define('BOOTSTRAP_JS', '//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js');
defined('BOOTSTRAP_CSS') ? NULL : define('BOOTSTRAP_CSS', '//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css');
defined('BOOTSTRAP_THEME_CSS') ? NULL : define('BOOTSTRAP_THEME_CSS', '//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap-theme.min.css');

defined('DO_AUTH') ? NULL : define('DO_AUTH', _Cfg('auth_enabled', TRUE));
defined('DO_VIEWS') ? NULL : define('DO_VIEWS', _Cfg('views_enabled', TRUE));

if (defined('DO_AUTH') && DO_AUTH === TRUE) {
    // Grab the last authentication time from the .auth file.
    $LastAuth = file_get_contents (ROOT._Cfg('last_auth_file', '.auth'));

    // Validate the value retrieved from the .auth file.
    if (!is_numeric($LastAuth)) {
        // Probably a new installation and we must authenticate now.
        $LastAuth = 0;
    } else {
        $LastAuth = intval($LastAuth);
    }
    // Check wheather we need to authenticate again.
    $LastAuth = cURL_Authenticate($LastAuth);

    // Store back the last authentication timestamp to the .auth file.
    file_put_contents(ROOT._Cfg('last_auth_file', '.auth'), strval($LastAuth));
}

// Get the User Details and store them in a global variable named User.
$User = GetPostCount(USER_ID);

// Find the user type.
if (!defined('USER_TYPE')) {
    if ($User['banned'] === TRUE || $User['unapproved'] === TRUE) {
        define('USER_TYPE', 'blocked');
    } elseif ($User['vps_owner'] === TRUE) {
        define('USER_TYPE', 'owner');
    } elseif ($User['vps_owner'] !== TRUE && $User['sponsor'] !== TRUE && empty($User['staff'])) {
        define('USER_TYPE', 'normal');
    } elseif ($User['sponsor'] === TRUE) {
        define('USER_TYPE', 'sponsor');
    } elseif ($User['staff'] === 'admin') {
        define('USER_TYPE', 'admin');
    } elseif ($User['staff'] === 'moderator') {
        define('USER_TYPE', 'moderator');
    } elseif ($User['staff'] === 'retired') {
        define('USER_TYPE', 'retired');
    } else {
        define('USER_TYPE', 'unknown');
    }
}

if (defined('DO_VIEWS') && DO_VIEWS === TRUE) {
    // Grab the current views from the .views file.
    $TotalViews = file_get_contents (ROOT._Cfg('total_views_file', '.views'));

    // Validate the value retrieved from the .views file.
    if (!is_numeric($TotalViews)) {
        $TotalViews = 1;
    } else {
        $TotalViews = intval($TotalViews);
        $TotalViews += 1;
    }
    // Store back the ammount of views to the .views file.
    file_put_contents(ROOT._Cfg('total_views_file', '.views'), strval($TotalViews));
}

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FreeVPS PostCount Script</title>

    <!-- Bootstrap CSS Files -->
    <link href="<?php echo BOOTSTRAP_CSS; ?>" rel="stylesheet">
    <link href="<?php echo BOOTSTRAP_THEME_CSS; ?>" rel="stylesheet">

    <style type="text/css">

    </style>

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
      <div class="container">          
<?php
    if (!file_exists(ROOT.'inc'.DS.'welcome.user'.EXT) || !require_once(ROOT.'inc'.DS.'welcome.user'.EXT)) {
        _Kill("ERROR: Uable to include the welcome message script.");
    }
?>
        <div class="row">
<?php
    if (!file_exists(ROOT.'inc'.DS.'user.profile'.EXT) || !require_once(ROOT.'inc'.DS.'user.profile'.EXT)) {
        _Kill("ERROR: Uable to include the user profile script.");
    }
?>
        </div>
        <div class="page-header">
            <h1>Posts Details</h1>
        </div>
        <div class="row">
<?php
    if (!file_exists(ROOT.'inc'.DS.'postd.'.USER_TYPE.EXT) || !require_once(ROOT.'inc'.DS.'postd.'.USER_TYPE.EXT)) {
        _Kill("ERROR: Uable to include the user profile script.");
    }
?>
        </div>
        <div class="page-header">
            <h1>Posts List</h1>
        </div>
        <div class="row">
<?php
    if (!file_exists(ROOT.'inc'.DS.'post.list'.EXT) || !require_once(ROOT.'inc'.DS.'post.list'.EXT)) {
        _Kill("ERROR: Uable to include the post list script.");
    }
?>
        </div>
<?php
    if (defined('DO_VIEWS') && DO_VIEWS === TRUE) {
        echo '<div class="row">';
            echo '<div class="col-lg-12">';
                echo '<div class="well well-lg text-primary"><center>This page was viewed <strong>'.$TotalViews.'</strong> times.</center></div>';
            echo '</div>';
        echo '</div>';
    }
?>
    </div>
  </body>
</html>
