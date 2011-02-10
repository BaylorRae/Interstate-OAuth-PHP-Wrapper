<?php

session_start();

define( 'CONSUMER_KEY', '' );
define( 'CONSUMER_SECRET', '' );
define( 'REDIRECT_URI', '' );

include 'Interstate.php';

$interstate = new Interstate( CONSUMER_KEY, CONSUMER_SECRET, REDIRECT_URI );

if( isset( $_GET[ 'code' ] ) !== false ) {
			
	if( $tokens = $interstate->getAccessToken( $_GET[ 'code' ] ) ) {
		
		$_SESSION[ 'tokens' ] = $tokens;
	
	}
	
}

if( isset( $_SESSION[ 'tokens' ] ) !== false ) {
	
	$interstate->setAccessToken( $_SESSION[ 'tokens' ]->access_token );

}

$response = false;

if( isset( $_SESSION[ 'tokens' ] ) !== false ) {
	
	$run = true;
	
	while( $run ) {
		
		try {
				
			$response	= $interstate->fetch( 'account/verify' );
			$run		= false;
			
		} catch( Interstate_Exception $e ) {
			
			if( $tokens = $interstate->getAccessToken( $_SESSION[ 'tokens' ]->refresh_token, 'refresh_token' ) ) {
				
				$_SESSION[ 'tokens' ] = $tokens;
				
			} else {
				
				unset( $_SESSION[ 'tokens' ] );
				
				$run = false;
				
			}
				
		}
	
	}
			
}

if( $response !== false ) {
	
	var_dump($response);

} else {
	
	echo '<a href="' . $interstate->getAuthorizeUrl() . '">Sign in with Interstate</a>';

}