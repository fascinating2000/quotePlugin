<?php

function famous_new() {	

	global $wpdb,$current_user;
	
	//load options
	$quotesoptions = array();
	$quotesoptions = get_option('famous_quotes_options');
	
	//security check
	if( $quotesoptions['famous_multiuser'] == false && !current_user_can('manage_options') )
		die('Access Denied');

	//decode and intercept
	foreach($_POST as $key => $val) {
		$_POST[$key] = stripslashes($val);
	}	

	// control the requests
	$action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : '';
	$quoteID = !empty($_REQUEST['quoteID']) ? $_REQUEST['quoteID'] : '';
	//this is for the bookmarklet
	if ( $action == 'bookmarklet' ) {
		
		$quotesoptions = array();
		$quotesoptions = get_option('famous_quotes_options');
		$quote = !empty($_REQUEST['quote_quote']) ? stripslashes(trim($_REQUEST['quote_quote'])) : '';
		if ($quotesoptions['bookmarlet_source'] == 'Y' )$source = !empty($_REQUEST['quote_source']) ? stripslashes(trim($_REQUEST['quote_source'])) : '';
		if ($quotesoptions['bookmarklet_cat']) $category = $quotesoptions['bookmarklet_cat'];
	}
	
	//after adding a new quote
	if ( $action == 'add' ) {
	
		//assign variables and trim them
		$quote = !empty($_REQUEST['quote_quote']) ? trim($_REQUEST['quote_quote']) : '';
		$author = !empty($_REQUEST['quote_author']) ? trim($_REQUEST['quote_author']) : '';

		//take care of stupid magic quotes
		if ( ini_get('magic_quotes_gpc') )	{
		
			$quote = stripslashes($quote);
			$author = stripslashes($author);
		}	
		
		//insert quote and author to back-end Symfony
		$curl = curl_init();
		//Set some options
		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => '127.0.0.1:8000/quote',
			CURLOPT_POST => 1,
			CURLOPT_POSTFIELDS => array(
				authorName => $author,
				quoteContent => $quote
			)
		));

		$resp = curl_exec($curl);

		curl_close($curl);
		$resp = json_decode($resp);

		//failure message
		if ( !empty($resp->status) && $resp->status != 'success' )	{
			?><div class="error fade"><p><?php _e('<strong>Failure:</strong> Something went wrong when trying to insert the quote. Try again?',
			'famous-quotes'); ?></p></div><?php				
		}
			
		//success message
		else {
			?><div class="updated fade"><p>New Quote Added.</p></div><?php			
		}
	
	}
	
	//making the "add new quote" page
	?><div class="wrap"><h2><?php _e('Add new quote','famous-quotes') ?></h2><?php
	
		//housecleaning 
		$quoteID=false;
		$data = false;
		
		//get the last inserted quote 
		if ( $quoteID !== false ) {
	
			if ( intval($quoteID) != $quoteID ) {		
				?><div class="error fade"><p><?php _e('The Quote ID seems to be invalid.','famous-quotes') ?></p></div><?php
				return;
			}
			else {
				$data = $wpdb->get_results("select * from " . WP_FAMOUS_QUOTES_TABLE . " where quoteID='" . mysql_real_escape_string($quoteID) . "' limit 1");
				if ( empty($data) ) {
					?><div class="error fade"><p><?php _e('Something is wrong. Sorry.','famous-quotes') ?></p></div><?php
					return;
				}
				$data = $data[0];
			}	
		}

		//optionally assign the just inserted quote to vaiables
		if ($quotesoptions['famous_clear_form']!=='Y') {
			if ( !empty($data) ) { 
				$quote = $data->quote; 		
				$author = $data->author;
			}
		} else if($action != 'bookmarklet')$quote = $author = false;
		
		//make the "add new quote" form
		$styleborder = 'style="border:1px solid #ccc"';
		$styletextarea = 'style="border:1px solid #ccc; font-family: Times New Roman, Times, serif; font-size: 1.4em;"'; ?>
		
		<div style="width:42em">
		<script src="<?php echo WP_FAMOUS_QUOTES_PATH ?>inc/famous_quicktags.js" type="text/javascript"></script>
		<form name="quoteform" id="quoteform" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
			<input type="hidden" name="action" value="add">
			<input type="hidden" name="quoteID" value="<?php echo $quoteID; ?>">
		
			<p><!--<label><?php _e('Quote:','famous-quotes') ?></label>-->
			<script type="text/javascript">edToolbar();</script>
			<textarea id="qeditor" name="quote_quote" <?php echo $styletextarea ?> cols=68 rows=7><?php echo $quote; ?></textarea>
			<script type="text/javascript">var edCanvas = document.getElementById('qeditor');</script>
			<p class="setting-description"><small><?php _e('* Other than the few offered in the toolbar above, many HTML and non-HTML formatting elements can be used for the quote. Lines can be broken traditionally or using <code>&lt;br/&gt;</code>, etcetera.','famous-quotes'); ?></small></p></p>
			
			<p><label><?php _e('Author:','famous-quotes') ?></label>
			<input type="text" id="aeditor" name="quote_author" size=58 value="<?php echo htmlspecialchars($author); ?>" <?php echo $styleborder ?> />
			<script type="text/javascript">edToolbar1();</script>
			<script type="text/javascript">var edCanvas1 = document.getElementById('aeditor');</script><br />
		
			<p><input type="submit" name="save"  class="button-primary" value="<?php _e('Add quote','famous-quotes') ?> &raquo;" /></p>
		</form></div>
        
	</div><?php	
}
?>