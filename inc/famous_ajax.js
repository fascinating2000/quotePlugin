jQuery(document).ready(function(){});  //needed because FF is stupid

function newQuote(categories, linkphrase, id, famousurl, multi, offset, sequence, timer, disableaspect, loading, contributor){
	
	jQuery(document).ready
	(
		function($){
			
				var divheight = $("div.famous_quote-" + id).height();
				$("div.famous_quote-" + id).height(divheight/2);
				$("div.famous_quote-" + id).css('text-align','center');
				$("div.famous_quote-" + id).css('padding-top',divheight/2);
				$("div.famous_quote-" + id).fadeOut('slow');
				$("div.famous_quote-" + id).html(loading).fadeIn('slow', function () {
																												 
					$.ajax({
							type: "POST",
							url: famousurl + "inc/famous_ajax.php",
							data: "action=newquote&categories=" + categories + "&sequence=" + sequence + "&linkphrase=" + linkphrase + "&widgetid=" + id + "&multi=" + multi + "&offset=" + offset + "&disableaspect=" + disableaspect + "&timer=" + timer + "&contributor=" + contributor,
							success: function(html){
								$("div.famous_quote-" + id).css('padding-top',null);
								$("div.famous_quote-" + id).css('height', null);
								$("div.famous_quote-" + id).after(html).remove();
							}
					});
			  });
				
		}
	)
}