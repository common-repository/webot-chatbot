<?php
if ( ! defined( 'ABSPATH' ) ) exit;
spl_autoload_register(function ($class_name) {
	$file = WEBOT_ROOT.'/classes/'.$class_name.'.php';
	if(file_exists($file)) include_once $file;
});