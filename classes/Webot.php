<?php
class Webot {

	private static $_endpoint = WEBOT_ENV == 'dev' ? 'https://webot.localhost.com' : 'https://webotchatbot.com';

	private static $_options = [];

	private static $_allowed_options = ["hostname" => "text", "status" => "text", "enabled" => "bool", "placeholder" => "text", "welcome_delay" => "number", "error" => "text", "primary_color" => "hex_color"];

	private static $__instance = null;

	public $info = [];

	public function __construct() {
		self::$__instance = $this;
		if(!function_exists('get_plugin_data')) require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		$this->info = get_plugin_data(WEBOT_ROOT.'/webot-chatbot.php');
	}

	public static function instance(){
		if(!self::$__instance) self::$__instance = new self();
		return self::$__instance;
	}

	public static function version(){
		$instance = self::instance();
		return $instance->info['Version'] ?? '';
	}

	public static function cleanOptionsData($data){
		$filtered = [];
		if(!empty($data)){
			foreach(self::$_allowed_options as $o => $type){
				if(isset($data[$o])){
					$v = wp_unslash($data[$o]);
					switch($type){
						case 'bool':
							$v = (is_numeric($v) && $v == 1) || $v == 'true' ? 1 : 0;
							break;
						case 'number':
							$v = is_numeric($v) && $v >= 0 ? $v : '';
							break;
						case 'hex_color':
							$v = sanitize_hex_color($v);
							break;
						default:
							$v = sanitize_text_field($v);
					}
					$filtered[$o] = esc_html($v);
				}
			}
		}
		return $filtered;
	}

	public static function getOptions($force = false){
		$options = [];
		foreach(self::$_allowed_options as $o => $type) $options[$o] = '';
		$saved_options = $force || empty(self::$_options) ? get_option('webot', []) : [];
		self::$_options = array_merge($options, self::$_options, $saved_options);
		return self::$_options;
	}

	public static function updateOptions($data, $check_status = true){
		$hostname_changed = false;
		$data = self::cleanOptionsData($data); //@note: filters and sanitizes the data
		if(isset($data['hostname'])){
			$data['hostname'] = self::getHostname($data['hostname']);
			$options = self::getOptions();
			$hostname_changed = $data['hostname'] != $options['hostname'];
		}
		$options = array_merge(self::$_options, $data);
		update_option('webot', $options); //returns false when nothing was updated
		self::$_options = $options;
		if($check_status && ($hostname_changed || (self::$_options['hostname'] && !self::$_options['status']))) self::checkStatus();
		return true;
	}

	public static function getHostname($domain){
		$domain = ltrim($domain, "/");
		if(!preg_match("#^https?://#", $domain)) $domain = "http://".ltrim($domain);
		if(!filter_var($domain, FILTER_VALIDATE_URL)) return '';
		$parts = wp_parse_url($domain);
		return $parts['host'];
	}

	public static function checkStatus($hostname = null){
		$options = self::getOptions();
		if($hostname && $hostname != $options['hostname']){
			self::updateOptions(['hostname' => $hostname], false); //false to avoid infinite loop.
			$options = self::getOptions();
		}
		$hostname = $options['hostname'];
		$status = '';
		$error = '';
		if($hostname){
			$body = [
				'domain' => $hostname,
				'return_info' => 1,
				'source' => 'plugin',
				'cms' => 'WP',
				'plugin_version' => self::version()
			];
			ksort($body);
			$time = time();
			$encoded = wp_json_encode($body);
			$key = md5($time.$encoded);
			$body['time'] = $time;
			$body['token'] = hash_hmac('sha256', $encoded, $key);
			$args = [
				'headers' => ['Referer' => self::$_endpoint],
				'body' => $body
			];

			$r = wp_remote_post(self::$_endpoint.'/api/domain/add', $args);
			if(!is_wp_error($r)){
				$body = json_decode($r['body'] ?: '{}', true);
				if(isset($body['status'])){
					$status = $body['status'];
					if($status == 'success'){
						$res = $body['data'] ?? [];
						if(!empty($res) && is_array($res)){
							$status = $res['is_active'] ? $res['crawl_status'] : 'inactive';
							if($status == 'crawled' && $res['can_chat']) $status = 'ready';
							if($status == 'crawled' && !$res['can_chat']) $status = 'Not Available';
							if($res['error_message']){
								$error = $res['error_message'];
							}
						}
					}
					$error = $error ?: ($status == 'error' ? ($body['error'] ?? $body['messages'] ?? '') : '');
				}
			}
		}
		$data = self::cleanOptionsData(array_merge(self::$_options, ['status' => $status, 'error' => $error]));

		if(update_option('webot', $data)) self::$_options = $data;
		return self::$_options;
	}


	public function init(){
		if(is_admin()){
			if(WEBOT_ENV == 'dev') add_filter( 'https_ssl_verify', '__return_false' );
			add_action( 'admin_enqueue_scripts', 'Webot::addAdminScripts');
			add_action('admin_menu', 'Webot::addAdminPages');
			add_action('wp_ajax_webot_ajax', 'Webot::ajax');
		} else {
			add_action('wp_footer', "Webot::addCodeSnippet");
		}
	}

	public static function addAdminScripts(){
		wp_enqueue_style('webot-admin-style', WEBOT_URI.'/assets/css/admin.css', false, filemtime(WEBOT_ROOT.'/assets/css/admin.css'));
		wp_enqueue_script('webot-admin', WEBOT_URI.'/assets/js/admin.js', ['jquery', 'wp-color-picker'], filemtime(WEBOT_ROOT.'/assets/js/admin.js'), true);
		wp_localize_script( 'webot-admin', 'webot_settings', ['endpoint' => admin_url( 'admin-ajax.php')]);
	}

	public static function renderAdminPage($page){
		include WEBOT_ROOT.'/admin/'.$page.'.php';
	}

	public static function addAdminPages(){
		$icon = '<?xml version="1.0" encoding="UTF-8"?><svg id="Layer_2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 249.21 171.11" width="20px"><defs><style>.cls-1{fill:currentColor;stroke-width:0px;}</style></defs><g id="Layer_1-2"><path class="cls-1" d="M248.26,166.3l-33.86-29.66c12.63-13.29,19.78-30.67,19.78-52.16C234.17,28.16,185.09,0,116.92,0,82.49,0,41.9,7.07,25.14,21.2,8.38,35.34,0,56.43,0,84.47s8.38,49.14,25.14,63.27c16.76,14.14,57.35,21.2,91.78,21.2,19.99,0,38.34-2.42,54.29-7.27,1.84-.56,3.78-.74,5.69-.47l69.17,9.87c2.7.39,4.25-2.98,2.2-4.78ZM51.34,105.61c-8.92,0-16.15-7.23-16.15-16.15s7.23-16.15,16.15-16.15,16.15,7.23,16.15,16.15-7.23,16.15-16.15,16.15ZM116.54,105.61c-8.92,0-16.15-7.23-16.15-16.15s7.23-16.15,16.15-16.15,16.15,7.23,16.15,16.15-7.23,16.15-16.15,16.15ZM181.74,105.61c-8.92,0-16.15-7.23-16.15-16.15s7.23-16.15,16.15-16.15,16.15,7.23,16.15,16.15-7.23,16.15-16.15,16.15Z"/></g></svg>';
		$icon = 'data:image/svg+xml;base64,'.base64_encode($icon);
		add_menu_page(
			'Webot Chatbot',
			'Webot',
			'manage_options',
			'webot',
			function(){ self::renderAdminPage('webot'); },
			$icon,
			20
		);
	}

	public static function addCodeSnippet(){
		$options = self::getOptions();
		$hostname = $options['hostname'];
		$enabled = $options['enabled'];
		if($hostname && $enabled){
			$options = array_intersect_key($options, array_flip(["hostname", "placeholder", "welcome_delay", "primary_color"]));
			$options = wp_json_encode($options);
			$snippet = '<script type="module" id="webot-chatbot-snippet">';
			$snippet .= "const options = {$options};";
			$snippet .= "const s = document.createElement('script');";
			$snippet .= "Object.assign(s, { src: 'https://webotchatbot.com/chat/assets/webot.min.js', onload: function(){ new Webot(options).mount(); } });";
			$snippet .= "document.body.appendChild(s);";
			$snippet .= '</script>';

			echo wp_kses($snippet, ['script' => ['type' => [], 'id' => []]]);

		}
	}

	public static function ajax(){
		$r = ["success" => true, "error" => "", "data" => []];
		$action = esc_html(sanitize_text_field(wp_unslash($_POST['a'] ?? '')));
		$errors = [];
		$data = [];
		$nonce = sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'] ?? ''));
		if(!wp_verify_nonce($nonce, 'webot-check-status')) $errors[] = 'Unauthorized! Please reload the page and try again!';
		else {
			switch($action){
				case 'check':
					$hostname = esc_url(sanitize_text_field(wp_unslash($_POST['hostname'] ?? '')));
					if(!$hostname){
						$options = self::getOptions();
						$hostname = $options['hostname'];
					}
					if(!$hostname) $errors[] = 'Website is required.';
					if(empty($errors)){
						$data = self::checkStatus($hostname);
					}
					break;
				default:
					$r['success'] = false;
					$r['error'] = "Invalid action";
					break;
			}
		}

		$r['data'] = $data;
		$error = implode('<br>', $errors);
		if($error){
			$r['success'] = false;
			$r['error'] = $error;
		}
		echo wp_json_encode($r);
		exit();
	}


}