<?php if (!defined('POST_COUNT')) exit('You cannot access this script directly.');
/** -------------------------------------------------------------------------------------------------------------------
 * Post Count
 * FreeVPS Post Count
 *
 * @author Sandu Liviu Catalin
 * @email slc(dot)universe(at)gmail(dot)com
 * @license Public Domain
**/

// Overloadable function to kill the application when something goes wrong.
if (!function_exists("_Kill"))
{
    function _Kill($msg)
    {
        exit( $msg );
    }
}

// Function that loads the configurations and provides access to the loaded options.
function _Cfg($Key, $Fallback)
{
    static $_Configs;
    if (empty($_Configs)) {
        if (!file_exists(ROOT.'inc'.DS.'config'.EXT) || !require_once(ROOT.'inc'.DS.'config'.EXT)) {
            _Kill("ERROR: Uable to include the configuration script.");
        } elseif (empty($_Configs) || !is_array($_Configs)) {
            _Kill("ERROR: Unable to detedct any valid options in the configuration script.");
        }
    } elseif (isset($_Configs[$Key])) {
        return $_Configs[$Key]; 
    }
    return $Fallback;
}

// Define a constant containing the myBB forum address to allow changing the address easily.
defined('SITE_ROOT_URL') ? NULL : define('SITE_ROOT_URL', _Cfg('website_root_url', 'http://freevps.us'));

// Forums to ignore from the post count
$IgnoredForums = array(
        'SPAM/Testing',
        'Introductions',
);

// Function to check the availability of the needed cURL functionality.
function cURL_Availability() 
{
    if( !function_exists('curl_init') &&
        !function_exists('curl_setopt') &&
        !function_exists('curl_exec') &&
        function_exists('curl_close') )
    { return false; } else { return true; }
}

// Prevent the script from continuing without the required cURL functionality.
if( !cURL_Availability() ) {
    _Kill('ERROR: The script cannot run without the cURL basic functions.');
}

// Define some cURL constants to have some global options throughout the code.
defined('USER_AGENT_URL') ? NULL : define('USER_AGENT_URL', _Cfg('user_agent_url', 'http://my-address/my-page.html'));
defined('USER_AGENT_NAME') ? NULL : define('USER_AGENT_NAME', _Cfg('user_agent_name', 'PostCountBot'));
defined('USER_AGENT_VERSION') ? NULL : define('USER_AGENT_VERSION', _Cfg('user_agent_version', '1.0'));
defined('USER_AGENT') ? NULL : define('USER_AGENT', 'Mozilla/5.0 (compatible; '.USER_AGENT_NAME.
                                        '/'.USER_AGENT_VERSION.'; +'.USER_AGENT_URL.')');

// Set up the cURL handle to emulate a crawler bot and to be able to browse hidden content.
function cURL_SetupHandle($CH, array $Opt = array())
{
    // Check the handle for validity
    if (!$CH) {
        _Kill('ERROR: Cannot set any options on an invalid cURL handle.');
    } else {
        curl_setopt($CH, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($CH, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($CH, CURLOPT_BINARYTRANSFER, TRUE);
        curl_setopt($CH, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($CH, CURLOPT_HEADER, FALSE);
        curl_setopt($CH, CURLOPT_USERAGENT, USER_AGENT);
    }
    // Apply custom user options
    if (!empty($Opt)) {
        curl_setopt_array($CH, $Opt);
    }
}

// Get the user name of the specified user id.
function GetUserInfo($ID)
{
    // Store the URL we need to retrieve into a variable, just in case.
    $URL = SITE_ROOT_URL.'/user-'.$ID.'.html';
    // Initialise a cURL handle
    $CH = curl_init($URL);
    
    // Check the handle to make sure it was created successfully.
    if (!$CH) {
        _Kill('ERROR: The cURL handle for the user profile page retrieval could not be created.');
    } else {
        cURL_SetupHandle($CH);
    }
    // Grab the user profile page content.
    $PageData = curl_exec($CH);
    
    // Check the return data to see if the page could be retrieved successfully.
    if (!$PageData) {
        _Kill('ERROR: The user profile page could not be retrieved by the cURL handle.');
    }
    // Create the DOMDocument object to transform the HTML string into a DOM structure.
    $DOC = new DOMDocument();
    // Disable any libXML parsing errors.
    libxml_use_internal_errors(true);
    
    // Load the retrieved user profile page HTML data into the DOMDocument object.
    if (!$DOC->loadHTML($PageData, LIBXML_NOWARNING)) {
        _Kill('ERROR: The HTML from user profile page could not be loaded into the DOMDocument object.');
    }
    // Create an DOMXpath object to help us navigate the DOM structure.
    $XPath = new DOMXpath($DOC);

    // Run a query to check weather the no results page was shown.
    $MsgElem = $XPath->query('/html/body/div/div[2]/table/tr[2]/td');
    
    // Check weather the DOM query returned a valid element in the DOM structure.
    if ($MsgElem->length <= 0) {
        // We actually expect it to return nothing if we want it to continue.
    } elseif (is_string($MsgElem->item(0)->nodeValue) && strpos($MsgElem->item(0)->nodeValue, 'The member you specified') !== FALSE) {
        // Exit the script as we have nothing to search for this user id.
        _Kill('ERROR: Unable to get the username of user id ('.$ID.') because the user is invalid or doesn\'t exist.');
    }
    
    // Let's create a temporary variable to store an array of the extracted information.
    $User = array(
        'userid' => $ID,
        'username' => '',
        'total_posts' => '',
        'reputation' => '',
        'points' => '',
        'score' => '',
        'referred' => '',
        'banned' => FALSE,
        'unapproved' => FALSE,
        'vps_owner' => FALSE,
        'sponsor' => FALSE,
        'staff' => '',
        'avatar' => '',
        'profile_link' => SITE_ROOT_URL.'/user-'.$ID.'.html'
    );
    
    // Run a query to grab the element that contains the username.
    $UserNameElem = $XPath->query('/html/body/div/div[2]/table/tr[3]/td/span[1]/strong/span');

    // Check weather the DOM query returned a valid element in the DOM structure. 
    if ($UserNameElem->length <= 0) {
        // Check weather this isn't a sponsor user in which case the XPath is different.
        $UserNameElem = $XPath->query('/html/body/div/div[2]/table/tr[3]/td/span[1]/strong/font/strong');
        // Check weather the DOM query returned a valid element in the DOM structure. 
        if ($UserNameElem->length <= 0) {
            // Check weather this isn't a banned user in which case the XPath is different.
            $UserNameElem = $XPath->query('/html/body/div/div[2]/table/tr[3]/td/span[1]/strong/s');
            // Check weather the DOM query returned a valid element in the DOM structure. 
            if ($UserNameElem->length <= 0) {
                // Check weather this isn't an unapproved user in which case the XPath is different.
                $UserNameElem = $XPath->query('/html/body/div/div[2]/table/tr[3]/td/span[1]/strong');
                // Check weather the DOM query returned a valid element in the DOM structure. 
                if ($UserNameElem->length <= 0) {
                    _Kill('ERROR: The DOM query for the user name element failed to retrieve a valid element.');
                } else {
                    // Unapproved users must be marked as such to use the correct XPaths later.
                    $User['unapproved'] = TRUE;
                }
            } else {
                // Banned users must be marked as such to use the correct XPaths later.
                $User['banned'] = TRUE;
            }
        } else {
            // Sponsors must be marked as such to use the correct XPaths later.
            $User['sponsor'] = TRUE;
        }


    }
    // Try to determine if the user is part of the staff or if it's a VPS owner by checking the name styling.
    if (!$User['sponsor'] && !$User['banned'] && !$User['unapproved']) {
        // Check the user element for a valid attribute containing the user type.
        if (!$UserNameElem->item(0)->hasAttribute('style')) {
             _Kill('ERROR: The user element has no "style" attribute containing the user type.');
        }

        // Save the value from the user element 'style' attribute to start validating it.
        $Type = $UserNameElem->item(0)->getAttribute('style');
        // Check weather the attribute data is in fact valid by checking for NULL data.
        if (!is_string($Type) || empty($Type)) {
             _Kill('ERROR: Unable to locate a valid user type in the user element (style) attribute.');
        }
        // Cut the color from the user style and trim any whitespaces to check what type of user it is.
        $Type = trim(substr($Type, strpos($Type, ':')));
        // Check wheather the user is registered user or an administrator by checkhing weather they don't have hexadecimal color styles.
        if (strpos($Type, 'blue') !== FALSE) {
            $User['vps_owner'] = TRUE;
        } elseif (strpos($Type, 'green') !== FALSE) {
           $User['staff'] = 'admin';
        } elseif (strpos($Type, '783206') !== FALSE) {
            $User['staff'] = 'moderator';
        } elseif (strpos($Type, '7E0B70') !== FALSE) {
            $User['staff'] = 'retired';
        }
    }
    // Get the username from the first DOMNode in the DOMNodeList object returned by the XPath query.
    $User['username'] = $UserNameElem->item(0)->nodeValue;
    
    // Check weather the element value is a valid string suitable for the username.
    if (!is_string($User['username']) || empty($User['username'])) {
        _Kill('ERROR: The username element retrieved from the user profile page DOM is invalid.');
    }
    
    // Run a query to grab the element that contains the posts information.
    $PostInfoElem = $XPath->query('/html/body/div/div[2]/table[2]/tr/td/table/tr[4]/td[2]');
    
    // Check weather the DOM query returned a valid element in the DOM structure.
    if ($PostInfoElem->length <= 0) {
        _Kill('ERROR: The DOM query for the post information element failed to retrieve a valid element.');
    } elseif (!is_string($PostInfoElem->item(0)->nodeValue) || empty($PostInfoElem->item(0)->nodeValue)) {
        _Kill('ERROR: The post information element retrieved from the user profile page DOM is invalid.');
    } else {
        // Variable to store the final total posts number.
        $Posts = '';
        // Value to store the posts information string extracted from the DOM element.
        $StrVal = $PostInfoElem->item(0)->nodeValue;
        
        // Loop through each character from the retrieved post information string from the beginning and extract the numbers.
        for ($i = 0; $i <= strlen($StrVal); $i++)
        {
            // Check weather we are dealing with a thousands of posts in which case they're separated by a ',' comma.
            if (substr($StrVal, $i, 1) === ',') {
                // Just ignore the comma and continue scanning for numbers.
                continue;
            } elseif (is_numeric(substr($StrVal, $i, 1))) {
                // Don't do += because it will mathematically recognise and add the numbers from the strings.
                $Posts = $Posts . substr($StrVal, $i, 1);
            } else {
                // Exit on the first non-numeric or non-comma character in the string.
                break;
            }
        }
        // Now validate the extracted number and if it's a number then store it otherwise exit.
        if ((!empty($Posts) || $Posts == '0') && is_numeric($Posts)) {
            $User['total_posts'] = intval($Posts);
        } else {
            _Kill('ERROR: Unable to retrieve a valid post number from the post information element.');
        }
    }
    
    // Banned or unapproved users don't have a reputation field which means we skip it.
    if (!$User['banned'] && !$User['unapproved']) {
        // Run a query to grab the element that contains the user reputation.
        $UserRepElem = $XPath->query('/html/body/div/div[2]/table[2]/tr/td/table/tr[7]/td[2]/strong');

        // Check weather the DOM query returned a valid element in the DOM structure.
        if ($UserRepElem->length <= 0) {
            // Means that there is a possibility for user account to not be activated or be banned.
            $User['reputation'] = 0;
            // Banned or unapproved users must be specifically marked as such to correct the XPaths later.
            // (banned and unapproved users don't have all the fields of a regular user)
            $User['banned'] = TRUE;
            $User['unapproved'] = TRUE;
        } elseif (!is_numeric($UserRepElem->item(0)->nodeValue) || empty($UserRepElem->item(0)->nodeValue)) {
            // Prevent empty() from considering a string '0' to be empty.
            if ($UserRepElem->item(0)->nodeValue == '0') {
                $User['reputation'] = intval($UserRepElem->item(0)->nodeValue);
            } else {
                _Kill('ERROR: The user reputation element retrieved from the user profile page DOM is invalid.');
            }
        } else {
            $User['reputation'] = intval($UserRepElem->item(0)->nodeValue);
        }
    } else {
        $User['reputation'] = 0;
    }
    
    // Run a query to grab the element that contains the user points.
    $UserPtsElem;
    // Banned or unapproved users don't have reputation field which means we need to use a different XPath.
    if (!$User['banned'] && !$User['unapproved']) {
        $UserPtsElem = $XPath->query('/html/body/div/div[2]/table[2]/tr/td/table/tr[8]/td[2]/a');
    } else {
        $UserPtsElem = $XPath->query('/html/body/div/div[2]/table[2]/tr/td/table/tr[7]/td[2]/a');
    }
    
    //Check weather the DOM query returned a valid element in the DOM structure.
    if ($UserPtsElem->length <= 0) {
        _Kill('ERROR: The DOM query for the user points element failed to retrieve a valid element.');
    } elseif (strpos($UserPtsElem->item(0)->nodeValue, ',') == FALSE &&
                (!is_numeric($UserPtsElem->item(0)->nodeValue) || empty($UserPtsElem->item(0)->nodeValue)))
    {
        // Prevent empty() from considering a string '0' to be empty.
        if ($UserPtsElem->item(0)->nodeValue == '0') {
            $User['points'] = intval($UserPtsElem->item(0)->nodeValue);
        } else {
            _Kill('ERROR: The user points element retrieved from the user profile page DOM is invalid.');
        }
    } else {
        // Just replace the commas with empty spaces and we have our number.
        $User['points'] = intval(str_replace(',', '', $UserPtsElem->item(0)->nodeValue));
    }
    
    // Run a query to grab the element that contains the user score.
    $UserScoreElem;
    // Banned or unapproved users don't have reputation field which means we need to use a different XPath.
    if (!$User['banned'] && !$User['unapproved']) {
        $UserScoreElem = $XPath->query('/html/body/div/div[2]/table[2]/tr/td/table/tr[9]/td[2]/a');
    } else {
        $UserScoreElem = $XPath->query('/html/body/div/div[2]/table[2]/tr/td/table/tr[8]/td[2]/a');
    }
    
    //Check weather the DOM query returned a valid element in the DOM structure.
    if ($UserScoreElem->length <= 0) {
        _Kill('ERROR: The DOM query for the user score element failed to retrieve a valid element.');
    } elseif (!is_numeric($UserScoreElem->item(0)->nodeValue) || empty($UserScoreElem->item(0)->nodeValue)) {
        // Prevent empty() from considering a string '0' to be empty.
        if ($UserScoreElem->item(0)->nodeValue == '0') {
            $User['score'] = intval($UserScoreElem->item(0)->nodeValue);
        } else {
            _Kill('ERROR: The user score element retrieved from the user profile page DOM is invalid.');
        }
    } else {
        // Check for float number first because is_numeric() returns true for both integer or floating point number.
        if (preg_match('/[+-]?(?=\d*[.eE])(?=\.?\d)\d*\.?\d*(?:[eE][+-]?\d+)?/', $UserScoreElem->item(0)->nodeValue)) {
            // We know that the value is know as a float so let's typecast the string to a floating point number.
            $User['score'] = (float)$UserScoreElem->item(0)->nodeValue;
        } else {
            $User['score'] = intval($UserScoreElem->item(0)->nodeValue);
        }
    }
    
    // Run a query to grab the element that contains the user referred members.
    $ReferredElem = $XPath->query('/html/body/div/div[2]/table[2]/tr/td/table/tr[6]/td[2]');

    // Check weather the DOM query returned a valid element in the DOM structure.
    if ($ReferredElem->length <= 0) {
        _Kill('ERROR: The DOM query for the referred users element failed to retrieve a valid element.');
    } elseif (!is_numeric($ReferredElem->item(0)->nodeValue) || empty($ReferredElem->item(0)->nodeValue)) {
        // Prevent empty() from considering a string '0' to be empty.
        if ($ReferredElem->item(0)->nodeValue == '0') {
            $User['referred'] = intval($ReferredElem->item(0)->nodeValue);
        } else {
            _Kill('ERROR: The referred users element retrieved from the user profile page DOM is invalid.');
        }
    } else {
        $User['referred'] = intval($ReferredElem->item(0)->nodeValue);
    }

    // Run a query to grab the element that contains the user avatar link.
    $AvatarElem = $XPath->query('/html/body/div/div[2]/table/tr[3]/td[2]/img');
    // Check weather the DOM query returned a valid element in the DOM structure.
    if ($AvatarElem->length <= 0) {
        // Means that the user has no avatar and we leave it blank.
    // Check the avatar element for a valid attribute containing the avatar link.
    } elseif (!$AvatarElem->item(0)->hasAttribute('src')) {
        _Kill('ERROR: The avatar element has no "src" attribute containing the avatar link.');
    } else {
        // Save the value from the avatar element 'src' attribute to start validating it.
        $Link = $AvatarElem->item(0)->getAttribute('src');
        // Check weather the attribute data is in fact valid by checking for NULL data.
        if (!is_string($Link) || empty($Link)) {
             _Kill('ERROR: Unable to locate a valid avatar link in the avatar element (src) attribute.');
        }
        // Try to determine if this is either a local or remote avatar link.
        if (substr($Link, 0, 2) == './') {
            $User['avatar'] = SITE_ROOT_URL.'/uploads/avatars/avatar_'.$ID;
            // Determine the image extension.
            $User['avatar'] .= substr($Link, strrpos($Link, '.'));
        } else {
            $User['avatar'] = $Link;
        }
    }
    // Admins, Mods, Sponsors, Retired Staff can't be verified if they own a VPS and we must make sure they have no VPS.
    if ($User['staff'] == 'admin' || $User['staff'] == 'moderator' || $User['staff'] == 'retired' || $User['sponsor'] == TRUE) {
        $User['vps_owner'] = FALSE;
    // Banned, Unapproved users can't have a VPS and we must make sure they don't have one.
    } elseif ($User['banned'] == TRUE || $User['unapproved'] == TRUE) {
        $User['vps_owner'] = FALSE;
    // Users with less than 30 posts and less than 55 score can't have a VPS and we must make sure they don't have one.
    } elseif ($User['total_posts'] < 30 || $User['score'] < 55.0) {
        $User['vps_owner'] = FALSE;
    }
    // Return the extracted user informations.
    return $User;
}

// Get the post count for this month of the specified user.
function GetPostCount($ID)
{
    // Get the username of the specified user id so we can track it's posts.
    $User = GetUserInfo($ID);
    // Banned or unapproved users can't make new posts or apply for a VPS and therefore a post count isn't necessary.
    if ($User['banned'] || $User['unapproved']) {
        // Fill with blank data.
        $User['postcount'] = 0;
        $User['postlist'] = array();
        // Return only the profile details.
        return $User;
    }
    // Store the URL we need to retrieve into a variable, just in case.
    $URL = SITE_ROOT_URL.'/search.php?action=finduser&uid='.$ID;
    // Initialise a cURL handle
    $CH = curl_init($URL);
    
    // Check the handle to make sure it was created successfully.
    if (!$CH) {
        _Kill('ERROR: The cURL handle for the post search page retrieval could not be created.');
    } else {
        cURL_SetupHandle($CH);
    }
    // Grab the post search page content.
    // Were not interested in the returned data so we wont validate or save it.
    curl_exec($CH);
    // Grab the effective URL containing the search id to be able to sort the posts by date.
    $EURL = curl_getinfo($CH, CURLINFO_EFFECTIVE_URL);
    
    // Check to see if we have a valid search id in the effective URL.
    if (!is_string($EURL) || empty($EURL)) {
        _Kill('ERROR: Unable to obtain a valid effective URL from the post search page.');
    } else {
        // Split the effective URL into specific URI segments.
        $URI = parse_url($EURL);
        
        // Check to see if the query segment isn't missing from the effective URL.
        if (!isset($URI['query']) || !is_string($URI['query']) || empty($URI['query'])) {
            _Kill('ERROR: The query string segment is missing from the effective URL.');
        }
        
        // Check to see if we have a valid search id.
        if (!preg_match('/\&sid\=[a-zA-Z0-9_]+/', $URI['query'])) {
            _Kill('ERROR: Unable to find a valid search id in the effective URL query string.');
        }
    }
    // Variable to store the amount of posts the user has made this month.
    $PostsThisMonth = 0;
    // Variable to store a list of posts and their links
    $PostList = array();
    
    // Set the basic cURL options we'll be needing in the loop.
    cURL_SetupHandle($CH);
    
    // Create the DOMDocument object to transform the HTML string into a DOM structure.
    $DOC = new DOMDocument();
    // Disable any libXML parsing errors.
    libxml_use_internal_errors(true);
    
    // Create a variable to indicate weather we reached the end of this month scope to end the search.
    $MonthScope = TRUE;
    // Create a variable to indicate what page we must fetch the beginning of the loop.
    $SearchPage = 1;
    // Create a variable to indicate the last page so the script won't ask for more pages than the user has.
    
    // Start crawling the post search page for user posts.
    while ($MonthScope)
    {
        // Bring the ignored forums list into this scope.
        global $IgnoredForums;
        
        // Update the cURL handle to the effective URL and sort the post search in descending order.
        curl_setopt($CH, CURLOPT_URL, $EURL.'&sortby=dateline&order=desc&page='.$SearchPage);
        // Grab the post search page content.
        $PageData = curl_exec($CH);
        
        // Check the return data to see if the page could be retrieved successfully.
        if (!$PageData) {
            _Kill('ERROR: The post search page ('.$SearchPage.') data could not be retrieved by the cURL handle.');
        }
        
        // Load the retrieved post search page HTML data into the DOMDocument object.
        if (!$DOC->loadHTML($PageData, LIBXML_NOWARNING)) {
            _Kill('ERROR: The HTML data from post search page ('.$SearchPage.') could not be loaded into the DOMDocument object.');
        }
        // Create an DOMXpath object to help us navigate the DOM structure.
        $XPath = new DOMXpath($DOC);
        
        // Run a query to check weather the no results page was shown.
        $MsgElem = $XPath->query('/html/body/div/div[2]/table/tr[2]/td');
        // Check weather the DOM query returned a valid element in the DOM structure.
        if ($MsgElem->length <= 0) {
            // We actually expect it to return nothing if we want it to continue.
        } elseif (is_string($MsgElem->item(0)->nodeValue) && substr($MsgElem->item(0)->nodeValue, 0, 5) == 'Sorry') {
            // Just toggle the MonthEnd viariable to exit the loop and return whatever data we collected.
            $MonthScope = FALSE;
            // Break out of the loop.
            break;
        }

        // Grab the number of elements we have to search through for posts.
        $Elements = $XPath->query('/html/body/div/div[2]/table[2]/tr');

        // Check weather the DOM query returned a valid element in the DOM structure.
        if ($Elements->length <= 0) {
            _Kill('ERROR: The DOM query for the user post elements failed to retrieve a valid element.');
        }

        // Skip the first two elements because they are not posts and start verifying each element incrementally for valid post.
        for ($i = 3; $i <= $Elements->length; $i++) {
            // Run a query to grab the element that contains the post author.
            $PostAuthElem = $XPath->query('/html/body/div/div[2]/table[2]/tr['.$i.']/td[4]/a');
            
            // Check weather this is a post by getting it's author and checking weather it matches or not.
            if ($PostAuthElem->length <= 0) {
                continue; // Ignoring posts from unknown users! (skip element)
            } elseif (!is_string($PostAuthElem->item(0)->nodeValue) || empty($PostAuthElem->item(0)->nodeValue)) {
                continue; // Ignoring posts from unknown users! (skip element)
            } elseif ($PostAuthElem->item(0)->nodeValue !== $User['username']) {
                continue; // Not a post of this user! (skip element)
            }
            // Run a query to grab the element that contains the post forum.
            $PostForumElem = $XPath->query('/html/body/div/div[2]/table[2]/tr['.$i.']/td[5]/a');
            
            // Check weather this is a post by getting it's forum and checking weather it's ignored or not.
            if ($PostForumElem->length <= 0) {
                continue; // Ignoring posts from unknown forums! (skip element)
            } elseif (!is_string($PostForumElem->item(0)->nodeValue) || empty($PostForumElem->item(0)->nodeValue)) {
                continue; // Ignoring posts from unknown forums! (skip element)
            } elseif (in_array($PostForumElem->item(0)->nodeValue, $IgnoredForums)) {
                continue; // Skipping posts from ignored forums! (skip element)
            }

            // Run a query to grab the element that contains the post date.
            $PostDateElem = $XPath->query('/html/body/div/div[2]/table[2]/tr['.$i.']/td[8]/span');
            
            // Check weather this is a post by getting it's date and checking weather it's valid or not.
            if ($PostDateElem->length <= 0) {
                continue; // Ignoring posts with unknown dates! (skip element)
            } elseif (!is_string($PostDateElem->item(0)->nodeValue) || empty($PostDateElem->item(0)->nodeValue)) {
                continue; // Ignoring posts with unknown dates! (skip element)
            }

            // Create a variable to store the post time stamp.
            $TimeStamp = NULL;

            // Extract the time stamp from the time string of the post.
            if (substr($PostDateElem->item(0)->nodeValue, 0, 3) === 'Yes' || substr($PostDateElem->item(0)->nodeValue, 0, 3) === 'Tod') {
                // Try to convert the date string to a time stamp.
                $TimeStamp = strtotime($PostDateElem->item(0)->nodeValue);
            } else {
                // Try to convert the date string to a DateTime object.
                $DTime = DateTime::createFromFormat('m-d-Y, h:i A', $PostDateElem->item(0)->nodeValue);
                
                // Get the time stamp from the DateTime object.
                $TimeStamp = $DTime->getTimestamp();
            }

            // Get the beginning and ending of the month.
            $MonthStart = mktime(0, 0, 0, date("n"), 1);
            $MonthEnd = mktime(23, 59, 59, date("n"), date("t"));

            // Check weather the post time stamp is inside the month scope.
            if ($TimeStamp >= $MonthStart && $TimeStamp <= $MonthEnd) {
                // Inside the month scope!
                $PostsThisMonth++;
                
                // Temporary variable to store an array with post information.
                $Post = $Array = array(
                	'forum_title' => $PostForumElem->item(0)->nodeValue,
                	'forum_link' => '',
                	'time_stamp' => $TimeStamp,
                	'thread_title' => '',
                	'thread_link' => '',
                	'post_title' => '',
                    'post_link' => '',
                	'post_preview' => ''
                );
                
                // Run a query to grab the elements that contains the post and thread name and link.
                $PostThreadElem = $XPath->query('/html/body/div/div[2]/table[2]/tr['.$i.']/td[3]/span/a');
                // Check weather this is a valid post by getting it's post and thread and checking weather it's valid or not.
                if ($PostThreadElem->length <= 1) {
                    _Kill('ERROR: Unable to retrieve the post and thread title and link element.');
                // Check the thread element for a valid thread name.
                } elseif (!is_string($PostThreadElem->item(0)->nodeValue) || empty($PostThreadElem->item(0)->nodeValue)) {
                    _Kill('ERROR: Unable to locate a valid title in the thread element.');
                // Check the post element for a valid post name.
                } elseif (!is_string($PostThreadElem->item(1)->nodeValue) || empty($PostThreadElem->item(1)->nodeValue)) {
                	_Kill('ERROR: Unable to locate a valid title in the post element.');
                // Save the post and thread title into the temporary variable.
                } else {
                	$Post['thread_title'] = $PostThreadElem->item(0)->nodeValue;
                	$Post['post_title'] = $PostThreadElem->item(1)->nodeValue;
                }

                // Check the thread element for a valid attribute containing the thread link.
                if (!$PostThreadElem->item(0)->hasAttributes()) {
                	_Kill('ERROR: The thread element has no attributes containing the thread link.');
                } else {
                	// Save the value from the thread element 'href' attribute to start validating it.
                	$Post['thread_link'] = $PostThreadElem->item(0)->getAttribute('href');
                	// Check weather the thread element 'href' attribute contains a valid thread link.
                	if (!is_string($Post['thread_link']) || empty($Post['thread_link'])) {
                		_Kill('ERROR: Unable to locate a valid link in the thread element (href) attribute.');
                	// Check weather the thread element 'href' attribute contains a valid thread id.
                	} elseif (!preg_match('/thread-(\d)+\.html/', $Post['thread_link'])) {
                		_Kill('ERROR: Unable to locate a valid id in the thread element (href) attribute.');
                	}
                    // Prepend the site address to complete the URL address.
                    $Post['thread_link'] = SITE_ROOT_URL.'/'.$Post['thread_link'];
                }

                // Check the post element for a valid attribute containing the post link.
                if (!$PostThreadElem->item(1)->hasAttributes()) {
                	_Kill('ERROR: The thread element has no attributes containing the post link.');
                } else {
                	// Save the value from the post element 'href' attribute to start validating it.
                	$Post['post_link'] = $PostThreadElem->item(1)->getAttribute('href');
                	// Check weather the post element 'href' attribute contains a valid post link.
                	if (!is_string($Post['post_link']) || empty($Post['post_link'])) {
                		_Kill('ERROR: Unable to locate a valid link in the post element (href) attribute.');
                	// Check weather the post element 'href' attribute contains a valid post id.
                	} elseif (!preg_match('/thread\-(\d)+\-post\-(\d)+\.html\#pid(\d)+/', $Post['post_link'])) {
                		_Kill('ERROR: Unable to locate a valid post id in the post element (href) attribute.');
                	}
                    // Prepend the site address to complete the URL address.
                    $Post['post_link'] = SITE_ROOT_URL.'/'.$Post['post_link'];
                }
                
                // Check the forum element for a valid attribute containing the forum link.
                if (!$PostForumElem->item(0)->hasAttributes()) {
                	_Kill('ERROR: The forum element has no attributes containing the forum link.');
                } else {
                	// Save the value from the forum element 'href' attribute to start validating it.
                	$Post['forum_link'] = $PostForumElem->item(0)->getAttribute('href');
                	// Check weather the forum element 'href' attribute contains a valid forum link.
                	if (!is_string($Post['forum_link']) || empty($Post['forum_link'])) {
                		_Kill('ERROR: Unable to locate a valid link in the forum element (href) attribute.');
                	// Check weather the forum element 'href' attribute contains a valid forum id.
                	} elseif (!preg_match('/forum\-(\d)+\.html/', $Post['forum_link'])) {
                		_Kill('ERROR: Unable to locate a valid forum id in the forum element (href) attribute.');
                	}
                    // Prepend the site address to complete the URL address.
                    $Post['forum_link'] = SITE_ROOT_URL.'/'.$Post['forum_link'];
                }
                                
                // Run a query to grab the elements that contains the post and thread name and link.
                $PostPreviewElem = $XPath->query('/html/body/div/div[2]/table[2]/tr['.$i.']/td[3]/table/tr/td/span/em');
                // Check weather this is a post by getting it's preview and checking weather it's valid or not.
                if ($PostPreviewElem->length <= 0) {
                    _Kill('ERROR: Unable to retrieve the post preview element.');
                } elseif (!is_string($PostPreviewElem->item(0)->nodeValue) || empty($PostPreviewElem->item(0)->nodeValue)) {
                    Kill('ERROR: Unable to locate a valid preview in the post preview element.');
                } else {
                    $Post['post_preview'] = $PostPreviewElem->item(0)->nodeValue;
                }

                // Let's add the collected data to the PostList variable.
                $PostList[] = $Post;
            } else {
                // Outside the month scope!
                $MonthScope = FALSE;
                // Skip the rest of the elements.
                break;
            }
        }
        // Check weather we reached the end of the month scope in the element iteration loop.
        if ($MonthScope !== FALSE) {
            // Increase the page number so we start crawling the next page on the next page iteration loop.
            $SearchPage++;
        } else {
            // We're outside the month scope so let's break the loop and return our current values.
            break;
        }
    }
    // Add more fields to the user details.
    $User['postcount'] = $PostsThisMonth;
    $User['postlist'] = empty($PostList) ? array() : $PostList;
    // Return and array containing all the collected user details.
    return $User;
}

