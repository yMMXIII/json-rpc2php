<?php
/*
					COPYRIGHT

Copyright 2012 Stijn Van Campenhout <stijn.vancampenhout@gmail.com>

This file is part of JSON-RPC2PHP.

JSON-RPC2PHP is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

JSON-RPC2PHP is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with JSON-RPC2PHP; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * This class builds a json-RPC 2.0 Server
 * http://www.jsonrpc.org/spec.html
 *
 * original idea from jsonrpcphp class of Sergio Vaccaro <sergio@inservibile.org>, http://jsonrpcphp.org/
 * @author stijn <stijn.vancampenhout@gmail.com>
 * @version  1.2
 */
class jsonRPCServer {
	/**
	 * Contains available classes
	 * @var array
	 */
	public $classes = array();
	/**
	 * Contains all requests
	 * @var array
	 */
	public $requests = array();
	/**
	 * contains current to be handled request
	 * @var array
	 */
	public $request;
	/**
	 * contains current to be called class
	 * @var string
	 */
	public $extension;
	/**
	 * contains the current response array
	 * @var array
	 */
	public $response;
	/**
	 * contains the responses
	 * @var array
	 */
	public $responses = array();
	/**
	 * errorcode to shortmessage
	 * @var array
	 */
	private $errorMessages = array(
		'-32700' => 'Parse error',
		'-32600' => 'Invalid request',
		'-32601' => 'Method not found',
		'-32602' => 'Invalid parameters',
		'-32603' => 'Internal error',
		'-32604' => 'Authentication error',
		'-32000' => 'Extension not found'
		);
	/**
	 * errorcode to fullmessage
	 * @var array
	 */
	private $errorMessagesFull = array(
		'-32700' => 'Invalid JSON was received by the server. An error occurred on the server while parsing the JSON string.',
		'-32600' => 'The JSON sent is not a valid Request object.',
		'-32601' => 'The method does not exist / is not available.',
		'-32602' => 'Invalid method parameters.',
		'-32603' => 'Internal Server error.',
		'-32000' => 'The requested extension does not exist / is not available.',
		'-32604' => 'User unknown / Password / Session id incorrect.'
		);
	/**
	 * camelstyle message to errorcode
	 * @var array
	 */
	private $errorCodes = array(
		'parseError' 			=> '-32700',
		'invalidRequest'		=> '-32600',
		'methodNotFound'		=> '-32601',
		'invalidParameters'		=> '-32602',
		'internalError'			=> '-32603',
		'authenticationError'	=> '-32604',
		'extensionNotFound'		=> '-32000'
		);
	/**
	 * contains the user => password
	 * @var array
	 */
	private $users = array();
	/**
	 * Register a class as an extension
	 * methods will be available as [class].[method]
	 *
	 * @param object $obj
	 * return boolean
	 */
	public function registerClass($obj){
		$this->classes[get_class($obj)] = $obj;
		return true;
	}
	/**
	 * Adds a user that's allowed to access the RPC
	 * @param  string $user     Username
	 * @param  string $password Password
	 * @return Bool             Return true
	 */
	public function registerUser($user,$password){
		$this->users[$user] = $password;
		foreach ($this->users as $user => $pass){
		}
		return true;
	}
	/**
	 * saves the current response into the responses array and
	 * empties the current response
	 *
	 * @return Bool             Return status of saving
	 */	
	private function addResponse(){
		error_log("saving response: ".json_encode($this->response));
		if (isset($this->response) && is_array($this->response)){
			$this->responses[] = $this->response;
			$this->response = "";
			return true;
		} else {
			return false;
		}
	}
	/**
	 * Handles the authentication
	 * @param  Array $HTTPHeaders Contains the apache_request_headers()
	 */
	private function authenticate($HTTPHeaders){
		foreach($HTTPHeaders as $i => $c){
			$HTTPHeaders[strtolower($i)] = $c;
		}
		if (isset($HTTPHeaders['x-rpc-auth-username']) && isset($HTTPHeaders['x-rpc-auth-password'])){
			if ($this->users[$HTTPHeaders['x-rpc-auth-username']] == $HTTPHeaders['x-rpc-auth-password']){
				session_start();
				$sid = session_id();
				$_SESSION["ip"] = $_SERVER["REMOTE_ADDR"];
				header('x-RPC-Auth-Session: ' . $sid);
			} else {
				throw new Exception($this->errorCodes['authenticationError']);
			}
		} else if (isset($HTTPHeaders['x-rpc-auth-session'])){
			session_id($HTTPHeaders['x-rpc-auth-session']);
			session_start();
			if ($_SESSION['ip'] == $_SERVER["REMOTE_ADDR"]){
				return true;
			} else {
				throw new Exception($this->errorCodes['authenticationError']);
			}
		} else {
				throw new Exception($this->errorCodes['authenticationError']);
		} 

	}
	/**
	 * responses to 'rpc.' calls.
	 *
	 */
	public function rpcCalls() {
		error_log(json_encode($this->request));
		if ($this->request['method'] == "listMethods"){
			foreach ($this->classes as $ext => $class){
				$methods[$ext] = get_class_methods($class);
			}
			if (isset($this->request['params']['extension'])){
				if (array_key_exists($this->request['params']['extension'],$this->classes)){
					$this->ok(array($this->request['params']['extension'] => $methods[$this->request['params']['extension']]));
				} else {
					$this->error($this->errorCodes['extensionNotFound'],"requested extension not found in extension list." );
				}
			} else {
					$this->ok($methods);
			}
		} else {
			$this->error($this->errorCodes['methodNotFound']);
		}
		return true;
	}
	/**
	 * returns true if the given array is an assoc
	 * See: http://stackoverflow.com/a/4254008/973416
	 * 
	 * @param  array   $array the array to investigate
	 * @return boolean        is assoc array
	 */
	private function is_assoc(array $array) {
		return (bool)count(array_filter(array_keys($array), 'is_string'));
	}
	/**
	 * This function validates the incoming json string
	 * - checks the request method
	 * - checks if we can parse the json
	 * - checks if the extension exists
	 * - checks if the method exists in the given extension
	 *
	 * @return boolean
	 */
	private function validate() {
		try {
			$request = $this->request;
			$requestMethod = explode('.',$request['method']);
			$this->extension = $requestMethod[0];
			if (!isset($this->classes[$this->extension]) && $this->extension != "rpc"){
				throw new Exception($this->errorCodes['extensionNotFound']);
			}
			$request['method'] = $requestMethod[1];
			$this->request = $request;
			if (!method_exists($this->classes[$this->extension],$request['method']) && $this->extension != "rpc"){
				throw new Exception($this->errorCodes['methodNotFound']);
			}
		} catch (Exception $e) {
				$this->error($e->getMessage());
				$this->addResponse();
				return false;
		}
		return true;
	}
	/**
	 * Builds the error response
	 *
	 * @param string $c the error code
	 * @param string $fmsg the full message of the error
	 */
	private function error($c,$fmsg=false){
		$this->response = array (
				'jsonrpc'	=> '2.0',
				'id' => (isset($this->request['id'])) ? $this->request['id'] : NULL,
				'result' => NULL,
				'error'	=> array(
					'code'	=> (int)$c,
					'message' => (isset($this->errorMessages[$c])) ? $this->errorMessages[$c] : 'internalError',
					
					'data'	=> array(
						'request' => (isset($this->request)) ? $this->request : NULL,
						'extension' => (isset($this->extension)) ? $this->extension : NULL,
						'fullMessage' => ($fmsg) ? $fmsg : $this->errorMessagesFull[$c]
						)
					)
				);
		return true;
	}
	private function toUtf8(array $array) { 
	    $convertedArray = array(); 
	    foreach($array as $key => $value) { 
			if(!mb_check_encoding($key, 'UTF-8')) $key = utf8_encode($key); 
			if(is_array($value)) $value = $this->toUtf8($value); 

			$convertedArray[$key] = $value; 
	    } 
	    return $convertedArray; 
  	}	
  	/**
  	 * Creates a successfull response
  	 * @param  array/string/bool $result result of executed rpc method 
  	 */
	private function ok($result){
					//print_r($result);
					$this->response = array (
						'jsonrpc'	=> '2.0',
						'id' => $this->request['id'],
						'result' => $result,
						'error' => NULL
						);
					error_log("ok:".json_encode($this->response));
	}
	/**
	 * check if there is a response needed & sends the response(s)
	 *
	 */
	private function sendResponse(){
		/**
		 * Check if there are more than one responses.
		 * If not, only respond with the first (and only)
		 * array value.
		 */
		if (!isset($this->responses[1])){
			if (!empty($this->request['id'])) { // notifications don't want response
				header('content-type: application/json');
				error_log(json_encode($this->responses));
				die( json_encode($this->responses[0]) );
			}
		}
		$responses = array();
		foreach ($this->responses as $response) {
			if (empty($this->request['id'])) { // notifications don't want response
				continue;
			} else {
				$responses[] = $response;
			}
		}

		header('content-type: application/json');
		die( json_encode($responses) );

	}
	
	/**
	 * main class method. starts all the magic
	 *
	 */
	public function handle() {
		if ($_SERVER['REQUEST_METHOD'] != 'POST' || empty($_SERVER['CONTENT_TYPE']) || strpos($_SERVER['CONTENT_TYPE'], 'application/json') === false) {
			throw new Exception($this->errorCodes['invalidRequest']);
		}
		$this->requests = json_decode(file_get_contents('php://input'),true);
		if (empty($this->requests)){
			throw new Exception($this->errorCodes['parseError']);
		}
		// Check if this is a batch request
		if ($this->is_assoc($this->requests)){
			// convert it in a "batch" request
			$this->requests = array($this->requests);
		}
		error_log("requests=".json_encode($this->requests));
		if (!empty($this->users)){
 			$this->authenticate(apache_request_headers());
 		}
		foreach ($this->requests as $request) {

			$this->request = $request;

			$this->validate();

			try {
				error_log("this->extension=".$this->extension);

				if ($this->extension == "rpc"){
					$this->rpcCalls();
				} else {
					$obj = $this->classes[$this->extension];
					error_log("calling: ".$this->extension."->".$this->request['method']."(".join(",",$this->request['params']).")");
					if (($result = @call_user_func_array(array($obj,$this->request['method']),$this->request['params'])) !== false) {
						error_log("result:".$result);
						$this->ok((is_array($result)) ? $result : array($result));
					} else {
						throw new Exception('Method function returned false.');
					}
				}
			} catch (Exception $e) {
					$c = ($e->getCode() != 0) ? $e->getCode : $this->errorCodes['internalError'];
					$this->error($c,$e->getMessage());
			}
			/**
			 * Save the current response
			 */
			$this->addResponse();
			error_log("current responses:".json_encode($this->responses));
		}

		$this->sendResponse();
		return true;
	}

}
?>
