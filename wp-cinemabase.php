<?php
/*
Plugin Name: Add Movie Trailers and Games Trailers to your site
Plugin URI: http://www.cinemabase.org
Description: Post Movie Trailers and Games Trailers to your site
Author: cinemabase 
Version: 1.04
*/



function _cinemabase_plugin_ver()
{
	return "1.04";
}

function _cinemabase_default_players()
{
	return "image_list_wide,A8FAUvKcqhlq,video_player,AsEA6vqDqlos,video_playlist_v,AMKARv6vB6bl";
}

$cinemabase_players = get_site_option('cinemabase_players');
if ($cinemabase_players == "")
{
	update_site_option('cinemabase_players', _cinemabase_default_players());
	$cinemabase_players = get_site_option('cinemabase_players');
}

function _cinemabase_getPlayerId($name)
{
	global $cinemabase_players;

	$name = trim($name);
	$items = explode(",", $cinemabase_players);
	if ($name == "")
		return $items[1];

	for ($i = 0; $i < count($items); $i++)
		if (trim($items[$i]) == $name)
			return $items[$i+1];

	return "unknown-".$name;
}

define("CINEMABASE_REGEXP", "/\[cinemabase([^\]]*)\]/");

function _cinemabase_tag($tag)
{
	return _cinemabase_async_plugin_callback(array(0 => $tag));
}

function _cinemabase_async_plugin_callback($match)
{
	$tmp = $match[0];
	$tmp = str_replace("[cinemabase", "", $tmp);
	$tmp = str_replace("]", "", $tmp);
	$tmp = preg_replace('/[^(\x20-\x7F)]*/','', $tmp); // remove all non ascii chars
	$var = preg_split('/\s+/m',trim($tmp)); // split a string with unknown number of spaces

	$uni = uniqid('');
	$ret = '
<!-- Cinemabase WordPress plugin '._cinemabase_plugin_ver().' (async engine): http://www.cinemabase.org -->

<div id="cb_widget_'.$uni.'"><img src="http://app.cinemabase.org/runtime/loading.gif" style="border:0;" alt="Cinemabase WordPress plugin" /></div>

<script type="text/javascript">

var zeo = [];
zeo["_object"] = "cb_widget_'.$uni.'";
zeo["_gid"] = "'._cinemabase_getPlayerId($var[1]).'";
zeo["_feedurl"] ="http://app.cinemabase.org/jsonimdb.aspx";
zeo["_feedparams"] ="&token='.get_site_option('cinemabase_token').'&imdb='.$var[0].'";

var _zel = _zel || [];
_zel.push(zeo);

(function() {
	var cp = document.createElement("script"); cp.type = "text/javascript";
	cp.async = true; cp.src = "http://app.cinemabase.org/runtime/loader.js";
	var c = document.getElementsByTagName("script")[0];
	c.parentNode.insertBefore(cp, c);
})();

</script>

';

	return $ret;
}

function _cinemabase_feed_plugin_callback($match)
{
	return preg_replace(CINEMABASE_REGEXP, '', $content);
}

function _cinemabase_plugin($content)
{
	$cinemabase_excerpt = get_site_option('cinemabase_excerpt');
	if ($cinemabase_excerpt == 'remove' && (is_search() || is_category() || is_archive() || is_home()))
		return preg_replace(CINEMABASE_REGEXP, '', $content);

	return (preg_replace_callback(CINEMABASE_REGEXP, '_cinemabase_async_plugin_callback', $content));
}

function _cinemabase_plugin_rss($content)
{
	return (preg_replace_callback(CINEMABASE_REGEXP, '_cinemabase_feed_plugin_callback', $content));
}

//add_shortcode('cinemabase'_cinemabase_plugin_shortcode');
add_filter('the_content', '_cinemabase_plugin');
add_filter('the_content_rss', '_cinemabase_plugin_rss');
add_filter('the_excerpt_rss', '_cinemabase_plugin_rss');
add_filter('comment_text', '_cinemabase_plugin'); 

add_action ( 'bp_get_activity_content_body', '_cinemabase_plugin' );
add_action ( 'bp_get_the_topic_post_content', '_cinemabase_plugin' );

// Hook for adding admin menus
// http://codex.wordpress.org/Adding_Administration_Menus
add_action('admin_menu', '_cinemabase_mt_add_pages');



// action function for above hook
function _cinemabase_mt_add_pages() {

	// Add a new submenu under Options:
	
	add_options_page('Cinemabase Options', 'Cinemabase Options', 8, 'cinemabaseoptions', '_cinemabase_mt_options_page');
}

function _cinemabase_isAdmin()
{
	return !function_exists('is_site_admin') || is_site_admin() == true;
}

function _cinemabase_mt_options_page()
{

//	if( is_site_admin() == false ) {
//		wp_die( __('You do not have permission to access this page.') );
//	}

	if (strpos($_SERVER['QUERY_STRING'], 'hide_note=welcome_notice'))
	{
		update_site_option('cinemabase_welcome_notice', _cinemabase_plugin_ver());
		echo "<script type=\"text/javascript\">	document.location.href = '".$_SERVER['HTTP_REFERER']."'; </script>";
		exit;
	}

	$cinemabase_token = get_site_option('cinemabase_token');
	$cinemabase_players = get_site_option('cinemabase_players');
	$cinemabase_excerpt = get_site_option('cinemabase_excerpt');

	if ( isset($_POST['submit']) )
	{
//		if (_cinemabase_isAdmin())
		{
			if (isset($_POST['cinemabase_token']))
			{
				$cinemabase_token = $_POST['cinemabase_token'];
				update_site_option('cinemabase_token', $cinemabase_token);
			}
			if (isset($_POST['cinemabase_players']))
			{
				$cinemabase_players = $_POST['cinemabase_players'];
				update_site_option('cinemabase_players', $cinemabase_players);
			}

		}
		if (isset($_POST['embedRel']))
		{
			$cinemabase_excerpt = $_POST['embedRel'];
			update_site_option('cinemabase_excerpt', $cinemabase_excerpt);
		}
		
		echo "<div id=\"updatemessage\" class=\"updated fade\"><p>Cinemabase settings updated.</p></div>\n";
		echo "<script type=\"text/javascript\">setTimeout(function(){jQuery('#updatemessage').hide('slow');}, 3000);</script>";	
	}

	$disp_excerpt2 = $cinemabase_excerpt == 'clean' ? 'checked="checked"' : '';
	$disp_excerpt3 = $cinemabase_excerpt == 'full' ? 'checked="checked"' : '';
	$disp_excerpt4 = $cinemabase_excerpt == 'remove' ? 'checked="checked"' : '';
	$disp_excerpt1 = $cinemabase_excerpt == '' || $cinemabase_excerpt == 'nothing' ? 'checked="checked"' : '';

?>
	<div class="wrap">
		<h2>Cinemabase Configuration</h2>
		<div class="postbox-container">
			<div class="metabox-holder">
				<div class="meta-box-sortables">
					<form action="" method="post" id="cinemabase-conf">
						<div id="cinemabase_settings" class="postbox">
							<div class="handlediv" title="Click to toggle">
								<br />
							</div>
							<h3 class="hndle">
								<span>Cinemabase Settings</span>
							</h3>
							<div class="inside" style="width:600px;">
								<table class="form-table">

									<tr style="width:100%;">
										<th valign="top" scrope="row">
											<label for="cinemabase_token">
												Cinemabase Token (<a target="_blank" href="http://app.cinemabase.org/manage/gettoken.aspx">what?</a>):
											</label>
										</th>
										<td valign="top">

											<input id="cinemabase_token" name="cinemabase_token" type="text" size="40" value="<?php echo $cinemabase_token; ?>"/>

										</td>
									</tr>


									<tr style="width:100%;">
										<th valign="top" scrope="row">
											<label for="cinemabase_players">
												Cinemabase Players (<a target="_blank" href="http://app.cinemabase.org/manage/players.aspx">what?</a>):
											</label>
										</th>
										<td valign="top">
											<textarea id="cinemabase_players" name="cinemabase_players" type="text" cols="40" rows="5"><?php echo $cinemabase_players; ?></textarea>
										</td>
									</tr>


									<tr style="width:100%;">
										<th valign="top" scrope="row">
											<label>
												Excerpt Handling (<a target="_blank" href="#excerpt">what?</a>):
											</label>
										</th>
										<td valign="top">

											<input type="radio" <?php echo $disp_excerpt1; ?> id="embedCustomization0" name="embedRel" value="nothing"/>
											<label for="embedCustomization0">Do nothing (default Wordpress behavior)</label>
											<br/>
											<input type="radio" <?php echo $disp_excerpt2; ?> id="embedCustomization1" name="embedRel" value="clean"/>
											<label for="embedCustomization1">Clean excerpt (do not show gallery)</label>
											<br/>
											<input type="radio" <?php echo $disp_excerpt4; ?> id="embedCustomization3" name="embedRel" value="remove"/>
											<label for="embedCustomization3">Remove gallery (do not show gallery in all non post pages)</label>
											<br/>
											<input type="radio" <?php echo $disp_excerpt3; ?> id="embedCustomization2" name="embedRel" value="full"/>
											<label for="embedCustomization2">Full excerpt (show gallery)</label>
											<br/>

										</td>
									</tr>



									<tr style="width:100%;">
										<th valign="top" scrope="row" colspan=2>
											Note:
<ol>
<li>Use this PHP code to add a movie trailer directly to your template : <br>&nbsp;&nbsp;&nbsp; <i>&lt;?php echo _cinemabase_tag("MOVIE_TT_CODE PLAYER"); ?&gt; </i><br> for example <br>&nbsp;&nbsp;&nbsp;<b>&lt;?php echo _cinemabase_tag("tt1598778 image_list_wide"); ?&gt; </b></li>
</ol>
										</th>
									</tr>


								</table>
							</div>
						</div>
						<div class="submit">
							<input type="submit" class="button-primary" name="submit" value="Update &raquo;" />
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
<?php
    
    
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////    Excerpt Handling   //////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////

// http://www.aaronrussell.co.uk/blog/improving-wordpress-the_excerpt/

function _cinemabase_improved_trim_excerpt($text)
{
	global $post;
	if ( '' == $text ) {
		$text = get_the_content('');

		$cinemabase_excerpt = get_site_option('cinemabase_excerpt');

		if ($cinemabase_excerpt == 'clean')
			$text = preg_replace(CINEMABASE_REGEXP, '', $text);

		$text = apply_filters('the_content', $text);

		if ($cinemabase_excerpt == 'full')
			return $text;

		$text = str_replace(']]>', ']]&gt;', $text);
		$text = preg_replace('@<script[^>]*?>.*?</script>@si', '', $text);

		$text = strip_tags($text, '<'.'p'.'>');
		$excerpt_length = 80;
		$words = explode(' ', $text, $excerpt_length + 1);
		if (count($words)> $excerpt_length) 
		{
			array_pop($words);
			array_push($words, '[...]');
			$text = implode(' ', $words);
		}
	}
			
	return $text;
}


$cinemabase_excerpt = get_site_option('cinemabase_excerpt');
if ($cinemabase_excerpt == 'full' || $cinemabase_excerpt == 'clean')
{
	remove_filter('get_the_excerpt', 'wp_trim_excerpt');
	add_filter('get_the_excerpt', '_cinemabase_improved_trim_excerpt');
}

?>