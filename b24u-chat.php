<?php
/*
Plugin Name: B24U Chat Widget
Plugin URI: https://b24u.com
Description: AI-powered chat widget. Installs in one click — all settings managed remotely.
Version: 1.0.0
Author: B24U
Author URI: https://b24u.com
License: GPL v2 or later
Text Domain: b24u-chat
*/

if (!defined('ABSPATH')) exit;

// Default settings on activation
register_activation_hook(__FILE__, 'b24u_activate');
function b24u_activate() {
	$domain = parse_url(get_site_url(), PHP_URL_HOST);
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
		update_option('b24u_domain', sanitize_text_field($_POST['domain']));
		update_option('b24u_mode', sanitize_text_field($_POST['mode']));
		
		// Local overrides
		if ($_POST['mode'] === 'local') {
			$overrides = array_filter([
				'popupText' => sanitize_text_field($_POST['popup_text'] ?? ''),
				'popupPhoto' => esc_url_raw($_POST['popup_photo'] ?? ''),
				'chatBottom' => intval($_POST['chat_bottom'] ?? 0) ?: null,
				'chatRight' => intval($_POST['chat_right'] ?? 0) ?: null,
			]);
			update_option('b24u_overrides', $overrides);
		} else {
			delete_option('b24u_overrides');
		}
		
		echo '<div class="notice notice-success"><p><strong>Settings saved.</strong></p></div>';
	}
	
	$enabled = get_option('b24u_enabled', true);
	$domain = get_option('b24u_domain', parse_url(get_site_url(), PHP_URL_HOST));
	$mode = get_option('b24u_mode', 'remote');
	$overrides = get_option('b24u_overrides', []);
	?>
	
	<div class="wrap">
		<h1>B24U Chat Widget</h1>
		
		<?php if ($enabled): ?>
			<div class="notice notice-info">
				<p><strong>Widget is active</strong> on domain: <code><?php echo esc_html($domain); ?></code></p>
			</div>
		<?php endif; ?>
		
		<form method="post">
			<?php wp_nonce_field('b24u_settings'); ?>
			
			<table class="form-table">
				<tr>
					<th>Enable Widget</th>
					<td>
						<label>
							<input type="checkbox" name="enabled" value="1" <?php checked($enabled); ?>>
							Show chat widget on website
						</label>
					</td>
				</tr>
				
				<tr>
					<th><label for="domain">Domain</label></th>
					<td>
						<input type="text" name="domain" id="domain" 
							   value="<?php echo esc_attr($domain); ?>" 
							   class="regular-text" required>
						<p class="description">Domain registered in B24U system (auto-detected)</p>
					</td>
				</tr>
				
				<tr>
					<th>Configuration</th>
					<td>
						<fieldset>
							<label>
								<input type="radio" name="mode" value="remote" 
									   <?php checked($mode, 'remote'); ?>>
								<strong>Remote</strong> — All settings managed on B24U server
							</label>
							<p class="description" style="margin: 5px 0 15px 25px;">
								Recommended. Change widget appearance, text, behavior in B24U admin panel.
							</p>
							
							<label>
								<input type="radio" name="mode" value="local" 
									   <?php checked($mode, 'local'); ?>>
								<strong>Local overrides</strong> — Override specific settings
							</label>
						</fieldset>
					</td>
				</tr>
			</table>
			
			<div id="local-overrides" style="<?php echo $mode === 'remote' ? 'display:none' : ''; ?>">
				<hr>
				<h2>Local Overrides</h2>
				<p class="description">Override server settings for this site only. Leave empty to use defaults.</p>
				
				<table class="form-table">
					<tr>
						<th><label for="popup_text">Popup Text</label></th>
						<td>
							<input type="text" name="popup_text" id="popup_text" 
								   value="<?php echo esc_attr($overrides['popupText'] ?? ''); ?>" 
								   class="large-text"
								   placeholder="Leave empty for server default">
						</td>
					</tr>
					
					<tr>
						<th><label for="popup_photo">Popup Photo URL</label></th>
						<td>
							<input type="url" name="popup_photo" id="popup_photo" 
								   value="<?php echo esc_attr($overrides['popupPhoto'] ?? ''); ?>" 
								   class="large-text"
								   placeholder="https://example.com/avatar.jpg">
						</td>
					</tr>
					
					<tr>
						<th>Button Position</th>
						<td>
							<label>
								Bottom: 
								<input type="number" name="chat_bottom" 
									   value="<?php echo esc_attr($overrides['chatBottom'] ?? ''); ?>" 
									   min="10" max="150" class="small-text"> px
							</label>
							&nbsp;&nbsp;
							<label>
								Right: 
								<input type="number" name="chat_right" 
									   value="<?php echo esc_attr($overrides['chatRight'] ?? ''); ?>" 
									   min="10" max="150" class="small-text"> px
							</label>
						</td>
					</tr>
				</table>
			</div>
			
			<p class="submit">
				<input type="submit" name="b24u_save" class="button-primary" value="Save Settings">
			</p>
		</form>
		
		<hr>
		<h3>Need help?</h3>
		<p>Manage all widget settings in <a href="https://my.b24u.com/b" target="_blank">B24U Dashboard</a></p>
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

// Frontend output
add_action('wp_footer', 'b24u_render_widget', 999);
function b24u_render_widget() {
	if (!get_option('b24u_enabled', true)) return;
	
	$domain = get_option('b24u_domain');
	if (empty($domain)) return;
	
	$mode = get_option('b24u_mode', 'remote');
	$safe_domain = esc_attr($domain);
	
	// Local overrides
	if ($mode === 'local') {
		$overrides = get_option('b24u_overrides', []);
		if (!empty($overrides)) {
			echo '<script>window.B24UConfig=' . wp_json_encode($overrides) . ';</script>' . "\n";
		}
	}
	
	// Widget script
	echo '<script src="https://i.b24u.com/' . $safe_domain . '" defer onload="if(window.B24U){B24U.init(window.B24UConfig);}"></script>' . "\n";
} 
