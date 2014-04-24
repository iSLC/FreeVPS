<div class="col-lg-12">
	<div class="list-group">
<?php
	foreach ($User['postlist'] as $Num => $Post) {
		echo '<a href="'.$Post['post_link'].'" class="list-group-item">';
		echo '<h4 class="list-group-item-heading"><span class="text-info" style="white-space: nowrap">'.date('m-d-Y h:ia', $Post['time_stamp']).'</span> '.$Post['post_title'].'</h4>';
		echo '<p class="list-group-item-text">'.$Post['post_preview'];
		echo '</p>';
		echo '</a>';
		echo '<ol class="breadcrumb">';
		echo '<li><a href="'.$Post['forum_link'].'">Forum</a></li>';
		echo '<li><a href="'.$Post['thread_link'].'">Thread</a></li>';
		echo '<li class="active">Post</li>';
		echo '</ol>';
	}
?>
	</div>
</div>