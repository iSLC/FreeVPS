<div class="col-lg-12">
<?php
	// Get the minimum required posts to enter the giveaway.
	$Required = _Cfg('required_vps_posts', 30);
	// Create a temporary variable to hold a rounded version of the current posts number.
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
	$PostPercFill = array(FALSE, FALSE, FALSE, FALSE);
	var_dump($User);
	// How much posts does the user needs in order to fill the minimum requirement.
	$Needs = ($PostCount < $Required) ? ($Required - $PostCount) : 0;
	// How many extra posts did the user made over the minimum requirement.
	$Extra = ($PostCount > $Required) ? ($PostCount - $Required) : 0;

	// Display an information message according to the posts number.
	if ($PostCount <= $Msg[0]) {
		echo '<div class="alert alert-danger"><strong>Oh no!</strong> Your current posts number is way too low. You need '.$Needs.' more in order to enter the Giveaway and get a VPS.</div>';
		$PostPercFill = array(FALSE, FALSE, FALSE, FALSE);
	} elseif ($PostCount <= $Msg[1]) {
		echo '<div class="alert alert-warning"><strong>Oh snap!</strong> You don\'t have enough posts. You need at least '.$Needs.' more to be able to enter the Giveaway and get a VPS.</div>';
		$PostPercFill = array(TRUE, FALSE, FALSE, FALSE);
	} elseif ($PostCount <= $Msg[2]) {
		echo '<div class="alert alert-info" style="border-color: #5FB5E3;background-image: linear-gradient(to bottom,#99D6F6 0,#64BEE8 100%)"><strong>Heads up!</strong> You still don\'t have enough posts to fill the minimum requirement. You still need at least '.$Needs.' more to enter the Giveaway and get a VPS.</div>';
		$PostPercFill = array(TRUE, TRUE, FALSE, FALSE);
	} elseif ($PostCount < $Msg[3]) {
		echo '<div class="alert alert-info"><strong>Almost there!</strong> You\'re almost close to completing your minimum required posts. You just need '.$Needs.' more in order to enter the Giveaway and get a VPS.</div>';
		$PostPercFill = array(TRUE, TRUE, TRUE, FALSE);
	} elseif ($PostCount >= $Msg[3] && $PostCount <= $Msg[4]) {
		echo '<div class="alert alert-success"><strong>Well done!</strong> You have completed your minimum posts requirement. You are now eligible to enter the Giveaway and get a VPS.</div>';
		$PostPercFill = array(TRUE, TRUE, TRUE, TRUE);
	} elseif ($PostCount <= $Msg[5]) {
		echo '<div class="alert alert-success"><strong>Congratulations!</strong> You have completed your minimum posts requirement and more. You are now eligible to enter the Giveaway and get a VPS.</div>';
		$PostPercFill = array(TRUE, TRUE, TRUE, TRUE);
	} elseif ($PostCount <= $Msg[6]) {
		echo '<div class="alert alert-success"><strong>Good work!</strong> You have completed your minimum posts requirement and way more. You are now eligible to enter the Giveaway and get a VPS without worries.</div>';
		$PostPercFill = array(TRUE, TRUE, TRUE, TRUE);
	} elseif ($PostCount <= $Msg[7]) {
		echo '<div class="alert alert-success"><strong>Great job!</strong> You have completed your minimum posts requirement and a much more. You are now eligible to enter the Giveaway and get a VPS without any worries.</div>';
		$PostPercFill = array(TRUE, TRUE, TRUE, TRUE);
	} elseif ($PostCount <= $Msg[8]) {
		echo '<div class="alert alert-success"><strong>Magnificent!</strong> You have way more than the minimum posts requirement. You can safely enter the Giveaway and get a VPS without any worries.</div>';
		$PostPercFill = array(TRUE, TRUE, TRUE, TRUE);
	} elseif ($PostCount <= $Msg[9]) {
		echo '<div class="alert alert-success"><strong>Breathtaking!</strong> You just massacred the minimum posts requirement. You can safely enter the Giveaway and get a VPS without any worries.</div>';
		$PostPercFill = array(TRUE, TRUE, TRUE, TRUE);
	} elseif ($PostCount <= $Msg[10]) {
		echo '<div class="alert alert-success"><strong>Hold on!</strong> Are you trying to win a contest of something? You have enough posts to enter the Giveaway and get a VPS without any worries.</div>';
		$PostPercFill = array(TRUE, TRUE, TRUE, TRUE);
	} elseif ($PostCount <= $Msg[11]) {
		echo '<div class="alert alert-success"><strong>Stop, Just Stop!</strong> Are you trying to get confused for a spam bot something? You have more than enough posts to enter the Giveaway and get a VPS.</div>';
		$PostPercFill = array(TRUE, TRUE, TRUE, TRUE);
	} elseif ($PostCount <= $Msg[12]) {
		echo '<div class="alert alert-success"><strong>Unstoppable!</strong> You definitely need some medical attention or something. You have way more than enough posts to enter the Giveaway and get a VPS.</div>';
		$PostPercFill = array(TRUE, TRUE, TRUE, TRUE);
	} else {
		echo '<div class="alert alert-success"><strong>Unbelievable!</strong> There\'s just no way to describe the mental sickness inside you. You have way more than enough posts to continue using the VPS for the next month.</div>';
		$PostPercFill = array(TRUE, TRUE, TRUE, TRUE);
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
<?php
	// Get the minimum required score to enter the giveaway.
	$Required = _Cfg('required_vps_Score', 55);
	// Create a temporary variable to hold a rounded version of the current score.
	$PostCount = round($User['score'], 2);
	// Generate a table of score numbers to check for when displaying any messages.
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
	$ScorePercFill = array(FALSE, FALSE, FALSE, FALSE);
	
	// How much score does the user needs in order to fill the minimum requirement.
	$Needs = ($PostCount < $Required) ? ($Required - $PostCount) : 0;
	// How much of extra score did the user made over the minimum requirement.
	$Extra = ($PostCount > $Required) ? ($PostCount - $Required) : 0;

	// Display an information message according to the current score.
	if ($PostCount <= $Msg[0]) {
		echo '<div class="alert alert-danger"><strong>Oh no!</strong> Your current score is way too low. You need '.$Needs.' more in order to enter the Giveaway and get a VPS.</div>';
		$ScorePercFill = array(FALSE, FALSE, FALSE, FALSE);
	} elseif ($PostCount <= $Msg[1]) {
		echo '<div class="alert alert-warning"><strong>Oh snap!</strong> You don\'t have enough score. You need at least '.$Needs.' more to be able to enter the Giveaway and get a VPS.</div>';
		$ScorePercFill = array(TRUE, FALSE, FALSE, FALSE);
	} elseif ($PostCount <= $Msg[2]) {
		echo '<div class="alert alert-info" style="border-color: #5FB5E3;background-image: linear-gradient(to bottom,#99D6F6 0,#64BEE8 100%)"><strong>Heads up!</strong> You still don\'t have enough score to fill the minimum requirement. You still need at least '.$Needs.' more to enter the Giveaway and get a VPS.</div>';
		$ScorePercFill = array(TRUE, TRUE, FALSE, FALSE);
	} elseif ($PostCount < $Msg[3]) {
		echo '<div class="alert alert-info"><strong>Almost there!</strong> You\'re almost close to completing your minimum required score. You just need '.$Needs.' more in order to enter the Giveaway and get a VPS.</div>';
		$ScorePercFill = array(TRUE, TRUE, TRUE, FALSE);
	} elseif ($PostCount >= $Msg[3] && $PostCount <= $Msg[4]) {
		echo '<div class="alert alert-success"><strong>Well done!</strong> You have completed your minimum score requirement. You are now eligible to enter the Giveaway and get a VPS.</div>';
		$ScorePercFill = array(TRUE, TRUE, TRUE, TRUE);
	} elseif ($PostCount <= $Msg[5]) {
		echo '<div class="alert alert-success"><strong>Congratulations!</strong> You have completed your minimum score requirement and more. You are now eligible to enter the Giveaway and get a VPS.</div>';
		$ScorePercFill = array(TRUE, TRUE, TRUE, TRUE);
	} elseif ($PostCount <= $Msg[6]) {
		echo '<div class="alert alert-success"><strong>Good work!</strong> You have completed your minimum score requirement and way more. You are now eligible to enter the Giveaway and get a VPS without worries.</div>';
		$ScorePercFill = array(TRUE, TRUE, TRUE, TRUE);
	} elseif ($PostCount <= $Msg[7]) {
		echo '<div class="alert alert-success"><strong>Great job!</strong> You have completed your minimum score requirement and a much more. You are now eligible to enter the Giveaway and get a VPS without any worries.</div>';
		$ScorePercFill = array(TRUE, TRUE, TRUE, TRUE);
	} elseif ($PostCount <= $Msg[8]) {
		echo '<div class="alert alert-success"><strong>Magnificent!</strong> You have way more than the minimum score requirement. You can safely enter the Giveaway and get a VPS without any worries.</div>';
		$ScorePercFill = array(TRUE, TRUE, TRUE, TRUE);
	} elseif ($PostCount <= $Msg[9]) {
		echo '<div class="alert alert-success"><strong>Breathtaking!</strong> You just massacred the minimum score requirement. You can safely enter the Giveaway and get a VPS without any worries.</div>';
		$ScorePercFill = array(TRUE, TRUE, TRUE, TRUE);
	} elseif ($PostCount <= $Msg[10]) {
		echo '<div class="alert alert-success"><strong>Hold on!</strong> Are you trying to win a contest of something? You have enough score to enter the Giveaway and get a VPS without any worries.</div>';
		$ScorePercFill = array(TRUE, TRUE, TRUE, TRUE);
	} elseif ($PostCount <= $Msg[11]) {
		echo '<div class="alert alert-success"><strong>Stop, Just Stop!</strong> Are you trying to get confused for a spam bot something? You have more than enough score to enter the Giveaway and get a VPS.</div>';
		$ScorePercFill = array(TRUE, TRUE, TRUE, TRUE);
	} elseif ($PostCount <= $Msg[12]) {
		echo '<div class="alert alert-success"><strong>Unstoppable!</strong> You definitely need some medical attention or something. You have way more than enough score to enter the Giveaway and get a VPS.</div>';
		$ScorePercFill = array(TRUE, TRUE, TRUE, TRUE);
	} else {
		echo '<div class="alert alert-success"><strong>Unbelievable!</strong> There\'s just no way to describe the mental sickness inside you. You have way more than enough score to continue using the VPS for the next month.</div>';
		$ScorePercFill = array(TRUE, TRUE, TRUE, TRUE);
	}
?>
</div>

<div class="col-lg-12">
<?php
	$Percent = array(0.0, 0.0, 0.0, 0.0, 0.0);
	$PercVal = array('', '', '', '', '');
	// Try to find the percentage of the score for the progress bar.
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
<?php
	// Try to get the Giveaway start and end dates.
	$GiveawayStart = mktime(23, 59, 59, date("n"), 12);
	$GiveawayEnd = mktime(23, 59, 59, date("n"), date("t"));
	$CurrenDate = time();

	// Determine wheather the Giveaway allready happened or not.
	if ($CurrenDate >= $GiveawayEnd && $CurrenDate <= $GiveawayStart) {
		echo '<div class="alert alert-warning"><strong>Note!</strong> The Giveaway allready ended on '.date('m-d-Y', $GiveawayEnd).'. You must wait for the Giveaway to start somewhere between '.date('m-d-Y', $GiveawayStart).' and '.date('m-d-Y', mktime(23, 59, 59, date("n"), 15)).'.</div>';
	} elseif ($CurrenDate >= $GiveawayStart && $CurrenDate <= mktime(23, 59, 59, date("n"), 16)) {
		echo '<div class="alert alert-warning"><strong>Note!</strong> The Giveaway recently started on '.date('m-d-Y', $GiveawayStart).'. Look into the <a href="'.SITE_ROOT_URL.'/forum-61.html">VPS Giveaways</a> forum for a thread with the available VPS\'s.</div>';
	} elseif ($CurrenDate >= $GiveawayStart && $CurrenDate <= mktime(23, 59, 59, date("n"), 18)) {
		echo '<div class="alert alert-warning"><strong>Note!</strong> The Giveaway allready started on '.date('m-d-Y', $GiveawayStart).'. Look into the <a href="'.SITE_ROOT_URL.'/forum-61.html">VPS Giveaways</a> forum for a thread with the available VPS\'s.</div>';
		echo '<div class="alert alert-warning"><strong>Note!</strong> The Giveaway happens very fast and there\'s a chance that you won\'t find any VPS\'s left even a few days after the Giveaway started.</div>';
 	} elseif ($CurrenDate >= $GiveawayStart && $CurrenDate <= mktime(23, 59, 59, date("n"), 24)) {
		echo '<div class="alert alert-warning"><strong>Note!</strong> The Giveaway started while ago on '.date('m-d-Y', $GiveawayStart).'. Look into the <a href="'.SITE_ROOT_URL.'/forum-61.html">VPS Giveaways</a> forum for a thread with the available VPS\'s.</div>';
		echo '<div class="alert alert-warning"><strong>Note!</strong> The Giveaway happens very fast and there\'s a chance that you won\'t find any VPS\'s left even a few days after the Giveaway started.</div>';
	} else {
		echo '<div class="alert alert-warning"><strong>Note!</strong> Look into the <a href="'.SITE_ROOT_URL.'/forum-61.html">VPS Giveaways</a> forum for a thread with the available VPS\'s.</div>';
		echo '<div class="alert alert-warning"><strong>Note!</strong> The Giveaway happens very fast and there\'s a chance that you won\'t find any VPS\'s left even a few days after the Giveaway started.</div>';
	}
?>
</div>