<?php
require_once( 'lib/Arc90/Service/Twitter.php' );

$twdash_twitter_service = new Arc90_Service_Twitter( $_SERVER['twdash_username'] , $_SERVER['twdash_password'] );

switch( $_GET['action'] )
{
case "twdash_update_tweet":
	//FB::log( 'twdash_twitter_update: begin' );
	$twitter_response = $twdash_twitter_service->updateStatus( $status );
	echo $twitter_response->getData();
	break;
default:
	return "default";
}

?>
