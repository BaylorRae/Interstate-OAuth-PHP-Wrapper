<?php

class Interstate {
	
	const ROOT_URL				= '://interstateapp.com/';
	const API_URL				= '://api.interstateapp.com/';
	const API_VERSION			= 1;
	protected $_useHttps		= true;
	protected $_accessToken		= null;
	protected $_consumerKey		= null;
	protected $_consumerSecret	= null;
	protected $_redirectUri		= null;
	protected $_format			= 'json';
	private static $_request;
	
	public function __construct( $consumerKey, $consumerSecret, $redirectUri, $useHttps = true ) {
		
		$this->_consumerKey		= $consumerKey;
		$this->_consumerSecret	= $consumerSecret;
		$this->_redirectUri		= $redirectUri;
		$this->_useHttps		= (bool)$useHttps;
	
		spl_autoload_register( __CLASS__ . '::autoload' );
	
	}
	
	public static function autoload( $name ) {
	
		$path = str_replace( '_', '/', $name );
		
		if( file_exists( dirname( __FILE__ ) . '/' . $path . '.php' ) !== false ) {
			
			require dirname( __FILE__ ) . '/' . $path . '.php';
		
		}
			
	}

	public function getRootUrl() {
	
		return ( ( $this->_useHttps ) ? 'https' : 'http' ) . self::ROOT_URL;
	
	}

	public static function getApiUrl() {
	
		return ( ( $this->_useHttps ) ? 'https' : 'http' ) . self::API_URL . 'v' . self::API_VERSION . '/';
	
	}
	
	public function getRequestUrl( $object, $method, $get = array() ) {
	
		$url = ( ( $this->_useHttps ) ? 'https' : 'http' ) . self::API_URL . 'v' . self::API_VERSION . '/' . $object . '/' . $method;
		
		foreach( $get as $key => $value ) {
			
			$url .= '/' . $key . '/' . $value;
		
		}
		
		$url .= '.' . $this->_format;
		
		if( $this->_accessToken !== null ) {
		
			$url .= '?oauth_token=' . $this->_accessToken;
		
		}
		
		return $url;
			
	}
	
	public function setAccessToken( $token ) {
	
		$this->_accessToken = $token;
	
	}
	
	public function getAuthorizeUrl() {
	
		return $this->getRootUrl() . 'oauth2/authorize?client_id=' . $this->_consumerKey . '&redirect_uri=' . $this->_redirectUri . '&response_type=code';
	
	}
	
	public function getAccessToken( $code, $type = 'authorization_code', $setToken = true ) {

		$uri	= self::API_URI . 'v1/oauth2/token';
		$post	= array(
			
			'redirect_uri'	=> $this->_redirectUri,
			'client_id'		=> $this->_consumerKey,
			'client_secret'	=> $this->_consumerSecret
		
		);
		
		switch( $type ) {
			
			default:
				case 'authorization_code':
				
				$post += array(
				
					'grant_type'	=> 'authorization_code',
					'code' 			=> $code,
					
				);
								
				break;
				
			case 'refresh_token':
			
				$post += array(
				
					'grant_type'		=> 'refresh_token',
					'refresh_token' 	=> $code,
				
				);
							
				break;
				
		}
		
		try {
			
			$data = $this->request( 'oauth2', 'token' )->post( $post )->send();
		
			if( property_exists( $data, 'error' ) ) {
				
				return false;
			
			} else {
				
				if( $setToken === true ) {
				
					$this->setAccessToken( $data->access_token );
					
				}
				
				return $data;
			
			}
		
		} catch( Interstate_Exception $e ) {
			
			return false;
			
		}
		
	}
	
	public function destroyToken( $type, $token ) {
		
		if( $type === 'access_token' || $type === 'refresh_token' ) {
		
			return $this->fetch( 'token/destroy/type/' . $type . '/token/' . $token, array(), true )->verb( 'DELETE' )->send();
		
		} else {
			
			throw new Interstate_Exception( 'Invalid token type passed.' );
		}
		
	}
	
	public function fetch( $url, Array $post = array(), $returnObject = false ) {
	
		$parts	= explode( '/', $url );
		$object	= $parts[ 0 ];
		$get	= array();
			
		if( isset( $parts[ 1 ] ) !== false ) {
			
			$method = $parts[ 1 ];
		
		} else {
			
			throw new Interstate_Exception( 'Invalid method given.' );
		
		}
		
		if( count( $parts ) > 2 ) {
			
			for( $i = 2; $i < ( count( $parts ) - 1 ); $i++ ) {
			
				if( isset( $parts[ $i ] ) ) {
					
					$get[ urlencode( $parts[ $i ] ) ] = urlencode( $parts[ $i + 1 ] );
					
					$i++;
				
				}
							
			}
		
		}
		
		$request = $this->request( $object, $method )->get( $get )->post( $post, true );
		
		if( $returnObject === true ) {
		
			return $request;
			
		} else {
		
			return $request->send();
		
		}
		
	}

	public function request( $object, $method ) {
	
		if( ( self::$_request instanceof Interstate_Request ) !== true ) {
			
			self::$_request = new Interstate_Request( $this );
		
		}
		
		self::$_request->request( $object, $method );
		
		return self::$_request;
	
	}

}