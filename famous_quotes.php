<?php
/*
Plugin Name: Famous Random Quotes
Plugin URI: http://
Description: Display random quotes and words everywhere on your blog. Easy to custom and manage. Ajax enabled.
Author: Reinhold
Author URI:http://
Version: 1.0.0
License: IC
*/

global $wpdb, $wp_version;

//few definitions
if ( ! defined( 'WP_CONTENT_URL' ) ) {	
	if ( ! defined( 'WP_SITEURL' ) ) define( 'WP_SITEURL', get_option("siteurl") );
	define( 'WP_CONTENT_URL', WP_SITEURL . '/wp-content' );
}
if ( ! defined( 'WP_SITEURL' ) ) define( 'WP_SITEURL', get_option("siteurl") );
if ( ! defined( 'WP_CONTENT_DIR' ) ) define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
if ( ! defined( 'WP_PLUGIN_URL' ) ) define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
if ( ! defined( 'WP_PLUGIN_DIR' ) ) define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );

define("WP_FAMOUS_QUOTES_TABLE", $wpdb->prefix . "famous_quotes");
if ( basename(dirname(__FILE__)) == 'plugins' )
	define("FAMOUS_DIR",'');
else define("FAMOUS_DIR" , basename(dirname(__FILE__)) . '/');
define("WP_FAMOUS_QUOTES_PATH", WP_PLUGIN_URL . "/" . FAMOUS_DIR);

//get ready for local

// fix REQUEST_URI for ISS
if ( !isset($_SERVER['REQUEST_URI']) || ($_SERVER['REQUEST_URI']=='') ) {

	$_SERVER['REQUEST_URI'] = substr($_SERVER['PHP_SELF'],1);

	if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] != '') {
		$_SERVER['REQUEST_URI'] .= '?'.$_SERVER['QUERY_STRING'];
	}
}

//add ajax script
function famous_quotes_add_js() {
	
	$quotesoptions = get_option('famous_quotes_options');
	if ($quotesoptions['famous_ajax'] !='Y') {
		wp_enqueue_script('famous_ajax.js', WP_FAMOUS_QUOTES_PATH.'inc/famous_ajax.js', array('jquery'));
	}
}

//add header
function famous_quotes_header(){
	
	//header for the manage page
	if(strpos($_SERVER['REQUEST_URI'],'famous_manage')) {
	
		?><script  type='text/javascript'><!-- 
		function switchpage(select) {var index;for(index=0; index<select.options.length; index++) {if(select.options[index].selected){if(select.options[index].value!="")window.location.href=select.options[index].value;break;}}} 

		jQuery(document).ready(function($) {
			$("#famousmanage thead tr th:first input:checkbox").click(function() {
				var checkedStatus = this.checked;
				$("#famousmanage tbody tr td:first-child input:checkbox").each(function() {
					this.checked = checkedStatus;
				});
			});
	
		});
		
		
		function disable_enable(){
			
		}
		// Multiple onload function created by: Simon Willison
		// http://simonwillison.net/2004/May/26/addLoadEvent/
		function addLoadEvent(func) {
		  var oldonload = window.onload;
		  if (typeof window.onload != 'function') {
			window.onload = func;
		  } else {
			window.onload = function() {
			  if (oldonload) {
				oldonload();
			  }
			  func();
			}
		  }
		}
		addLoadEvent(function() {
		});

        --></script><?php	
	}

	
}

//upon activation
function quotes_activation() {

	global $wpdb,$current_user;
	
	//set the messages
	$famousmessage = "";
	$newmessage = str_replace("%1","http://www.italyisfalling.com/famous-random-quotes/#changelog",__('<p>You installed a new version of <strong>Famous Random Quotes</strong>. All changes are addressed in the <a href="%1">changelog</a>, but you should know that: </p>','famous-quotes'));
	
	//in case we have to point to other pages in the messages
	$widgetpage = get_option('siteurl')."/wp-admin/widgets.php";
	$management = get_option('siteurl')."/wp-admin/admin.php?page=famous_manage";
	$options =  get_option('siteurl')."/wp-admin/admin.php?page=famous_quotes_options";
	$new = get_option('siteurl')."/wp-admin/admin.php?page=famous_new";

	//check if table exists and alter it if necessary	
	$famoustableExists = false;
	$famoustables = $wpdb->get_results("SHOW TABLES");
	$wp_quotes = $wpdb->prefix . "quotes";
	foreach ( $famoustables as $famoustable ){	
		foreach ( $famoustable as $value ){
			
			//takes care of the old wp_quotes table (probably useless)
			if ( $value == $wpdb->prefix . "quotes" ){
					
				$famoustableExists = true;	
				//if table exists it must be old -- must update and rename.
				$wpdb->query('RENAME TABLE ' . $wp_quotes . ' TO ' . WP_FAMOUS_QUOTES_TABLE);
				
				//message
				$search = array("%s1", "%s2");
				$replace = array($wp_quotes, WP_FAMOUS_QUOTES_TABLE);
				if (!$famousmessage) $famousmessage = $newmessage;
				$famousmessage .= str_replace($search,$replace,__('<li>I changed the old table "%s1" into a new one called "%s2" but don\'t worry, all your quotes are still there.</li>','famous-quotes')); 
				
				break;
			}
			
			//takes care of the new table
			if ( $value == WP_FAMOUS_QUOTES_TABLE ){			
						
				$famoustableExists=true;			
				break;	
			}		
		}
	}
	
	//table does not exist, create one
	if ( !$famoustableExists ) {
		
		$wpdb->query("
		CREATE TABLE IF NOT EXISTS `". WP_FAMOUS_QUOTES_TABLE . "` (
		`quoteID` INT NOT NULL AUTO_INCREMENT ,
		`quote` TEXT NOT NULL ,
		`author` varchar( 255 ) NOT NULL ,
		PRIMARY KEY ( `quoteID` ) )
		");
		
		//insert sample quote
		$wpdb->query("INSERT INTO " . WP_FAMOUS_QUOTES_TABLE . " (
		`quote`, `author`) values ('Always tell the truth. Then you don\'t have to remember anything.', 'Mark Twain') ");
		
		//message
		$famousmessage = str_replace("%s1", WP_FAMOUS_QUOTES_TABLE,__('<p>Hey. This seems to be your first time with this plugin. I\'ve just created the database table "%s1" to store your quotes, and added one to start you off.</p>','famous-quotes'));
	}
	
	$quotesoptions = get_option('famous_quotes_options');
		
	//convert old options into (and insert the) new array options	
	if (false === $quotesoptions || !is_array($quotesoptions) || $quotesoptions=='' ) {
		
		$quotesoptions = array();
		
		//conversion of old pre-1.7 options AND/OR creation of new options
		$var = 'famous_quotes_before_all';
		$temp = get_option($var);
		if (false === $temp) $quotesoptions[$var] =  '';
		else $quotesoptions[$var] = $temp;
		delete_option($var);
		unset($var);unset($temp);		
		$var = 'famous_quotes_before_quote';
		$temp = get_option($var);
		if (false === $temp) $quotesoptions[$var] =  '&#8220;';
		else $quotesoptions[$var] = $temp;
		delete_option($var);		
		unset($var);unset($temp);
		$var = 'famous_quotes_after_quote';
		$temp = get_option($var);
		if (false === $temp) $quotesoptions[$var] =  '&#8221;';
		else $quotesoptions[$var] = $temp;
		delete_option($var);		
		unset($var);unset($temp);
		$var = 'famous_quotes_before_author';
		$temp = get_option($var);
		if (false === $temp) $quotesoptions[$var] =  '<br/>by&nbsp;';
		else $quotesoptions[$var] = $temp;
		delete_option($var);		
		unset($var);unset($temp);
		$var = 'famous_quotes_after_author';
		$temp = get_option($var);
		if (false === $temp) $quotesoptions[$var] =  '';
		else $quotesoptions[$var] = $temp;
		delete_option($var);		
		unset($var);unset($temp);
		$var = 'famous_quotes_before_source';
		$temp = get_option($var);
		if (false === $temp) $quotesoptions[$var] =  '<em>&nbsp;';
		else $quotesoptions[$var] = $temp;
		delete_option($var);		
		unset($var);unset($temp);
		$var = 'famous_quotes_after_source';
		$temp = get_option($var);
		if (false === $temp) $quotesoptions[$var] =  '</em>';
		else $quotesoptions[$var] = $temp;
		delete_option($var);		
		unset($var);unset($temp);
		$var = 'famous_quotes_after_all';
		$temp = get_option($var);
		if (false === $temp) $quotesoptions[$var] =  '';
		else $quotesoptions[$var] = $temp;
		delete_option($var);		
		unset($var);unset($temp);
		$var = 'famous_quotes_put_quotes_first';
		$temp = get_option($var);
		if (false === $temp) $quotesoptions[$var] =  'Y';
		else $quotesoptions[$var] = $temp;
		delete_option($var);		
		unset($var);unset($temp);
		$var = 'famous_quotes_default_visible';
		$temp = get_option($var);
		if (false === $temp) $quotesoptions[$var] =  'Y';
		else $quotesoptions[$var] = $temp;
		delete_option($var);
		unset($var);unset($temp);
		
		//only remove
		$var = 'famous_quotes_widget_title';
		$temp = get_option($var);
		if ($temp)delete_option($var);		
		unset($var);unset($temp);
		$var = 'famous_quotes_regular_title';
		$temp = get_option($var);
		if ($temp)delete_option($var);		
		unset($var);unset($temp);
		
		//special trasformation for how link options work now
		$var = 'famous_quotes_use_google_links';
		$temp = get_option($var);
		$varb = 'famous_quotes_wiki_lan';		
		$tempb = get_option($varb);
		if ($temp == 'Y') {
			$quotesoptions['famous_quotes_linkto'] = '<a href="http://www.google.com/search?q=&quot;%AUTHOR%&quot;">';
			$quotesoptions['famous_quotes_sourcelinkto'] = '<a href="http://www.google.com/search?q=&quot;%SOURCE%&quot;">';
			$quotesoptions['famous_quotes_sourcespaces'] = ' ';	
			$quotesoptions['famous_quotes_authorspaces'] = ' ';		
		} 
		
		else if ($temp == 'W') {
			$quotesoptions['famous_quotes_linkto'] = '<a href="http://'.$tempb.'.wikipedia.org/wiki/%AUTHOR%';
			$quotesoptions['famous_quotes_linkto'] = '<a href="http://'.$tempb.'.wikipedia.org/wiki/%SOURCE%';
			$quotesoptions['famous_quotes_sourcespaces'] = '_';	
			$quotesoptions['famous_quotes_authorspaces'] = '_';		
		}
		
		else {
			$quotesoptions['famous_quotes_linkto'] =  '';
			$quotesoptions['famous_quotes_sourcelinkto'] =  '';
			$quotesoptions['famous_quotes_sourcespaces'] = '-';	
			$quotesoptions['famous_quotes_authorspaces'] = '-';		
		}
		delete_option($var);
		delete_option($varb);
		
		//more new entries
		$quotesoptions['famous_if_no_author'] =  __('<br/>source:&nbsp;','famous-quotes');	
		$quotesoptions['famous_quotes_uninstall'] = '';
		$quotesoptions['famous_clear_form'] =  'Y';	
		$quotesoptions['famous_quotes_order'] = 'quoteID';
		$quotesoptions['famous_quotes_rows'] = 10; 
		$quotesoptions['famous_quotes_categories'] = 'all';
		$quotesoptions['famous_quotes_sort'] = 'DESC';
		$quotesoptions['famous_default_category'] =  'default';	
		$quotesoptions['famous_before_loader'] = '<p align="left">';
		$quotesoptions['famous_loader'] = '';
		$quotesoptions['famous_after_loader'] = '</p>';
		$quotesoptions['famous_ajax'] =  '';
		$quotesoptions['comment_scode'] =  '';
		$quotesoptions['title_scode'] =  '';
		$quotesoptions['excerpt_scode'] =  '';
		$quotesoptions['widget_scode'] =  '';
		$quotesoptions['categories_scode'] =  '';
		$quotesoptions['tags_scode'] =  '';
		$quotesoptions['bloginfo_scode'] =  '';		
		$quotesoptions['bookmarlet_source'] =  '';
		$quotesoptions['bookmarklet_cat'] =  '';
		$quotesoptions['famous_loading'] =  __('loading...','famous-quotes');
		$quotesoptions['famous_multiuser'] = false;
				
		//the message
		delete_option('famous_quotes_first_time');		
		
	}
		
	settype($quotesoptions['famous_quotes_version'], "integer");
	
	// <= 1.9.5
	if( $quotesoptions['famous_quotes_version'] <= 195 ){
		
		//message
		if (!$famousmessage)$famousmessage = $newmessage;
		$famousmessage .= __('<li> for compatibility reasons, Famous Random Quotes shortcodes have changed their names. Please take note: <code>random-quote</code> is now <code>famous-random</code>, <code>all-quotes</code> is now <code>famous-all</code> and <code>quote</code> is now <code>famous-id</code>. Please update them wherever they have been used on your blog. Thanks.</li>','famous-quotes');
		
	}

	//!!  CHANGE THIS WITH EVERY NEW VERSION !!
	$quotesoptions['famous_quotes_version'] = 199;
	
	//reset the removal option for everyone
	$quotesoptions['famous_quotes_uninstall'] = "";
	
	//insert the feedback message
	$quotesoptions['famous_quotes_first_time'] = $famousmessage;

	//and finally we actually put the option thing in the database
	update_option('famous_quotes_options', $quotesoptions);		
	
}

//upon deactivation
function quotes_deactivation() {

	global $wpdb;

	$quotesoptions = get_option('famous_quotes_options');
	$sql = "DROP TABLE IF EXISTS ".WP_FAMOUS_QUOTES_TABLE;

	//delete the options
	if($quotesoptions['famous_quotes_uninstall'] == 'options') {
		delete_option('famous_quotes_options');
		delete_option('widget_famous_quotes');
	}
	else if ($quotesoptions['famous_quotes_uninstall'] == 'table')$wpdb->query($sql);
	else if ($quotesoptions['famous_quotes_uninstall'] == 'both'){
		 delete_option('famous_quotes_options');
		 delete_option('widget_famous_quotes');
		$wpdb->query($sql);
	}

}
	
//for compatibility
if ($wp_version <= 2.3 ) add_filter('the_content', 'wp_quotes_page', 10);

//includes
include('inc/famous_functions.php');
include('inc/famous_overview.php');
include('inc/famous_manage.php');
include('inc/famous_new.php');
include('inc/famous_widgets.php');

//build submenu entries
function famous_quotes_add_pages() {
	
	$quotesoptions = get_option('famous_quotes_options');
	if($quotesoptions['famous_multiuser'] == true) $famouscan = 'edit_posts';
	else $famouscan = 'manage_options';

	add_menu_page('Famous Random Quotes', __('Quotes','famous-quotes'), $famouscan, __FILE__, 'famous_intro', WP_FAMOUS_QUOTES_PATH.'img/lightbulb.png');
	add_submenu_page(__FILE__, __('Overview for the Quotes','famous-quotes'), __('Overview','famous-quotes'), $famouscan, __FILE__, 'famous_intro');
	add_submenu_page(__FILE__, __('Manage Quotes','famous-quotes'), __('Manage','famous-quotes'), $famouscan, 'famous_manage', 'famous_manage');
	add_submenu_page(__FILE__, __('Add New Quote','famous-quotes'), __('Add New','famous-quotes'), $famouscan, 'famous_new', 'famous_new');
	
}

//excuse me, I'm hooking wordpress
add_action('admin_menu', 'famous_quotes_add_pages');
add_action('wp_print_scripts', 'famous_quotes_add_js');
add_action('admin_head', 'famous_quotes_header');

if (function_exists(add_shortcode)) {
	
	add_shortcode('famous-id', 'famous_id_shortcode');		
	add_shortcode('famous-random', 'famous_random_shortcode');			
	add_shortcode('famous-all', 'famous_all_shortcode');
}

register_activation_hook(__FILE__, 'quotes_activation');
register_deactivation_hook(__FILE__, 'quotes_deactivation');

$quotesoptions = get_option('famous_quotes_options');
if ($quotesoptions['comment_scode'] == 'Y') add_filter('comment_text', 'do_shortcode');
if ($quotesoptions['title_scode'] == 'Y') add_filter('the_title', 'do_shortcode');
if ($quotesoptions['excerpt_scode'] == 'Y') add_filter('the_excerpt', 'do_shortcode');
if ($quotesoptions['widget_scode'] == 'Y') add_filter('widget_text', 'do_shortcode');
if ($quotesoptions['categories_scode'] == 'Y') add_filter('the_category', 'do_shortcode');
if ($quotesoptions['tags_scode'] == 'Y') add_filter('the_tags', 'do_shortcode');
if ($quotesoptions['bloginfo_scode'] == 'Y') {
	add_filter('bloginfo', 'do_shortcode');
	add_filter('bloginfo_rss', 'do_shortcode');	
}

?>