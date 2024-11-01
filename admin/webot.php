<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="webot-wrap">
	<h1 class="heading"><img src="<?php echo esc_url(WEBOT_URI.'/assets/logo.svg'); ?>" alt="Webot" /> <span class="subtle">Chatbot</span><span class="version subtle">v<?php echo esc_html(Webot::version()); ?></span></h1>

	<section class="split">
		<div>
			<p>
				<strong>A Free <span class="primary-text">AI Chatbot</span> for Your Website!</strong><br>
				Increase your website <strong>engagement</strong> and <strong>sales</strong> by allowing your visitors to chat with your website AI chatbot assistant. Webot Chatbot is <strong>Free</strong> and <strong>Easy</strong> to add to your website.
			</p>
			<h2>Settings</h2>
			<?php
			$page = sanitize_text_field(wp_unslash($_GET['page'] ?? ''));
			$post = Webot::cleanOptionsData($_POST ?? []);//@note: filters and sanitizes the post data.
			$error = '';
			$success = '';
			$nonce = sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'] ?? ''));
			if(isset($_POST['submit']) && wp_verify_nonce($nonce, 'save-webot-settings')){
				if(!isset($post['enabled'])) $post['enabled'] = 0;
				$save = Webot::updateOptions($post);
				if($save) $success = 'Info have been saved successfully!';
				else $error = 'Unable to save info!';
			}

			$options = Webot::getOptions();
			$data = array_merge($options, $post);

			if($success) echo '<div class="notice notice-success is-dismissible"><p>'.esc_html($success).'</p></div>';
			if($error) echo '<div class="notice notice-error is-dismissible"><p>'.esc_html($error).'</p></div>';
			?>

			<form action="?page=<?php echo esc_attr($page); ?>" method="post">
				<label class="input checkbox"><input type="checkbox" name="enabled" value="1"  <?php echo esc_html($data['enabled'] ? 'checked' : ''); ?> /><span>Enable the chat on website</span></label>
				<div class="input">
					<label for="wi-hostname">Website</label>
					<input id="wi-hostname" type="text" placeholder="example.com" name="hostname" value="<?php echo esc_attr($data['hostname']); ?>" />
				</div>
				<div class="webot-domain-status-wrap<?php echo esc_html($options['hostname'] ? '' : ' hidden'); ?>">
					<div><strong>Chatbot Status:</strong> <span id="webot-domain-status" class="<?php echo esc_attr($options['status'] == 'error' ? 'negative-text' : ''); ?>"><?php echo esc_html(ucfirst($data['status']) ?: 'n/a'); ?></span></div>
					<div class="error-wrap<?php echo esc_html($options['error'] ? '' : ' hidden'); ?>"><strong>Error:</strong> <span id="webot-domain-error"><?php echo esc_html($data['error']??''); ?></span></div>
					<button type="button" class="button small" id="webot-check-domain-status" data-nonce="<?php echo esc_attr(wp_create_nonce('webot-check-status')); ?>">Check Status</button>
				</div>
				<div class="input">
					<label for="wi-primary-color">Primary Color</label>
					<input id="wi-primary-color" class="webot-color-picker" type="text" data-default-color="#28a08c" name="primary_color" value="<?php echo esc_attr($data['primary_color']); ?>" />
				</div>
				<div class="input">
					<label for="wi-placeholder">Placeholder Text</label>
					<input id="wi-placeholder" type="text" placeholder="Default: Write a message..." name="placeholder" value="<?php echo esc_attr($data['placeholder']); ?>" />
				</div>
				<div class="input">
					<label for="wi-welcome-delay">Welcome Message Delay <small>(Seconds)</small></label>
					<input id="wi-welcome-delay" type="number" class="short" placeholder="Default: 3" name="welcome_delay" value="<?php echo esc_attr($data['welcome_delay']); ?>" />
				</div>
				<p>
					Would you like to update the chatbot Name, Color, Icon, Welcome Message, and more? Please visit <a href="https://webotchatbot.com" target="_blank">Webot&nbsp;Chatbot</a> for more information.
				</p>
				<?php
				wp_nonce_field('save-webot-settings');
				// output save settings button
				submit_button( __( 'Save', 'webot-chatbot' ) );
				?>
			</form>
			<a href="https://buymeacoffee.com/webot" target="_blank"><img class="leaderboard" src="<?php echo esc_url(WEBOT_URI.'/assets/buy-me-a-coffee.jpg');  ?>" alt="Buy Me a Coffee" /></a>
		</div>

		<div class="preview">
			<img src="<?php echo esc_url(WEBOT_URI.'/assets/chat-screenshot.png'); ?>" alt="" />
		</div>
	</section>

</div>
