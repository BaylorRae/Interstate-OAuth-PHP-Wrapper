<?php

class Interstate_Request extends Interstate {

	private static $_errors		= array(
		
		400	=> 'The OAuth server did not understand your request. Please make sure you are requesting the correct resource and sending the correct parameters(if required)',
		401	=> 'You do not have permission to access this resource. If you believe you should have access double check that your API keys are correct.',
		404 => 'Object/method requested does not exist.',
		503	=> 'The Interstate API is down for maintenance.'
		
	);
	private $_object;
	private $_method;
	private $_get	= array();
	private $_post	= array();
	private $_parent;
	private $_curl;
	private $_verb;
	private $_json;
	
	public function __construct( Interstate $parent ) {
		
		$this->_parent	= $parent;
		$this->_curl	= curl_init();
		
	}
	
	public function sendTokenHeader() {
	
		curl_setopt( $this->_curl, CURLOPT_USERPWD, $this->_parent->_consumerKey . ':' . $this->_parent->_consumerSecret );
		
		return $this;
		
	}
	
	public function request( $object, $method ) {
	
		$this->_curl	= curl_init();
		$this->_object	= $object;
		$this->_method	= $method;
		$this->_get		= array();
		$this->_post	= array();
		$this->_verb	= null;
		$this->_json	= false;

		curl_setopt( $this->_curl, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $this->_curl, CURLOPT_FOLLOWLOCATION, 1 );
		
		return $this;
		
	}
	
	public function get( Array $data = array() ) {
	
		$this->_get = $data;
		
		return $this;
	
	}
	
	public function post( $data, $json = false ) {
		
		$this->_json	 = $json;
		$this->_post	+= $data;
	
		return $this;
		
	}
	
	public function verb( $verb ) {
		
		$this->_verb = $verb;
		
		return $this;
		
	}
	
	public function send() {

		$url = $this->_parent->getRequestUrl( $this->_object, $this->_method, $this->_get );
		
		if( count( $this->_post ) > 0 ) {
			
			curl_setopt( $this->_curl, CURLOPT_POST, 1 );

			if( $this->_json === true ) {
					
				curl_setopt( $this->_curl, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json' ) ); 
				curl_setopt( $this->_curl, CURLOPT_POSTFIELDS, json_encode( $this->_post ) );
			
			} else {

				curl_setopt( $this->_curl, CURLOPT_POSTFIELDS, $this->_post );
			
			}
					
		} else {
			
			curl_setopt( $this->_curl, CURLOPT_POST, 0 );
		
		}
		
		curl_setopt( $this->_curl, CURLOPT_URL, $url );
		
		if( $this->_verb !== null ) {
			
			curl_setopt( $this->_curl, CURLOPT_CUSTOMREQUEST, $this->_verb );
		
		}
				
		$data	= curl_exec( $this->_curl );
		$error	= curl_errno( $this->_curl );
		
		if( $error === 0 ) {

			$code = curl_getinfo( $this->_curl, CURLINFO_HTTP_CODE );
			
			if( $code === 200 ) {
				
				return $this->_decode( $data );
			
			} else {
								
				throw new Interstate_Exception( self::$_errors[ $code ], $code );
			
			}
			
		} else {
			
			throw new Interstate_Exception( 'cURL encountered an error (code: ' . $error . ')' );
		
		}	
	
		return $this;
	
	}
	
	protected function _decode( $data ) {
	
		switch( $this->_parent->_format ) {
			
			case 'json':
				
				return json_decode( $data );
			
				break;
		
		}
		
	}

}