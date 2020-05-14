<?php
define('GOTOANDPLAY_IP', '90.191.43.123');
define('DISALLOW_FILE_EDIT', true);
define('THEME_DISABLE_COMMENTS', false);
define('THEME_DIRECTORY', __DIR__ . DIRECTORY_SEPARATOR);
define('THEME_DATA_DIRECTORY', THEME_DIRECTORY . 'data' . DIRECTORY_SEPARATOR);
define('THEME_LOG_DIRECTORY', THEME_DIRECTORY . 'log' . DIRECTORY_SEPARATOR);
define('THEME_LIB_DIRECTORY', THEME_DIRECTORY . 'lib' . DIRECTORY_SEPARATOR);
define('THEME_CACHE_DIRECTORY', THEME_DIRECTORY . 'cache' . DIRECTORY_SEPARATOR);
define('THEME_AJAX_NONCE', 'gotoAndPlay');
define('GOOGLE_MAPS_API_KEY', 'AIzaSyD1PwxI49DSSb3pVBnHmQcM8mg2cMX5_8c');

// must use classes
$classNames   = [
    'Timber' => [
        'name' => 'Timber',
        'url' => admin_url('plugin-install.php?tab=search&type=term&s=timber-library'),
    ],
    'Woocommerce' => [
        'name' => 'Woocommerce',
        'url' => admin_url('plugin-install.php?tab=search&type=term&s=woocommerce'),
    ],
];
$pluginErrors = [];
foreach ($classNames as $className => $pluginInfo) {
    if (!class_exists($className)) {
        add_action('admin_notices', function () use ($pluginInfo) {
            printf('<div class="error"><p>%1$s not activated. Make sure you install and activate the <a href="%2$s">plugin</a>.</p></div>', $pluginInfo['name'], $pluginInfo['url']);
        });
        $pluginErrors[] = sprintf('<li>%1$s not activated. Make sure you install and activate the <a href="%2$s">plugin</a>.</li>', $pluginInfo['name'], $pluginInfo['url']);
    }
}

if ($pluginErrors) {
    if (!is_admin()) {
        if (current_user_can('manage_options')) {
            printf('<h1>Theme setup not complete</h1><ul>%s</ul>', implode($pluginErrors));
        } else {
            print ('<h1>Site is under maintenance, will be back up shortly!</h1>');
        }

        exit;
    }

    return;
}

// gotoAndPlay autoloader

function gotoAndPlayAutoLoader($fileLocation) {
    $filename = THEME_DIRECTORY . implode(DIRECTORY_SEPARATOR, explode('\\', $fileLocation)) . ".php";
    if (is_readable($filename)) {
        require_once $filename;

        if (is_callable([$fileLocation, '__init__'])) {
            $fileLocation::__init__();
        }
    }
}

spl_autoload_register("gotoAndPlayAutoLoader");

if (!session_id()) {
    session_start();
}

if (get_option('timezone_string')) {
    date_default_timezone_set(get_option('timezone_string'));
}

$gotoAndPlay = new gotoAndPlay\Theme();


add_filter('woocommerce_package_rates', 'wf_sort_shipping_methods', 10, 2);

function wf_sort_shipping_methods($available_shipping_methods, $package)
{
	// Arrange shipping methods as per your requirement
	$sort_order	= array(
		'local_pickup'	=>	array(),
		'eabi_itella_smartpost'	=>	array(),
		'eabi_omniva_parcelterminal'		=>	array(),
		'eabi_dpd_courier'		=>	array(),
	
	);
	
	// unsetting all methods that needs to be sorted
	foreach($available_shipping_methods as $carrier_id => $carrier){
		$carrier_name	=	current(explode(":",$carrier_id));
		if(array_key_exists($carrier_name,$sort_order)){
			$sort_order[$carrier_name][$carrier_id]	=		$available_shipping_methods[$carrier_id];
			unset($available_shipping_methods[$carrier_id]);
		}
	}
	
	// adding methods again according to sort order array
	foreach($sort_order as $carriers){
		$available_shipping_methods	=	array_merge($available_shipping_methods,$carriers);
	}
	return $available_shipping_methods;
}


register_sidebar( array(
            'name' => 'Compare name',
            'id' => 'compare_name',
            'before_widget' => '<div>',
            'after_widget' => '</div>',
            'before_title' => '<h2 class="rounded">',
            'after_title' => '</h2>',
        ) );
		
function get_dynamic_sidebar($index) {
     $sidebar_contents = "";
     ob_start();
     dynamic_sidebar($index);
     $sidebar_contents = ob_get_clean();

     return $sidebar_contents;
}		
		
