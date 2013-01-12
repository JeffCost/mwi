<?php

include(path('app').'tests/Helper.php');

class Settings_Test extends \PHPUnit_Framework_TestCase
{
    public $org_value;
    
    public static function setUpBeforeClass()
    {
        //\Tests\Helper::migrate();
        //\Tests\Helper::use_sessions();
    }

    public function setUp()
    {
        \Bundle::start('settings');
        $this->org_value = Config::get('settings::core.frontend_theme');

        if(is_null($this->org_value))
        {

            $this->org_value = 'basic';
            // We dont have this setting in the database
            // lets load some values
            $new_setting = new Settings\Model\Setting;
            $new_setting->title         = 'frontend_theme_title';
            $new_setting->slug          = 'frontend_theme';
            $new_setting->description   = 'frontend_theme_desc';
            $new_setting->type          = 'text';
            $new_setting->default       = 'basic';
            $new_setting->value         = 'basic';
            $new_setting->options       = '';
            $new_setting->class         = '';
            $new_setting->is_gui        = 0;
            $new_setting->module_slug   = 'themes';
            $new_setting->module_id     = 0;
            $new_setting->order         = 0;
            $new_setting->save();
        }
        
    }

    public function tearDown()
    {
        // When testing with sqlite memory is fine
        // because after the test finishes the database
        // is creared. When using mysql reset the database 
        // to its original value (testing database)
        Settings\Config::set('settings::core.frontend_theme', $this->org_value);
    }

    public function test_set_setting()
    {
        Settings\Config::set('settings::core.frontend_theme', 'test_theme_test');

        $setting_value = Config::get('settings::core.frontend_theme');

        $this->assertEquals('test_theme_test', $setting_value);

        $persistence = Settings\Model\Setting::where('slug', '=', 'frontend_theme')->first();

        $this->assertEquals('test_theme_test', $persistence->value);
    }

    public function test_set_non_persistent_setting()
    {
        Settings\Config::set('settings::core.frontend_theme', 'test_theme_test', false);

        $setting_value = Settings\Config::get('settings::core.frontend_theme');

        $this->assertEquals('test_theme_test', $setting_value);

        $persistence = Settings\Model\Setting::where('slug', '=', 'frontend_theme')->first();

        $this->assertNotEquals('test_theme_test', $persistence->value);
    }

    public function test_should_get_null_for_non_existent_setting()
    {
        $setting_value = Settings\Config::get('settings::core.test_frontend_theme_test');

        $this->assertNull($setting_value);
    }

    public function test_should_not_persist_non_existend_setting()
    {
        Settings\Config::set('settings::core.frontend_themexxx', 'test_theme_test', true);

        $setting_value = Settings\Config::get('settings::core.frontend_themexxx');

        $this->assertNull($setting_value);
    }
}
