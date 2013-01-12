<?php

include(path('app').'tests/Helper.php');

class TestUserModel extends PHPUnit_Framework_TestCase {

    public static function setUpBeforeClass()
    {
        \Tests\Helper::migrate();
        \Tests\Helper::use_sessions();
    }

    public function setUp()
    {
        \Bundle::start('settings');
        \Bundle::start('modules');
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

    public function test_user_should_not_login()
    {
        $credentials = array(
            'username' => 'example@gmail.com',
            'password' => 'secret',
        );
        
        $this->assertFalse(\Auth::attempt($credentials));
    }

    public function test_user_should_exists()
    {
        $user = \Users\Model\User::where('username', '=', 'JoeDoe')->first();

        $this->assertEquals($user->email, 'test@user.com');
    }

    public function test_user_should_login()
    {
        $credentials = array(
            'username' => 'test@user.com',
            'password' => 'password',
        );
        
        $this->assertTrue(\Auth::attempt($credentials));
    }

    /*
     Should reset password
    */
    // public function testUserShouldResetPassword()
    // {
    //     $old_password = 'secret';

    //     $params = array(
    //         'username' => 'example',
    //         'email' => 'example@gmail.com',
    //         'password' => $old_password,
    //     );

    //     User::signup($params);
    //     $user = User::order_by('id','desc')->first();
    //     $new_password = $user->reset_password();

    //     // new password should be different from the old one
    //     $this->assertNotEquals($new_password, $old_password);

    //     // should login with new password
    //     $credentials = array(
    //         'username' => 'example@gmail.com',
    //         'password' => $new_password,
    //     );
    //     $this->assertTrue(Auth::attempt($credentials));
    // }
}