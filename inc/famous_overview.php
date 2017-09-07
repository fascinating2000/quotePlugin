<?php

//intro page
function famous_intro() {

	global $wpdb,$current_user;
	
	//load options
	$quotesoptions = array();
	$quotesoptions = get_option('famous_quotes_options');
	
	//security check
	if( $quotesoptions['famous_multiuser'] == false && !current_user_can('manage_options') )
		die('Access Denied');
	
	$widgetpage = get_option('siteurl')."/wp-admin/widgets.php";
	$management = get_option('siteurl')."/wp-admin/admin.php?page=famous_manage";
	$options =  get_option('siteurl')."/wp-admin/admin.php?page=famous_quotes_options";
	$new = get_option('siteurl')."/wp-admin/admin.php?page=famous_new";
	$help =  get_option('siteurl')."/wp-admin/admin.php?page=famous_help";
	$toolspage = get_option('siteurl')."/wp-admin/admin.php?page=famous_tools";
	$famousmessage = $quotesoptions['famous_quotes_first_time'];
	
	//get total quotes
	$totalsql = "SELECT COUNT(`quoteID`) AS `Rows` FROM `" . WP_FAMOUS_QUOTES_TABLE . "` WHERE `user`='".$current_user->user_nicename."'";
	$totalquotes = $wpdb->get_var($totalsql);

	//feedback following activation (see main file)
	if ($famousmessage !="") {
		
		?><div id="message" class="updated fade"><ul><?php echo $famousmessage; ?></ul></div><?php
		
		//empty message after feedback
		$quotesoptions['famous_quotes_first_time'] = "";
		update_option('famous_quotes_options', $quotesoptions);
	}	
	
	?><div class="wrap"><h2>Famous Random Quotes: <?php _e('Overview','famous-quotes'); ?></h2><p>Generate Random Famous Quote.</br> And you can put it anywhere you want. </br>Create, Edit, Delete functions.</p>
	<?php
}
?>