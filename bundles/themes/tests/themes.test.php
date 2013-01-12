<?php

class Themes_Test extends \PHPUnit_Framework_TestCase
{
    public $layout;

    public $theme;

    public static function setUpBeforeClass()
    {
        \Bundle::start('settings');
        \Bundle::start('themes');

        // setting used when calling theme->set_theme()
        $frontend_theme = Config::get('settings::core.frontend_theme');
        if(is_null($frontend_theme))
        {

            $frontend_theme = 'basic_theme';
            // We dont have this setting in the database
            // lets load some values
            $new_layout = new Settings\Model\Setting;
            $new_layout->title         = 'frontend_theme_title';
            $new_layout->slug          = 'frontend_theme';
            $new_layout->description   = 'frontend_theme_desc';
            $new_layout->type          = 'text';
            $new_layout->default       = $frontend_theme;
            $new_layout->value         = $frontend_theme;
            $new_layout->options       = '';
            $new_layout->class         = '';
            $new_layout->is_gui        = 0;
            $new_layout->module_slug   = 'themes';
            $new_layout->module_id     = 0;
            $new_layout->order         = 0;
            $new_layout->save();
        }
        
        $frontend_layout = Config::get('settings::core.frontend_layout');
        
        if(is_null($frontend_layout))
        {

            $frontend_layout = 'basic_layout';
            // We dont have this setting in the database
            // lets load some values
            $new_layout = new Settings\Model\Setting;
            $new_layout->title         = 'frontend_layout_title';
            $new_layout->slug          = 'frontend_layout';
            $new_layout->description   = 'frontend_layout_desc';
            $new_layout->type          = 'text';
            $new_layout->default       = 'basic';
            $new_layout->value         = 'basic';
            $new_layout->options       = '';
            $new_layout->class         = '';
            $new_layout->is_gui        = 0;
            $new_layout->module_slug   = 'themes';
            $new_layout->module_id     = 0;
            $new_layout->order         = 0;
            $new_layout->save();
        }

        $backend_layout = Config::get('settings::core.backend_layout');
        
        if(is_null($backend_layout))
        {

            $backend_layout = 'basic_admin_layout';
            // We dont have this setting in the database
            // lets load some values
            $new_layout = new Settings\Model\Setting;
            $new_layout->title         = 'backend_layout_title';
            $new_layout->slug          = 'backend_layout';
            $new_layout->description   = 'backend_layout_desc';
            $new_layout->type          = 'text';
            $new_layout->default       = 'basic_admin_layout';
            $new_layout->value         = 'basic_admin_layout';
            $new_layout->options       = '';
            $new_layout->class         = '';
            $new_layout->is_gui        = 0;
            $new_layout->module_slug   = 'themes';
            $new_layout->module_id     = 0;
            $new_layout->order         = 0;
            $new_layout->save();
        }
    }

    public function setUp()
    {
        $this->theme = IoC::resolve('Theme');
    }

    public function tearDown()
    {
    }

    public function test_theme_can_be_stantiated()
    {
        // Tests created theme when started bundle
        $theme = \IoC::resolve('Theme');

        $this->assertEquals('base', $theme->_theme_name);

        $this->assertNotEmpty($theme);
    }

    public function test_set_theme()
    {
        $this->theme->set_theme('basic_theme_test', 'frontend', 'path: bundles/themes/tests/basic_theme_test');
        
        $this->assertNotEmpty($this->theme);

        $this->assertEquals('basic_theme_test', $this->theme->_theme_name);
    }

    public function test_theme_original_absolute_path()
    {
        $this->assertEquals($this->theme->_theme_base_path.'bundles/themes/tests/basic_theme_test', $this->theme->_theme_absolute_path);
    }

    public function test_theme_new_absolute_path()
    {
        $this->theme->set_theme('basic_theme_test', '', 'path: bundles/themes/tests/basic_theme_test');

        $this->assertEquals($this->theme->_theme_base_path.'bundles/themes/tests/basic_theme_test', $this->theme->_theme_absolute_path);
    }

    public function test_theme_path()
    {
        $this->assertEquals('bundles/themes/tests/basic_theme_test', $this->theme->_theme_path);
    }

    public function test_set_layout()
    {
        // FRONTEND SET LAYOUT
        // change layout and update database layout settings
        $this->theme->set_layout('basic_layout', 'frontend', true);

        $this->assertEquals('basic_layout', $this->theme->_layout);

        // Check if config was updated
        $setting_value = Config::get('settings::core.frontend_layout');
        $this->assertEquals('basic_layout', $setting_value);

        // // Check if was saved in database
        $layout = Settings\Model\Setting::where('slug', '=', 'frontend_layout')->first();
        $this->assertEquals('basic_layout', $layout->value);


        // BACKEND SET LAYOUT
        // change layout and update database layout settings
        $this->theme->set_layout('basic_admin_layout', 'backend', true);
        $this->assertEquals('basic_admin_layout', $this->theme->_layout);

        // Check if config was updated
        $setting_value = Config::get('settings::core.backend_layout');
        $this->assertEquals('basic_admin_layout', $setting_value);

        // Check if was saved in database
        $layout = Settings\Model\Setting::where('slug', '=', 'backend_layout')->first();
        $this->assertEquals('basic_admin_layout', $layout->value);
    }

    public function test_set_partial()
    {
        $this->theme->set_theme('basic_theme_test', 'frontend', 'path: bundles/themes/tests/basic_theme_test');
        $this->theme->set_partial('header', array('username' => 'jeff'));
        $this->theme->set_partial('nav');
        $this->theme->set_partial('footer');

        $arr = array('partials' => array('header' => array('username' => 'jeff'), 'nav' => array(), 'footer' => array()));

        $this->assertEquals($arr, $this->theme->_theme_partials);
    }

    public function test_append_and_prepend_metadata()
    {
        $arr = array('meta append1', 'meta append2');
        $this->theme->append_metadata('meta append1');
        $this->theme->append_metadata('meta append2');
        $this->assertEquals($arr, $this->theme->_theme_metadata);

        $arr = array('meta prepend2', 'meta prepend1', 'meta append1', 'meta append2');
        $this->theme->prepend_metadata('meta prepend1');
        $this->theme->prepend_metadata('meta prepend2');
        $this->assertEquals($arr, $this->theme->_theme_metadata);
    }

    public function test_add_asset()
    {
        $this->theme->add_asset('jquery.js');
        $this->theme->add_asset('style.css');

        // $container = $this->getContainer();
        // $styles = $container->styles();
        // $scripts = $container->scripts();

        $this->assertEquals('themes/bundles/themes/tests/basic_theme_test/assets/css/style.css', Asset::container()->assets['style']['style.css']['source']);
        $this->assertEquals('themes/bundles/themes/tests/basic_theme_test/assets/js/jquery.js', Asset::container()->assets['script']['jquery.js']['source']);
    }

    public function test_add_asset_with_dependencies()
    {
        $this->theme->add_asset('style.css', 'path: bundles/themes/tests/basic_theme_test/assets', array('jquery'));
        $this->theme->add_asset('jquery.js', 'path: bundles/themes/tests/basic_theme_test/assets', array('jquery-ui'));

        $this->assertEquals(array('jquery'), Asset::container()->assets['style']['style.css']['dependencies']);
        $this->assertEquals(array('jquery-ui'),Asset::container()->assets['script']['jquery.js']['dependencies']);
    }

    public function test_render_view()
    {
        //$this->theme->set_layout('basic_admin_layout', 'backend');
        $this->theme->set_partial('nav', array('menu' => 'menu_data'));
        $this->theme->set_theme('basic_theme_test', 'backend', 'path: bundles/themes/tests/basic_theme_test');
        $page = $this->theme->render('index', array('username' => 'jeff'));

        $this->assertEquals('<h1>Basic Admin Test Layout</h1>Index Content View jeff<div>test footer partial<div>', trim($page->render()));

        $this->assertEquals('path: '.$this->theme->_theme_absolute_path.'/views/index.blade.php', $page->theme_content->view);
    }

    public function test_render_partial_view()
    {
        $partial = $this->theme->render_partial('footer');

        $this->assertEquals($this->theme->_theme_absolute_path.'/views/partials/footer'.BLADE_EXT, $partial->path);
    }

    public function testVariableShouldBeAvaliableInPartial()
    {
        $this->theme->set_partial('nav', array('menu' => 'menu_data'));
        $this->theme->set_theme('basic_theme_test', 'backend', 'path: bundles/themes/tests/basic_theme_test');
        $page = $this->theme->render('index', array('username' => 'jeff', 'footer_menu' => 'string_test'));

        $this->assertEquals('<h1>Basic Admin Test Layout</h1>Index Content View jeff<div>test footer partial<div>string_test', trim($page->render()));

        $this->assertEquals('path: '.$this->theme->_theme_absolute_path.'/views/index.blade.php', $page->theme_content->view);
    }

    public function testRenderViewFromBundle()
    {
        $page = $this->theme->render('auth::frontend.login');
        $this->assertEquals('path: '.path('bundle').'auth/views/frontend/login.blade.php', $page->theme_content->view);
    }

    public function testRenderBundleViewWithPartial()
    {
        $this->theme->set_partial('themes::footer_test');
        
        $this->assertEmpty($this->theme->_theme_partials['partials']['themes::footer_test']);

        $this->theme->set_partial('themes::footer_test', array('username' => 'Jeff'));
        
        $this->assertNotEmpty($this->theme->_theme_partials['partials']['themes::footer_test']);
    }

    public function testRenderBundleViewCustomPath()
    {
        $this->theme->_theme_partials['partials'] = array();
        
        $page = $this->theme->render('bundles.auth.views.frontend.login');
        
        $this->assertEquals('path: '.'bundles/auth/views/frontend/login.blade.php', $page->theme_content->view);
    }

    /**
    * Get an asset container instance.
    *
    * @param string $name
    * @return Asset_Container
    */
    private function getContainer($name = 'foo')
    {
        return new Laravel\Asset_Container($name);
    }
}
