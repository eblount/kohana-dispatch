<?php defined('SYSPATH') OR die('Kohana bootstrap needs to be included before tests run');
/**
 * Tests Dispatch Module
 *
 * @see			Controller_Dispatch_Test
 * @group		dispatch
 * @package		Dispatch
 * @category	Tests
 * @author		Micheal Morgan <micheal@morgan.ly>
 * @copyright	(c) 2011 Micheal Morgan
 * @license		MIT
 */
class Kohana_DispatchTest extends Unittest_TestCase
{
	/**
	 * Default config
	 * 
	 * @access	protected
	 * @var		array
	 */
	protected $_config = array
	(
		'namespace'		=> 'dispatch',
		'extension' 	=> 'json'
	);
	
	/**
	 * Provider for test_path
	 *
	 * @access	public
	 * @return	array
	 */
	public static function provider_path()
	{
		return array
		(
			array
			(
				array('user', 2, 'email', 5),
				'user/2/email/5'
			),
			array
			(
				'account/3/user/2',
				'account/3/user/2'
			)
		);
	}	
	
	/**
	 * Test path handling
	 * 
	 * @covers			Dispatch::factory
	 * @covers			Dispatch_Request::path
	 * @dataProvider	provider_path
	 * @access			public
	 * @return			void
	 */
	public function test_path($provided, $expected)
	{
		// Test Dispatch::factory
		$this->assertSame(Dispatch::factory($provided)->path(), $expected);

		// Test Dispatch_Request::path
		$dispatch = Dispatch_Request::factory()->path($provided);

		$this->assertSame($dispatch->path(), $expected);
	}
	
	/**
	 * Internal and external configuration to test consistency across 
	 * Client-Dispatcher-Server pattern.
	 *
	 * @access	public
	 * @return	array
	 */
	public static function provider_config()
	{
		return array
		(
			array
			(
				array
				(
					'namespace'		=> 'dispatch',
					'extension'		=> 'json',
					'attempt_local'	=> TRUE
				)
			),
			array
			(
				array
				(
					'url'			=> URL::site(),
					'namespace'		=> 'dispatch',
					'extension'		=> 'php',
					'attempt_local'	=> FALSE
				)
			)
		);
	}	
	
	/**
	 * Tests HTTP code handling
	 * 
	 * @covers			Dispatch::factory
	 * @covers			Dispatch_Request::factory
	 * @covers			Dispatch_Response::factory
	 * @covers			Dispatch_Request::where
	 * @covers			Dispatch_Response::loaded
	 * @dataProvider	provider_config
	 * @access			public
	 * @return			void
	 */	
	public function test_http_code($config)
	{
		$dispatch = Dispatch::factory('test', Dispatch_Connection::factory($config));

		$this->assertTrue($dispatch->find()->loaded(), 'Expecting resource to have loaded.');
		
		$dispatch->where('code', 500);
		
		$this->assertFalse($dispatch->find()->loaded(), 'Invalid HTTP status code should not validate as a loaded resource.');
	}
	
	/**
	 * Test pass-through
	 * 
	 * @covers	Dispatch::factory
	 * @covers	Dispatch::execute
	 * @covers	Dispatch_Kohana_Response::get_body
	 * @access	public
	 * @return	void
	 */
	public function test_pass_through()	
	{
		$dispatch = Dispatch::factory('test', Dispatch_Connection::factory($this->_config + array('attempt_local' => TRUE)));
		
		$result = $dispatch->execute();
		
		$response = $result->get_response();
		
		$this->assertTrue(method_exists($response, 'get_body'), 'Dispatch_Kohana_Response is providing Response raw body.');

		$this->assertInstanceOf('Model_Dispatch_Test', $response->get_body());
	}
	
	/**
	 * Tests request
	 * 
	 * @covers			Dispatch::factory
	 * @covers			Dispatch_Request::factory
	 * @covers			Dispatch_Response::factory
	 * @covers			Dispatch_Request::execute
	 * @covers			Dispatch_Response::loaded
	 * @dataProvider	provider_config
	 * @access			public
	 * @return			void
	 */	
	public function test_request($config)
	{
		$methods = array(Request::GET, Request::POST, Request::PUT, Request::DELETE);

		$connection = Dispatch_Connection::factory($config);
		
		foreach ($methods as $method)
		{			
			$dispatch = Dispatch::factory('test', $connection);
			
			$response = $dispatch->execute($method);
			
			$this->assertSame($response->loaded(), TRUE);
		}
	}
}