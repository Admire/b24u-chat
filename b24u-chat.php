<?php
/*
Plugin Name: B24U Chat Widget
Plugin URI: https://github.com/Admire/b24u-chat
Description: AI-powered chat widget with remote configuration
Version: 1.0.2
Author: B24U
Author URI: https://b24u.com
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: b24u-chat-widget
*/

if (!defined('ABSPATH')) exit;

// Default settings on activation
register_activation_hook(__FILE__, 'b24u_activate');
function b24u_activate() {
	$parsed_url = wp_parse_url(get_site_url());
	$domain = isset($parsed_url['host']) ? $parsed_url['host'] : '';
	add_option('b24u_domain', $domain);
	add_option('b24u_mode', 'remote');
	add_option('b24u_enabled', true);
}

// Admin menu
add_action('admin_menu', 'b24u_add_menu');
function b24u_add_menu() {
	add_options_page(
		'B24U Chat',
		'B24U Chat',
		'manage_options',
		'b24u-chat',
		'b24u_settings_page'
	);
}

// Settings page
function b24u_settings_page() {
	if (isset($_POST['b24u_save'])) {
		check_admin_referer('b24u_settings');
		
		update_option('b24u_enabled', isset($_POST['enabled']));
		
		if (isset($_POST['domain'])) {
			update_option('b24u_domain', sanitize_text_field(wp_unslash($_POST['domain'])));
		}
		
		if (isset($_POST['mode'])) {
			update_option('b24u_mode', sanitize_text_field(wp_unslash($_POST['mode'])));
		}
		
		// Local overrides
		if (isset($_POST['mode']) && $_POST['mode'] === 'local') {
			$overrides = array_filter([
				'popupText' => isset($_POST['popup_text']) ? sanitize_text_field(wp_unslash($_POST['popup_text'])) : '',
				'popupPhoto' => isset($_POST['popup_photo']) ? esc_url_raw(wp_unslash($_POST['popup_photo'])) : '',
				'chatBottom' => isset($_POST['chat_bottom']) ? intval($_POST['chat_bottom']) : 0,
				'chatRight' => isset($_POST['chat_right']) ? intval($_POST['chat_right']) : 0,
			]);
			update_option('b24u_overrides', $overrides);
		} else {
			delete_option('b24u_overrides');
		}
		
		echo '<div class="notice notice-success"><p><strong>Settings saved.</strong></p></div>';
	}
	
	$enabled = get_option('b24u_enabled', true);
	$parsed_url = wp_parse_url(get_site_url());
	$default_domain = isset($parsed_url['host']) ? $parsed_url['host'] : '';
	$domain = get_option('b24u_domain', $default_domain);
	$mode = get_option('b24u_mode', 'remote');
	$overrides = get_option('b24u_overrides', []);
	?>
	
	<div class="wrap">
		<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
		
		<?php if ($enabled): ?>
			<div class="notice notice-info">
				<p><strong>Widget is active</strong> on domain: <code><?php echo esc_html($domain); ?></code></p>
			</div>
		<?php endif; ?>
		
		<form method="post">
			<?php wp_nonce_field('b24u_settings'); ?>
			
			<table class="form-table">
				<tr>
					<th><?php esc_html_e('Enable Widget', 'b24u-chat-widget'); ?></th>
					<td>
						<label>
							<input type="checkbox" name="enabled" value="1" <?php checked($enabled); ?>>
							<?php esc_html_e('Show chat widget on website', 'b24u-chat-widget'); ?>
						</label>
					</td>
				</tr>
				
				<tr>
					<th><label for="domain"><?php esc_html_e('Domain', 'b24u-chat-widget'); ?></label></th>
					<td>
						<input type="text" name="domain" id="domain" 
							   value="<?php echo esc_attr($domain); ?>" 
							   class="regular-text" required>
						<p class="description"><?php esc_html_e('Domain registered in B24U system (auto-detected)', 'b24u-chat-widget'); ?></p>
					</td>
				</tr>
				
				<tr>
					<th><?php esc_html_e('Configuration', 'b24u-chat-widget'); ?></th>
					<td>
						<fieldset>
							<label>
								<input type="radio" name="mode" value="remote" 
									   <?php checked($mode, 'remote'); ?>>
								<strong><?php esc_html_e('Remote', 'b24u-chat-widget'); ?></strong> — <?php esc_html_e('All settings managed on B24U server', 'b24u-chat-widget'); ?>
							</label>
							<p class="description" style="margin: 5px 0 15px 25px;">
								<?php esc_html_e('Recommended. Change widget appearance, text, behavior in B24U admin panel.', 'b24u-chat-widget'); ?>
							</p>
							
							<label>
								<input type="radio" name="mode" value="local" 
									   <?php checked($mode, 'local'); ?>>
								<strong><?php esc_html_e('Local overrides', 'b24u-chat-widget'); ?></strong> — <?php esc_html_e('Override specific settings', 'b24u-chat-widget'); ?>
							</label>
						</fieldset>
					</td>
				</tr>
			</table>
			
			<div id="local-overrides" style="<?php echo $mode === 'remote' ? 'display:none' : ''; ?>">
				<hr>
				<h2><?php esc_html_e('Local Overrides', 'b24u-chat-widget'); ?></h2>
				<p class="description"><?php esc_html_e('Override server settings for this site only. Leave empty to use defaults.', 'b24u-chat-widget'); ?></p>
				
				<table class="form-table">
					<tr>
						<th><label for="popup_text"><?php esc_html_e('Popup Text', 'b24u-chat-widget'); ?></label></th>
						<td>
							<input type="text" name="popup_text" id="popup_text" 
								   value="<?php echo esc_attr($overrides['popupText'] ?? ''); ?>" 
								   class="large-text"
								   placeholder="<?php esc_attr_e('Leave empty for server default', 'b24u-chat-widget'); ?>">
						</td>
					</tr>
					
					<tr>
						<th><label for="popup_photo"><?php esc_html_e('Popup Photo URL', 'b24u-chat-widget'); ?></label></th>
						<td>
							<input type="url" name="popup_photo" id="popup_photo" 
								   value="<?php echo esc_attr($overrides['popupPhoto'] ?? ''); ?>" 
								   class="large-text"
								   placeholder="https://example.com/avatar.jpg">
						</td>
					</tr>
					
					<tr>
						<th><?php esc_html_e('Button Position', 'b24u-chat-widget'); ?></th>
						<td>
							<label>
								<?php esc_html_e('Bottom:', 'b24u-chat-widget'); ?> 
								<input type="number" name="chat_bottom" 
									   value="<?php echo esc_attr($overrides['chatBottom'] ?? ''); ?>" 
									   min="10" max="150" class="small-text"> px
							</label>
							&nbsp;&nbsp;
							<label>
								<?php esc_html_e('Right:', 'b24u-chat-widget'); ?> 
								<input type="number" name="chat_right" 
									   value="<?php echo esc_attr($overrides['chatRight'] ?? ''); ?>" 
									   min="10" max="150" class="small-text"> px
							</label>
						</td>
					</tr>
				</table>
			</div>
			
			<p class="submit">
				<input type="submit" name="b24u_save" class="button-primary" value="<?php esc_attr_e('Save Settings', 'b24u-chat-widget'); ?>">
			</p>
		</form>
		
		<hr>
		<h3><?php esc_html_e('Need help?', 'b24u-chat-widget'); ?></h3>
		<p><?php esc_html_e('Manage all widget settings in', 'b24u-chat-widget'); ?> <a href="https://b24u.com/dashboard" target="_blank">B24U Dashboard</a></p>
	</div>
	
	<script>
	jQuery(function($) {
		$('[name="mode"]').change(function() {
			$('#local-overrides').toggle($(this).val() === 'local');
		});
	});
	</script>
	<?php
}

// Admin script
add_action('admin_enqueue_scripts', 'b24u_admin_scripts');
function b24u_admin_scripts($hook) {
	if ($hook !== 'settings_page_b24u-chat') {
		return;
	}
	wp_enqueue_script('jquery');
}

// Frontend output
add_action('wp_footer', 'b24u_render_widget', 999);
function b24u_render_widget() {
	if (!get_option('b24u_enabled', true)) {
		return;
	}
	
	$domain = get_option('b24u_domain');
	if (empty($domain)) {
		return;
	}
	
	$mode = get_option('b24u_mode', 'remote');
	
	// Local overrides
	if ($mode === 'local') {
		$overrides = get_option('b24u_overrides', []);
		if (!empty($overrides)) {
			wp_add_inline_script(
				'b24u-widget-config',
				'window.B24UConfig=' . wp_json_encode($overrides) . ';',
				'before'
			);
		}
	}
	
	// Enqueue widget script
	wp_enqueue_script(
		'b24u-widget',
		'https://i.b24u.com/' . esc_attr($domain),
		[],
		'1.0.0',
		true
	);
	
	wp_add_inline_script(
		'b24u-widget',
		'if(window.B24U){B24U.init(window.B24UConfig);}'
	);
}

// Register inline script handle
add_action('wp_enqueue_scripts', 'b24u_register_scripts');
function b24u_register_scripts() {
	wp_register_script('b24u-widget-config', '', [], '1.0.0', true);
	wp_enqueue_script('b24u-widget-config');
}

// Register inline script handle
add_action('wp_enqueue_scripts', 'b24u_register_scripts');
function b24u_register_scripts() {
	wp_register_script('b24u-widget-config', '', [], null, true);
	wp_enqueue_script('b24u-widget-config');
}