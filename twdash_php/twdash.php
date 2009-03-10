<?php
/*
Plugin Name: Twitter Dashboard Widget
Plugin URI: http://www.atlguy.com/twdash
Description: Brings twitter to your dashboard
Version: 0.9
Author: Curtis Dawson
Author URI: http://www.cdawson.com

Copyright 2009  Curtis Dawson  (email : curtis@cdawson.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/
require_once( 'lib/Arc90/Service/Twitter.php' );

function twdash_dashboard_callback() 
{
	if ( !current_user_can( 'manage_options' ) )
		return;
		
	$twdash_username = get_option( 'twdash_username' );
	$twdash_password = get_option( 'twdash_password' );
	
	if ( !isset( $twdash_username ) && !isset( $twdash_password ) )
		twdash_get_user_info();
	else
	{
		$_SERVER['twdash_username'] = $twdash_username;
		$_SERVER['twdash_password'] = $twdash_password;
		twdash_twitter_home();
	}
}

function twdash_add_dashboard_widget() 
{
	wp_add_dashboard_widget( 'twdash_dashboard', __( 'Twitter' ), 'twdash_dashboard_callback' );
}

function twdash_get_user_info()
{
?>
	<div class="wrap">
	<h2>Twitter Dashboard Widget</h2>
	<form method="post" action="options.php">
	<?php wp_nonce_field( 'update-options' ); ?>
	<table class="form-table">
		<tr valign="top">
		<th scope="row"><?php _e( 'username or email address:'); ?></th>
		<td><input type="text" name="twdash_username" value="<?php echo get_option( 'twdash_username' ); ?>" /></td>
		</tr>
		<tr valign="top">
		<th scope="row"><?php _e( 'password:' ); ?></th>
		<td><input type="password" name="twdash_password" value="<?php echo get_option( 'twdash_password' ); ?>" /></td>
		</tr>
	</table>
	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="page_options" value="twdash_username,twdash_password" />
	<p class="submit">
	<input type="submit" name="Submit" value="<?php _e('Save Changes') ?>" />
	</p>
	</form>
	</div>


<?php
}

function twdash_print_twitter_home( $json_str )
{
	$json = json_decode($json_str, true);
	/**
	 * DNFL:
	 * Add Twitter feed refresh
	 * Add Twitter update form
	 * DONE: Add timestamp functionality
	 * Mimic reply, favorites functionality (on hover)
	 * Show Latest tweet
	 * Mimic character count functionality
	 * Add Delete tweet button (on hover)
	 * Add styling
	 * Add Show Older (Prev) tweets link
	 * ADD BACK BUTTON TO ALL PAGES
	 * Add usernames to beginning of tweets
	 * Recognize/link @username text
	 */

?>
	<div class="wrap">
	<form class="twdash_update_form" method="post">
		<!-- <input type="hidden" name="action" value="twdash_update_tweet"/> -->
		<table>
			<tr>
				<td><h2>What are you doing?</h2></td>
				<td><p class="twdash_twitter_char_count" id="twdash_twitter_char_count">140</p></td>
			</tr>
			<tr>
				<td colspan="2"><textarea name="twdash_update_text" rows="2" cols="50"></textarea></td>
			</tr>
			<tr>
				<td><p class="twdash_twitter_latest_label">Latest: </p><p class="twdash_twitter_latest_text" id="twdash_twitter_latest_text"></p></td>
				<td>
					<p class="submit"><input type="button" onclick="twdash_ajax_twitter_update( this.form.twdash_update_text );" name="Submit" value="<?php _e( 'Update' ) ?>" /></p>
				</td>
			</tr>
		</table>
	</form>
	</div>
	<div class="twdash_tweet_box">
		<table cellspacing="10">
		<?php foreach ( $json as $tweet ) : ?>
			<tr class="twdash_tweet_row">
				<td class="twdash_profile_image"><img src="<?php echo $tweet['user']['profile_image_url'] ?>" /></td>
				<td class="twdash_tweet_text_block"><p class="twdash_twitter_username"><?php echo $tweet['user']['screen_name'] ?></p> <p class="twdash_twitter_text"><?php echo $tweet['text'] ?></p> <p class="twdash_twitter_timestamp"><?php echo twdash_parse_time( strtotime( $tweet['created_at'] ) ) ?></p></td>
			</div>
		<?php endforeach; ?>
		</table>
	</div>
<?php
}

function twdash_twitter_update( $twdash_twitter_service, $status )
{
	FB::log( 'twdash_twitter_update: begin' );
	$twitter_response = $twdash_twitter_service->updateStatus( $status );
	
	return $twitter_response->toJSON();
	//print_r( $twitter_response );	
	
}

function twdash_twitter_home()
{
	$twdash_twitter_service = new Arc90_Service_Twitter( $_SERVER['twdash_username'] , $_SERVER['twdash_password'] );

	try  
	{   
		// Gets the authenticated user's friends timeline in XML format  
		$response = $twdash_twitter_service->getFriendsTimeline( 'json' );

		// If Twitter returned an error (401, 503, etc), print status code  
		if( $response->isError() )
		{
			echo $response->http_code . "\n";
			twdash_get_user_info();
		}
		else
		{
			// Print the XML response
			echo twdash_print_twitter_home( $response->getData() );
		}
	}
	catch( Arc90_Service_Twitter_Exception $e )
	{
		// Print the exception message (invalid parameter, etc)
		print $e->getMessage();
		twdash_get_user_info();
	}
}


function twdash_js_admin_header()
{
	wp_print_scripts ( array( 'jquery' ) );

?>
<script type="text/javascript">
//<![CDATA[
function twdash_ajax_twitter_update( status )
{
	var updateObj = 
	{ 
		"action" : "twdash_update_tweet",
		"twdash_update_text" : status.value
	}

	jQuery.getJSON( "<?php echo WP_PLUGIN_URL . '/twdash/twdash_action.php' ?>", updateObj, twdash_ajax_twitter_update_callback );
		
}

function twdash_ajax_twitter_update_callback(that)
{
	var noop = "";
}
//]]>
</script>
<?php
}

function twdash_parse_time( $past )
{
	$interval = twdash_date_diff( time(), $past );
	if( $interval['years'] > 0 )
		return ( $interval['years'] == 1 ) ? $interval['years'] . " year ago" : $interval['years'] . " years ago";
	elseif( $interval['months_total'] > 0)
		return ( $interval['months_total'] == 1 ) ? $interval['months_total'] . " month ago" : $interval['months_total'] . " months ago";
	elseif( $interval['days_total'] > 0 )
		return ( $interval['days_total'] == 1 ) ? $interval['days_total'] . " day ago" : $interval['days_total'] . " days ago";
	elseif( $interval['hours_total'] > 0 )
		return ( $interval['hours_total'] == 1 ) ? $interval['hours_total'] . " hour ago" : $interval['hours_total'] . " hours ago";
	elseif( $interval['minutes_total'] > 0 )
		return ( $interval['minutes_total'] == 1 ) ? $interval['minutes_total'] . " minute ago" : $interval['minutes_total'] . " minutes ago";
	elseif( $interval['seconds_total'] > 0 )
		return ( $interval['seconds_total'] == 1 ) ? $interval['seconds_total'] . " second ago" : $interval['seconds_total'] . " seconds ago";
}

function twdash_stylesheet()
{
?>
	<style type="text/css">
		p
		{
			display:inline;
		}
		
		p.twdash_twitter_text
		{
		}

		p.twdash_twitter_timestamp
		{
		}

		p.twdash_twitter_username
		{
			font-weight: bold;
		}
		
		form.twdash_update_form 
		{
			margin-bottom: 10px;
		}
		
		div.twdash_tweet_row
		{
			margin-top: 10px;
			margin-bottom: 10px;
			border-top: 1px dash #000000;
		}
			td.twdash_profile_image
			{
				width: 50px;
			}
			
			td.twdash_tweet_text_block
			{
				margin-left: 55px;
			}
	</style>
<?php
}

function twdash_date_diff( $d1, $d2 )
{
    $d1 = (is_string( $d1 ) ? strtotime( $d1 ) : $d1);
    $d2 = (is_string( $d2 ) ? strtotime( $d2 ) : $d2);

    $diff_secs = abs( $d1 - $d2 );
    $base_year = min( date( "Y", $d1 ), date( "Y", $d2 ) );

    $diff = mktime( 0, 0, $diff_secs, 1, 1, $base_year );
    return array(
        "years" => date( "Y", $diff ) - $base_year,
        "months_total" => ( date( "Y", $diff ) - $base_year ) * 12 + date( "n", $diff ) - 1,
        "months" => date( "n", $diff ) - 1,
        "days_total" => floor( $diff_secs / ( 3600 * 24 )),
        "days" => date("j", $diff) - 1,
        "hours_total" => floor( $diff_secs / 3600 ),
        "hours" => date( "G", $diff ),
        "minutes_total" => floor( $diff_secs / 60 ),
        "minutes" => (int) date( "i", $diff ),
        "seconds_total" => $diff_secs,
        "seconds" => (int) date( "s", $diff )
    );
}

if( isset( $_POST['action'] ) )
{
	$twdash_twitter_service = new Arc90_Service_Twitter( $_SERVER['twdash_username'] , $_SERVER['twdash_password'] );
	
	switch( $action )
	{
	case "twdash_update_tweet":
		twdash_twitter_update( $twdash_twitter_service, $_POST['twdash_update_text'] );
		twdash_twitter_home( $twdash_twitter_service );
		break;
	default:
		twdash_twitter_home( );
	}
}

add_action ( 'admin_print_scripts', 'twdash_js_admin_header' );
add_action ( 'admin_head', 'twdash_stylesheet' );
add_action ( 'wp_dashboard_setup', 'twdash_add_dashboard_widget' );
?>
