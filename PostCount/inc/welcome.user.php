<?php if(!defined('POST_COUNT')) exit('Direct access is denied!'); ?>
<div class="page-header">
    <h1>Welcome <?php
            echo '<span class=""';
            // Style the username tag accorting to the user type.
            if (USER_TYPE == 'blocked') { echo ' style="color: #FF2D47">'; }
            elseif (USER_TYPE == 'normal') { echo ' style="color: #3283CD">'; }
            elseif (USER_TYPE == 'owner') { echo ' style="color: #2740FF">'; }
            elseif (USER_TYPE == 'sponsor') { echo ' style="color: #DAA520">'; }
            elseif (USER_TYPE == 'admin') { echo ' style="color: #4EA34E">'; }
            elseif (USER_TYPE == 'moderator') { echo ' style="color: #783206">'; }
            elseif (USER_TYPE == 'retired') { echo ' style="color: #7E0B70">'; }
            else { echo '>'; }
            // Finaly output the user name.
            echo $User['username'].'</span>';
        ?></h1>
</div>