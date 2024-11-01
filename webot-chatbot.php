<?php
/*
 * Plugin Name: 	Webot Chatbot
 * Plugin URI: 		https://webotchatbot.com
 * Description: 	A Free AI Chatbot for Your Website! Increase your website engagement and sales by allowing your visitors to chat with your website AI chatbot assistant. Webot Chatbot is Free and Easy to add to your website.
 * Version: 		1.0.0
 * Author: 			Webot
 * Author URI: 		https://buymeacoffee.com/webot
 * License:			GPL v2 or later
 * License URI: 	https://www.gnu.org/licenses/gpl-2.0.html
 */
if ( ! defined( 'ABSPATH' ) ) exit;
require "init.php";
$instance = new Webot();
$instance->init();