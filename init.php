<?php
if ( ! defined( 'ABSPATH' ) ) exit;
define("WEBOT_ENV", 'prod');
define("WEBOT_ROOT", __DIR__);
define("WEBOT_URI", rtrim(plugin_dir_url(__FILE__), '/'));
require "autoload.php";