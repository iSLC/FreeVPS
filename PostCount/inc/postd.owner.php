<?php if(!defined('POST_COUNT')) exit('Direct access is denied!'); ?>
<div class="col-lg-12">
<?php
	// Get the monthly required posts.
	$Required = _Cfg('required_monthly_posts', 20);
	// Create a temporary variable to hold a rounded version of the current monthly posts.
	$PostCount = round($User['postcount'], 2);
	// Generate a table of post numbers to check for when displaying any messages.
	$Msg = array(
		round(($Required / 4), 2),
		round(($Required / 2), 2),
		round(($Required / 2) + ($Required / 4), 2),
		round($Required, 2),
		round(($Required + ($Required / 4)), 2),
		round(($Required + ($Required / 2)), 2),
		round(($Required + (($Required / 2) * 2)), 2),
		round(($Required + (($Required / 2) * 3)), 2),
		round(($Required + (($Required / 2) * 4)), 2),
		round(($Required + (($Required / 2) * 5)), 2),
		round(($Required + (($Required / 2) * 6)), 2),
		round(($Required + (($Required / 2) * 7)), 2),
		round(($Required + (($Required / 2) * 8)), 2)
	);
	// Create a variable to mark the completed goals for later styling.
	$PercFill = array(FALSE, FALSE, FALSE, FALSE);
	
	// How much posts does the user needs in order to fill the monthly requirement.
	$Needs = ($PostCount < $Required) ? ($Required - $PostCount) : 0;
	// How many extra posts did the user made over the monthly requirement.
	$Extra = ($PostCount > $Required) ? ($PostCount - $Required) : 0;

	// Display an information message according to the monthly posts.
	if ($PostCount <= $Msg[0]) {
		echo '<div class="alert alert-danger"><strong>Oh no!</strong> Your current monthly posts are way too low. You need '.$Needs.' more in order to keep the VPS for the next month.</div>';
		$PercFill = array(FALSE, FALSE, FALSE, FALSE);
	} elseif ($PostCount <= $Msg[1]) {
		echo '<div class="alert alert-warning"><strong>Oh snap!</strong> You don\'t have enough posts to keep your VPS. You need at least '.$Needs.' more to be able keep it for the next month.</div>';
		$PercFill = array(TRUE, FALSE, FALSE, FALSE);
	} elseif ($PostCount <= $Msg[2]) {
		echo '<div class="alert alert-info" style="border-color: #5FB5E3;background-image: linear-gradient(to bottom,#99D6F6 0,#64BEE8 100%)"><strong>Heads up!</strong> You still don\'t have enough posts to fill the monthly requirement. You still need at least '.$Needs.' more to keep the VPS for the next month.</div>';
		$PercFill = array(TRUE, TRUE, FALSE, FALSE);
	} elseif ($PostCount < $Msg[3]) {
		echo '<div class="alert alert-info"><strong>Almost there!</strong> You\'re almost close to completing your monthly posts. You just need '.$Needs.' more in order to keep the VPS for the next month.</div>';
		$PercFill = array(TRUE, TRUE, TRUE, FALSE);
	} elseif ($PostCount >= $Msg[3] && $PostCount <= $Msg[4]) {
		echo '<div class="alert alert-success"><strong>Well done!</strong> You have completed your monthly posts requirement. You can continue to use the VPS for the next month.</div>';
		$PercFill = array(TRUE, TRUE, TRUE, TRUE);
	} elseif ($PostCount <= $Msg[5]) {
		echo '<div class="alert alert-success"><strong>Congratulations!</strong> You have completed your monthly posts requirement and more. You can continue to use the VPS for the next month.</div>';
		$PercFill = array(TRUE, TRUE, TRUE, TRUE);
	} elseif ($PostCount <= $Msg[6]) {
		echo '<div class="alert alert-success"><strong>Good work!</strong> You have completed your monthly posts requirement and way more. You can continue to use the VPS for the next month without worries.</div>';
		$PercFill = array(TRUE, TRUE, TRUE, TRUE);
	} elseif ($PostCount <= $Msg[7]) {
		echo '<div class="alert alert-success"><strong>Great job!</strong> You have completed your monthly posts requirement and a much more. You can continue to use the VPS for the next month without any worries.</div>';
		$PercFill = array(TRUE, TRUE, TRUE, TRUE);
	} elseif ($PostCount <= $Msg[8]) {
		echo '<div class="alert alert-success"><strong>Magnificent!</strong> You have way more than the monthly posts requirement. You can safely continue to use the VPS for the next month without any worries.</div>';
		$PercFill = array(TRUE, TRUE, TRUE, TRUE);
	} elseif ($PostCount <= $Msg[9]) {
		echo '<div class="alert alert-success"><strong>Breathtaking!</strong> You just massacred the monthly posts requirement. You can safely continue to use the VPS for the next month without any worries.</div>';
		$PercFill = array(TRUE, TRUE, TRUE, TRUE);
	} elseif ($PostCount <= $Msg[10]) {
		echo '<div class="alert alert-success"><strong>Hold on!</strong> Are you trying to win a contest of something? You have enough posts to continue using the VPS for the next month without any worries.</div>';
		$PercFill = array(TRUE, TRUE, TRUE, TRUE);
	} elseif ($PostCount <= $Msg[11]) {
		echo '<div class="alert alert-success"><strong>Stop, Just Stop!</strong> Are you trying to get confused for a spam bot something? You have more than enough posts to continue using the VPS for the next month.</div>';
		$PercFill = array(TRUE, TRUE, TRUE, TRUE);
	} elseif ($PostCount <= $Msg[12]) {
		echo '<div class="alert alert-success"><strong>Unstoppable!</strong> You definitely need some medical attention or something. You have way more than enough posts to continue using the VPS for the next month.</div>';
		$PercFill = array(TRUE, TRUE, TRUE, TRUE);
	} else {
		echo '<div class="alert alert-success"><strong>Unbelievable!</strong> There\'s just no way to describe the mental sickness inside you. You have way more than enough posts to continue using the VPS for the next month.</div>';
		$PercFill = array(TRUE, TRUE, TRUE, TRUE);
	}
?>
</div>

<div class="col-lg-12">
<?php
	$Percent = array(0.0, 0.0, 0.0, 0.0, 0.0);
	$PercVal = array('', '', '', '', '');
	// Try to find the percentage of the post count for the progress bar.
	if ($PostCount <= $Msg[0]) {
		$Percent[0] = round(($PostCount / $Msg[3]) * 100, 2);
		$PercVal[0] = $PostCount;
	} elseif ($PostCount <= $Msg[1]) {
		$Percent[0] = round(($Msg[0] / $Msg[3]) * 100, 2);
		$Percent[1] = round((($PostCount - $Msg[0]) / $Msg[3]) * 100, 2);
		$PercVal[1] = $PostCount;
	} elseif ($PostCount <= $Msg[2]) {
		$Percent[0] = round(($Msg[0] / $Msg[3]) * 100, 2);
		$Percent[1] = round((($Msg[2] - $Msg[1]) / $Msg[3]) * 100, 2);
		$Percent[2] = round((($PostCount - $Msg[1]) / $Msg[3]) * 100, 2);
		$PercVal[2] = $PostCount;
	} elseif ($PostCount <= $Msg[3]) {
		$Percent[0] = round(($Msg[0] / $Msg[3]) * 100, 2);
		$Percent[1] = round((($Msg[2] - $Msg[1]) / $Msg[3]) * 100, 2);
		$Percent[2] = round((($Msg[3] - $Msg[2]) / $Msg[3]) * 100, 2);
		$Percent[3] = round((($PostCount - $Msg[2]) / $Msg[3]) * 100, 2);
		$PercVal[3] = $PostCount;
	} else {
		$Percent[0] = round( ($Msg[0] / $PostCount) * 100, 2);
		$Percent[1] = round( (($Msg[1] - $Msg[0]) / $PostCount) * 100, 2);
		$Percent[2] = round( (($Msg[2] - $Msg[1]) / $PostCount) * 100, 2);
		$Percent[3] = round((($Msg[3] - $Msg[2]) / $PostCount) * 100, 2);
		$PercVal[3] = ($PostCount > $Msg[3]) ? '' : $PostCount;
		$Percent[4] = ($PostCount > $Msg[3]) ?
						round( (100 - ($Percent[0] + $Percent[1] + $Percent[2] + $Percent[3]) ), 2) : round(0, 2);
		$PercVal[4] = ($PostCount > $Msg[3]) ? $PostCount : '';
	}
?>
	<div class="progress progress-striped active">
	  <div class="progress-bar progress-bar-danger" style="width: <?php echo $Percent[0]; ?>%">
	    <span><?php echo $PercVal[0]; ?></span>
	  </div>
	  <div class="progress-bar progress-bar-warning" style="width: <?php echo $Percent[1]; ?>%">
	    <span><?php echo $PercVal[1]; ?></span>
	  </div>
	  <div class="progress-bar progress-bar-default" style="width: <?php echo $Percent[2]; ?>%">
	    <span><?php echo $PercVal[2]; ?></span>
	  </div>
	  <div class="progress-bar progress-bar-info" style="width: <?php echo $Percent[3]; ?>%">
	    <span><?php echo $PercVal[3]; ?></span>
	  </div>
	  <div class="progress-bar progress-bar-success" style="width: <?php echo $Percent[4]; ?>%">
	    <span><?php echo $PercVal[4]; ?></span>
	  </div>
	</div>
</div>
<div class="col-lg-12">
    
</div>