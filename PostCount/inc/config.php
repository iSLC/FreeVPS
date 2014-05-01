<?php if(!defined('POST_COUNT')) exit('Direct access is denied!'); ?>
<?php
	$_Configs['website_root_url'] = 'http://freevps.us';
	$_Configs['required_monthly_posts'] = 20;

	$_Configs['user_agent_url'] = 'http://my-address/my-page.html';
	$_Configs['user_agent_name'] = 'PostCountBot';
	$_Configs['user_agent_version'] = '1.0';

	$_Configs['required_vps_posts'] = 30;
	$_Configs['required_vps_Score'] = 55;

	$_Configs['days_to_auth'] = 30;

	$_Configs['total_views_file'] = '.views';
	$_Configs['last_auth_file'] = '.auth';
	$_Configs['cookie_jar_file'] = '.cookie';

	$_Configs['auth_username'] = '';
	$_Configs['auth_pasword'] = '';
	
	$_Configs['auth_enabled'] = TRUE;

	$_Configs['views_enabled'] = TRUE;
?>