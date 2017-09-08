<?php 

//manage page
function famous_manage() {
    	
	global $wpdb,$current_user;
	
	//load options
	$quotesoptions = get_option('famous_quotes_options');
	//security check
	if( $quotesoptions['famous_multiuser'] == false && !current_user_can('manage_options') )
		die('Access Denied');
	
	//decode and intercept
	foreach($_POST as $key => $val)$_POST[$key] = stripslashes($val);
	 		
	//defaults and gets
	$action = !empty($_REQUEST['qa']) ? $_REQUEST['qa'] : '';
	$quoteID = !empty($_REQUEST['qi']) ? $_REQUEST['qi'] : '';
	
	$orderby = $quotesoptions['famous_quotes_order'];
	$pages = 1;
	$rows = $quotesoptions['famous_quotes_rows']; 
	$categories = $quotesoptions['famous_quotes_categories']; 
	$sort = $quotesoptions['famous_quotes_sort']; 
	
	if(isset($_GET['qo'])){
		$orderby = $_GET['qo'];
		$quotesoptions['famous_quotes_order'] = $_GET['qo'];
	}
	if(isset($_GET['qp']))$pages = $_GET['qp'];	
	
	if(isset($_GET['qr'])){
		$rows = $_GET['qr'];
		$quotesoptions['famous_quotes_rows'] = $_GET['qr'];	
	}
	
	if(isset($_GET['qc'])){
		$categories = $_GET['qc'];
		$quotesoptions['famous_quotes_categories'] = $_GET['qc'];	
	}
	
	if(isset($_GET['qs'])){
		$sort = $_GET['qs'];
		$quotesoptions['famous_quotes_sort'] = $_GET['qs'];		
	}
	
	$offset = ($pages - 1) * $rows;
	
	//check if the category I want exists
	$ok = false;
	$categorylist = make_categories(); 
	foreach($categorylist as $category){ 
		if ($category == $categories) $ok = true;
	}		
	if ($ok == false) {
		$categories = 'all';
		$quotesoptions['famous_quotes_categories'] = 'all';
	}
	
	//update options now
	update_option('famous_quotes_options', $quotesoptions);
	
	//add variables to the url -- for different uses -- thanks to frettsy who suggested this use
	$baseurl = get_option("siteurl").'/wp-admin/admin.php?page=famous_manage';
	$baseurl = querystrings($baseurl, 'qo', $orderby);
	$baseurl = querystrings($baseurl, 'qp', $pages);
	$baseurl = querystrings($baseurl, 'qr', $rows);
	$baseurl = querystrings($baseurl, 'qc', $categories);
	$urlaction = querystrings($baseurl, 'qs', $sort);
	
	//action: edit the quote
	if ( $action == 'edit' ) {
		?><div class="wrap"><h2><?php _e('Edit quote '.$quoteID, 'famous-quotes') ?></h2><?php 
		//check if something went wrong with quote id
		if ( empty($quoteID) ) {
			?><div id="message" class="error"><p><?php _e('Something is wrong. No quote ID from the query string.','famous-quotes') ?></p></div><?php
		}
		
		else {			
			
			//query
			$curl = curl_init();
			curl_setopt_array($curl, array(
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_URL => '127.0.0.1:8000/quote/'.$quoteID
			));

			$resp = curl_exec($curl);
			curl_close($curl);

			$data = json_decode($resp);
			
			//bad feedback
			if ( empty($data) ) {
				?><div id="message" class="error"><p><?php _e('Something is wrong. I can\'t find a quote linked up with that ID.','famous-quotes') ?></p></div><?php
				return;
			}
			// $data = $data[0];
			
			//encode strings
			if ( !empty($data) ) $quote = htmlspecialchars($data->quoteContent); 
			if ( !empty($data) ) $author = htmlspecialchars($data->author->authorName);		
			
			//make the edit form
			$styleborder = 'style="border:1px solid #ccc"';
			$styletextarea = 'style="border:1px solid #ccc; font-family: Times New Roman, Times, serif; font-size: 1.4em;"'; ?>
            <div style="width:42em">
			<script src="<?php echo WP_FAMOUS_QUOTES_PATH ?>inc/famous_quicktags.js" type="text/javascript"></script>
            <form name="quoteform" id="quoteform" method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
				<input type="hidden" name="qa" value="edit_save">
				<input type="hidden" name="qi" value="<?php echo $quoteID; ?>">
			
				<p><!--<label><?php _e('Quote:','famous-quotes') ?></label><br />-->
                <div style="float:left"><script type="text/javascript">edToolbar();</script></div>
                
                <textarea id="qeditor" name="quote_quote" <?php echo $styletextarea ?> cols=68 rows=7><?php echo $quote; ?></textarea></p>
				<script type="text/javascript">var edCanvas = document.getElementById('qeditor');</script>
                <p class="setting-description"><small><?php _e('* Other than the few offered in the toolbar above, many HTML and non-HTML formatting elements can be used for the quote. Lines can be broken traditionally or using <code>&lt;br/&gt;</code>, etcetera.','famous-quotes'); ?></small></p></p>
                
				<p><label><?php _e('Author:','famous-quotes') ?></label>
                <input type="text" id="aeditor" name="quote_author" size=58 value="<?php echo $author ?>" <?php echo $styleborder ?> />
				<script type="text/javascript">edToolbar1();</script>
                <script type="text/javascript">var edCanvas1 = document.getElementById('aeditor');</script><br />
                
				<p> <a href=" <?php echo $urlaction ?>"><?php _e('Cancel','famous-quotes') ?></a>&nbsp;
         	   <input type="submit" name="save"  class="button-primary" value="<?php _e('Update quote','famous-quotes') ?> &raquo;" /></p>
			</form><p>&nbsp;</p></div><?php 
	
		}	
		
	} else { //this "else" separates the edit form from the list of quotes. make it a "else if" below to revert to the old ways
		
		?><div class="wrap">
        <h2><?php _e('Manage quotes','famous-quotes') ?></h2><?php 
		
		$nothingmessage = __('Please select something first.','famous-quotes');
		$wrongmessage = __('Something went wrong.','famous-quotes');
	
		//action: save the quote
		if ( $action == 'edit_save' ) {
		
			//assign variables, trim, replace spaces
			$quote = !empty($_REQUEST['quote_quote']) ? trim($_REQUEST['quote_quote']) : '';	
			$author = !empty($_REQUEST['quote_author']) ? trim($_REQUEST['quote_author']) : '';
	
			//magic quotes
			if ( ini_get('magic_quotes_gpc') )	{
			
				$quote = stripslashes($quote);
				$author = stripslashes($author);
			}
			
			//negative feedback or UPDATE
			if ( empty($quoteID) )	{
				?><div id="message" class="error fade"><p><?php _e('<strong>Failure:</strong> No quote ID given.','famous-quotes') ?></p></div><?php
			}
			
			else {		
				//update the quote
				$curl = curl_init();
				//Set some options
				curl_setopt_array($curl, array(
					CURLOPT_RETURNTRANSFER => 1,
					CURLOPT_URL => '127.0.0.1:8000/quote/'.$quoteID,
					CURLOPT_CUSTOMREQUEST => 'PUT',
					CURLOPT_POSTFIELDS => http_build_query(
						array(
							authorName => $author,
							quoteContent => $quote,
						)
					)
				));

				$resp = curl_exec($curl);
				curl_close($curl);
				$resp = json_decode($resp);
				
				//feedback
				if ( empty($resp->status) || $resp->status != 'success' )	{			
					?><div id="message" class="error fade"><?php echo $wrongmessage ?></div><?php				
				}
				else {			
					?><div id="message" class="updated fade"><p>
					<?php echo str_replace("%s",$quoteID,__('Quote <strong>%s</strong> updated.'.$plusmessage,'famous-quotes'));?></p></div><?php
				}		
			}
		}
		
		//action: delete quote
		else if ( $action == 'delete' ) {
			
			//delete a quote

			$curl = curl_init();
			//Set some options
			curl_setopt_array($curl, array(
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_URL => '127.0.0.1:8000/quote/'.$quoteID,
				CURLOPT_CUSTOMREQUEST => 'DELETE',
			));

			$resp = curl_exec($curl);

			curl_close($curl);
			$resp = json_decode($resp);
			
			if ( !empty($resp->status) && $resp->status == 'success' )	{			
				?><div class="updated"><p><?php echo str_replace("%s",$quoteID,__('Quote <strong>%s</strong> deleted.','famous-quotes')); ?></p></div><?php
			}			
			else {						
				?><div class="error fade"><?php echo $resp ?></div><?php	
			}		
		}

		// prepares WHERE condition (categories/users)
		$where = '';
		if (!$categories || $categories == 'all') {
			
			if(!current_user_can('manage_options'))$where = " ";
			else $where = '';
			
		} else {
			
			if(!current_user_can('manage_options'))$where = " ";
			else $where = " ";
			
		}
			
		
		// how many rows we have in database
		$numrows = $wpdb->get_var("SELECT COUNT(`quoteID`) as rows FROM " . WP_FAMOUS_QUOTES_TABLE . $where);

		//temporary workaround for the "division by zero" problem
		if (is_string($rows))$rows=intval($rows);
		settype($rows, "integer"); 
		
		// how many pages we have when using paging?
		if ($rows == NULL || $rows < 10) $rows = 10; 
		$maxPage = ceil($numrows/$rows);		
		
		// print the link to access each page (thanks to http://www.php-mysql-tutorial.com/wikis/php-tutorial/paging-using-php.aspx)
		$nav  = '';
		for($quotepage = 1; $quotepage <= $maxPage; $quotepage++) {
			
			//with few pages, print all the links
			if ($maxPage < 4) {
				
				if ($quotepage == $pages)$nav .= $quotepage; // no need to create a link to current page
				else $nav .= ' <a href="'.querystrings($urlaction, 'qp', $quotepage).'">'.$quotepage.'</a> ';
			
			//with many pages
			} else {
				
				if ($quotepage == $pages)$nav .= $quotepage; // no need to create a link to current page
				else if ($quotepage == 1 || $quotepage == $maxPage)$nav .= ''; //no need to create first and last (they are created by the first and last links afterwards)
				else {
					
					//print links that are close to the current page (< 2 steps away)
					if ( ($quotepage < ($pages+2)) && ($quotepage > ($pages-2)) )$nav .= ' <a href="'.querystrings($urlaction, 'qp', $quotepage).'">'.$quotepage.'</a> ';
					
					//otherwise they're dots
					else {
						
						if ($pages > 3) $fdot = '.';
						if ($pages != ($maxPage-1)) $ldot = '.';
					}
					
				}
				
			}
		   
		}

		//print first and last, next and previous links
		if ($pages > 1) {
			$quotepage  = $pages - 1;		
			$prev  = ' <a href="'.querystrings($urlaction, 'qp', $quotepage).'" title="Previous '.$rows.'">&laquo;</a> ';		
			if ($maxPage > 4) $first = ' <a href="'.querystrings($urlaction, 'qp', '1').'">1</a> '.$fdot.' ';
		}
		else {
		   $prev  = '&nbsp;'; // we're on page one, don't print previous link
		   if ($maxPage > 4) $first = '&nbsp;';  //nor the first page link
		}
		
		if ($pages < $maxPage) {
		
			$missing = $numrows-($rows*$pages);		
			if ($missing > $rows) $missing = $rows;
			
			$quotepage = $pages + 1;
			$next = ' <a href="'.querystrings($urlaction, 'qp', $quotepage).'" title=" Next '.$missing.'">&raquo;</a> ';
			if ($maxPage > 4) $last = ' ' .$ldot.' <a href="'.querystrings($urlaction, 'qp', $maxPage).'"> '.$maxPage.'</a> ';
		}
		else {
		   $next = '&nbsp;'; // we're on the last page, don't print next link
		   if ($maxPage > 4) $last = '&nbsp;';  //nor the last page link
		}		
	
		//get all the quotes

		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => '127.0.0.1:8000/quote'
		));

		$resp = curl_exec($curl);

		curl_close($curl);

		$quotes = json_decode($resp);
		
		//page number has to be reset to 1 otherwise it would look like you have no quotes left when you are on a page too high for so many quotes.
		$urlrows = querystrings($urlaction, 'qp', '1');
	
		//HTML		
		$bulkurl = remove_querystring_var($_SERVER['REQUEST_URI'], 'qa');
		$bulkurl = remove_querystring_var($bulkurl, 'qi');
		?><form name="bulkform" id="bulkform" method="post" action="<?php echo $bulkurl ?>">
        <div class="tablenav">
        
        
		
		<?php
		
		//build table
		if ( !empty($quotes) ) {
			$imgasc = WP_FAMOUS_QUOTES_PATH . 'img/s_asc.png';
			$imgdsc = WP_FAMOUS_QUOTES_PATH . 'img/s_desc.png';
			?><table class="widefat" id="famousmanage">
            
            <?php //column headers ?>
			<thead><tr>  
				
				<th scope="col" style="white-space: nowrap;"> <?php if ($numrows != 1) { if ( $orderby != 'quoteID') { ?>
				<a href="<?php echo querystrings($urlaction, 'qo', 'quoteID'); ?>" title="Sort"><?php _e('ID','famous-quotes') ?></a>
				<?php } else { _e('ID','famous-quotes');
					if ($sort == 'ASC') { ?><a href="<?php echo querystrings($urlaction, 'qs', 'DESC'); ?>">
					<img src= <?php echo $imgasc ?> alt="Descending" title="Descending" /></a> <?php }
					else if ($sort == 'DESC') { ?><a href="<?php echo querystrings($urlaction, 'qs', 'ASC'); ?>">
					<img src= <?php echo $imgdsc ?> alt="Ascending" title="Ascending" /></a> <?php } ?>
							
				<?php } }else{ _e('ID','famous-quotes'); }?>            
				</th>
				
				<th scope="col"> <?php _e('Quote','famous-quotes') ?> </th>
				
				<th scope="col" style="white-space: nowrap;"> <?php if ($numrows != 1) { if ( $orderby != 'author') { ?>
				<a href="<?php echo querystrings($urlaction, 'qo', 'author'); ?>"><?php _e('Author','famous-quotes') ?></a>
				<?php } else { _e('Author','famous-quotes');
					if ($sort == 'ASC') { ?><a href="<?php echo querystrings($urlaction, 'qs', 'DESC'); ?>">
					<img src= <?php echo $imgasc ?> alt="Descending" title="Descending" /></a> <?php }
					else if ($sort == 'DESC') { ?><a href="<?php echo querystrings($urlaction, 'qs', 'ASC'); ?>">
					<img src= <?php echo $imgdsc ?> alt="Ascending" title="Ascending" /></a> <?php } ?>
				<?php } }else{ _e('Author','famous-quotes'); } ?>            
				</th>
                
				<th scope="col">&nbsp;</th>
				<th scope="col">&nbsp;</th>

				<?php if(current_user_can('manage_options') && $quotesoptions['famous_multiuser'] == true) { ?>
                <th scope="col" style="white-space: nowrap;"> <?php if ($numrows != 1) { if ( $orderby != 'user') { ?>
				<a href="<?php  echo querystrings($urlaction, 'qo', 'user'); ?>"><?php _e('User','famous-quotes') ?></a>
				<?php } else { _e('User','famous-quotes');
					if ($sort == 'ASC') { ?><a href="<?php echo querystrings($urlaction, 'qs', 'DESC'); ?>">
					<img src= <?php echo $imgasc ?> alt="Descending" title="Descending" /></a> <?php }
					else if ($sort == 'DESC') { ?><a href="<?php echo querystrings($urlaction, 'qs', 'ASC'); ?>">
					<img src= <?php echo $imgdsc ?> alt="Ascending" title="Ascending" /></a> <?php } ?>
				<?php }}else{ _e('User','famous-quotes'); }  ?>            
				</th><?php } ?>
				
                
			</tr></thead>
                
            <?php //table rows ?>
            <tbody><?php
			
			$i = 0;	
			foreach ( $quotes as $quote ) {
				$alt = ($i % 2 == 0) ? ' class="alternate"' : ''; ?>		
				<tr <?php echo($alt); ?> <?php if( $quote->user != $current_user->user_nicename ) echo ' style="color:#aaa"' ?> >
					
					<th scope="row"><?php echo ($quote->id); ?></th>
					<td><?php echo(nl2br($quote->quoteContent)); ?></td>
					<td><?php echo($quote->author->authorName); ?></td>
										
					<td align="center">
					<a href="<?php echo querystrings( querystrings($urlaction, 'qa', 'edit'), 'qi', $quote->id ); ?>">
					<?php _e('Edit','famous-quotes') ?></a></td>
	
					<td align="center">
					<a href="
					<?php echo querystrings( querystrings($urlaction, 'qa', 'delete'), 'qi', $quote->id );  ?>"
					onclick="if ( confirm('<?php echo __( 'You are about to delete quote ','famous-quotes') . $quote->id . '.\\n\\\'' . __('Cancel','famous-quotes') . '\\\' ' . __('to stop','famous-quotes') . ', \\\'OK\\\' ' . __('to delete','famous-quotes') . '.\''; ?>) ) { return true;}return false;"><?php echo __('Delete','famous-quotes') ?></a></td>			
                    
                    <?php if(current_user_can('manage_options') && $quotesoptions['famous_multiuser'] == true) { ?>
					<td><?php if( $quote->user == $current_user->user_nicename )echo ''; else echo $quote->user; ?></td>
                    <?php } ?>
                    
				</tr>
				<?php $i++; 
			} ?>
			</tbody>
            
            <?php //end table and navigation ?>
            </table></form><?php
			
		} else { ?><p><div style="clear:both"> <?php echo str_replace("%s1",get_option('siteurl')."/wp-admin/admin.php?page=famous_manage",__('<br/>No quotes here. Maybe you want to <a href="%s1">reopen</a> this page.','famous-quotes')); ?> </div></p>
		</div><?php	}	
	}

?></div><?php

}


?>