<div class="col-lg-2 col-md-3 col-sm-4 col-xs-12">
    <?php
            echo '<div class="panel panel-default"';
            // Style the username tag accorting to the user type.
            if (USER_TYPE == 'blocked') { echo ' style="border-color: #FF2D47">'; }
            elseif (USER_TYPE == 'normal') { echo ' style="border-color: #3283CD">'; }
            elseif (USER_TYPE == 'owner') { echo ' style="border-color: #2740FF">'; }
            elseif (USER_TYPE == 'sponsor') { echo ' style="border-color: #DAA520">'; }
            elseif (USER_TYPE == 'admin') { echo ' style="border-color: #4EA34E">'; }
            elseif (USER_TYPE == 'moderator') { echo ' style="border-color: #783206">'; }
            elseif (USER_TYPE == 'retired') { echo ' style="border-color: #7E0B70">'; }
            else { echo '>'; }
            // End the line.
            echo PHP_EOL;
    ?>
        <table class="table table-condensed">
            <tr>
              <img class="center-block img-thumbnail" alt="Avatar" src="<?php echo (empty($User['avatar']) ? SITE_ROOT_URL.'/images/avatars/invalid_url.gif' : $User['avatar']); ?>" style="min-width:150px;min-height:150px;max-width:160px;max-height:160px;">
            </tr>
            <tr>
                <td style="text-align: center"><?php
                echo '<span';
                // Style the username tag accorting to the user type.
                if (USER_TYPE == 'blocked') {echo ' style="color: #FF2D47">Blocked Member</span>'; }
                elseif (USER_TYPE == 'normal') {echo ' style="color: #3283CD">Registered Member</span>'; }
                elseif (USER_TYPE == 'owner') {echo ' style="color: #2740FF">VPS Owner</span>'; }
                elseif (USER_TYPE == 'sponsor') {echo ' style="color: #DAA520">Sponsor</span>'; }
                elseif (USER_TYPE == 'admin') {echo ' style="color: #4EA34E">Administrator</span>'; }
                elseif (USER_TYPE == 'moderator') {echo ' style="color: #783206">Moderator</span>'; }
                elseif (USER_TYPE == 'retired') {echo ' style="color: #7E0B70">Retired Staff</span>'; }
                else {echo '>Unknown Type</span>'; }
              ?></td>
            </tr>
        </table>
    </div>
</div>
<div class="col-lg-10 col-md-9 col-sm-8 col-xs-12">
    <?php
        echo '<div class="panel panel-default"';
        if (USER_TYPE == 'blocked') { echo ' style="border-color: #FF2D47">'; }
        elseif (USER_TYPE == 'normal') { echo ' style="border-color: #3283CD">'; }
        elseif (USER_TYPE == 'owner') { echo ' style="border-color: #2740FF">'; }
        elseif (USER_TYPE == 'sponsor') { echo ' style="border-color: #DAA520">'; }
        elseif (USER_TYPE == 'admin') { echo ' style="border-color: #4EA34E">'; }
        elseif (USER_TYPE == 'moderator') { echo ' style="border-color: #783206">'; }
        elseif (USER_TYPE == 'retired') { echo ' style="border-color: #7E0B70">'; }
        else { echo '>'; }
        // End the line.
        echo PHP_EOL;
    ?>
        <table class="table table-condensed">
            <tr>
              <td>Total Posts</td>
              <td><?php echo $User['total_posts']; ?></td>
            </tr>
            <tr>
              <td>Reputation</td>
              <td><?php echo $User['reputation']; ?></td>
            </tr>
            <tr>
              <td>Total Score</td>
              <td><?php echo $User['score']; ?></td>
            </tr>
            <tr>
              <td>Total Points</td>
              <td><?php echo $User['points']; ?></td>
            </tr>
            <tr>
              <td>Referred Users</td>
              <td><?php echo $User['referred']; ?></td>
            </tr>
        </table>
    </div>
</div>