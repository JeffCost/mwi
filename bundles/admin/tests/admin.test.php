<?php namespace Admin;

require_once(path('app').'tests/ControllerTestCase.php');
require_once(path('app').'tests/Helper.php');

class Admin_Test extends \ControllerTestCase
{
    public function setUp()
    {
        \Laravel\Session::load();
        \Bundle::start('settings');
        \Bundle::start('modules');
        \bundle::start('auth');
        \Bundle::start('themes');
        \Bundle::start('admin');
    }

    public function test_dependency_is_loaded()
    {
        $this->assertTrue(\Bundle::exists('settings'));
        $this->assertTrue(\Bundle::exists('modules'));
        $this->assertTrue(\Bundle::exists('auth'));
        $this->assertTrue(\Bundle::exists('themes'));
    }

    public function test_bundle_is_loaded()
    {
        $this->assertTrue(\Bundle::exists('admin'));
    }

    public function test_can_call_get_index()
    {
        $response = $this->get('admin::backend.admin@index');
        $this->assertEquals(302, $response->foundation->getStatusCode());
        
        //This test fails when not routing with any
        // check Tests\Helper class
        //$result = \Tests\Helper::http_request('GET','admin');
        //$this->assertEquals(302, $result->foundation->getStatusCode()); 
    }

    public function tearDown()
    {
        // \Bundle::$started = array();
        // \Bundle::$elements = array();
        
        // \Bundle::disable('settings');
        // \Bundle::disable('themes');
        // \Bundle::disable('admin');
    }
}