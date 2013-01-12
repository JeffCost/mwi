<?php namespace Auth;

//require_once(path('app').'tests/ControllerTestCase.php');
require_once(path('app').'tests/Helper.php');

class Auth_Test extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        \Tests\Helper::migrate();
        \Tests\Helper::use_sessions();
    }
    
    public function setUp()
    {
        \Laravel\Session::load();
        \Bundle::start('settings');
        \Bundle::start('modules');
        \Bundle::start('themes');
        \Bundle::start('admin');

        \Bundle::start('users');
        \DB::table('users')->delete();

        $user = new \Users\Model\User;
        $user->group_id = 56;
        $user->uuid = 'uuid';
        $user->username = 'JoeDoe';
        $user->avatar_first_name = 'user';
        $user->avatar_last_name = 'name';
        $user->hash = 'hash';
        $user->salt = 'salt';
        $user->password = \Hash::make('password');
        $user->email = 'test@user.com';
        $user->is_core = 0;
        $user->save();

    }

    public function test_user_cant_login_with_wrong_password()
    {
        $result = \Tests\Helper::http_request('POST','login', array('username' => 'test@user.com', 'password' => 'nopass'));

        $this->assertEquals(302, $result->foundation->getStatusCode());
        
        $session_errors = \Laravel\Session::instance()->get('errors');

        $this->assertNotEmpty($session_errors);
    }

    public function test_user_can_login()
    {
        
        $result = \Tests\Helper::http_request('POST','login', array('username' => 'test@user.com', 'password' => 'password'));

        $this->assertEquals(302, $result->foundation->getStatusCode());
        
        $session_errors = \Laravel\Session::instance()->get('errors');

        $this->assertNull($session_errors);
    }

    public function test_user_can_logout()
    {
        
        $result = \Tests\Helper::http_request('GET','logout');

        $this->assertEquals(302, $result->foundation->getStatusCode());
        
        $session_errors = \Laravel\Session::instance()->get('errors');

        $this->assertNull($session_errors);
    }

    public function test_user_should_not_login()
    {
        $credentials = array(
            'username' => 'example@gmail.com',
            'password' => 'secret',
        );
        
        $this->assertFalse(\Auth::attempt($credentials));
    }

    public function tearDown()
    {
    }
}