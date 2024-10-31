<?php
	
	/**
	 * Plugin Name: Push Anything To Social
	 * Version: 1.0
	 * Description: Push Any notification to Facebook messenger, Telegram, Whatsapp using Callmebot API
	 * Author: thienduc0105
	 * Author URI: https://phongmy.vn/
	 * Plugin URI: https://phongmy.vn
	 * Text Domain: phongmy.vn
	 **/
	
	defined('ABSPATH') or die('OOPS...');
	if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))
	) {
		if (!class_exists('Class_Pmfacebookbot')) {
			class Class_Pmfacebookbot
			{
				protected static $instance;
				public $_optionName = 'pmfacebook_options';
				public $_version = '1.0';
				public $_defaultOptions = array(
					'check_version' => '',
					'tokenbot' => '',
					'usertoken' => '',
					'mess_content' => '',
					'account_creat_mess' => '',
					'account_creat' => '',
					'mess_content_list' => array(),
					'enable_woo' => '',
					'order_creat' => '',
					'order_creat_mess' => '',
					'woo_status_complete' => '',
					'woo_status_complete_mess' => '',
					'woo_status_cancelled' => '',
					'woo_status_cancelled_mess' => '',
					'woo_status_refunded' => '',
					'woo_status_refunded_mess' => ''
				);
				public $_optionGroup = 'pmfacebook-options-group';
				
				public static function init()
				{
					is_null(self::$instance) AND self::$instance = new self;
					return self::$instance;
				}
				
				public function __construct()
				{
					$this->define_constants();
					global $pmfacebookbot_settings;
					$pmfacebookbot_settings = $this->get_options();
					
					add_action('admin_menu', array($this, 'admin_menu'));
					add_action('admin_init', array($this, 'pm_register_adminsetting'));
					add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
					
					if ($pmfacebookbot_settings['enable_woo']) {
						if (($pmfacebookbot_settings['order_creat'] && $pmfacebookbot_settings['order_creat_mess'])) {
							add_action('woocommerce_thankyou', array($this, 'pmfacebook_newOrder'), 10, 1);
						}
						if (
						(
							($pmfacebookbot_settings['woo_status_complete'] && $pmfacebookbot_settings['woo_status_complete_mess']) ||
							($pmfacebookbot_settings['woo_status_cancelled'] && $pmfacebookbot_settings['woo_status_cancelled_mess']) ||
							($pmfacebookbot_settings['woo_status_refunded'] && $pmfacebookbot_settings['woo_status_refunded_mess'])
						)
						) {
							add_action('woocommerce_order_status_changed', array($this, 'pmfacebook_order_status_changed'), 10, 3);
						}
					}
				}
				
				public function define_constants()
				{
					if (!defined('PM_FB_VERSION_NUM'))
						define('PM_FB_VERSION_NUM', $this->_version);
					if (!defined('PM_FB_URL'))
						define('PM_FB_URL', plugin_dir_url(__FILE__));
					if (!defined('PM_FB_BASENAME'))
						define('PM_FB_BASENAME', plugin_basename(__FILE__));
					if (!defined('PM_FB_PLUGIN_DIR'))
						define('PM_FB_PLUGIN_DIR', plugin_dir_path(__FILE__));
				}
				
				function get_options()
				{
					return wp_parse_args(get_option($this->_optionName), $this->_defaultOptions);
				}
				
				function admin_menu()
				{
					add_options_page(
						__('Anything to Social', 'phongmy.vn'),
						__('Anything to Social', 'phongmy.vn'),
						'manage_options',
						'pm-facebook-noti-option',
						array($this, 'pm_facebook_setting_page')
					);
				}
				
				function pm_register_adminsetting()
				{
					register_setting($this->_optionGroup, $this->_optionName);
				}
				
				function pm_facebook_setting_page()
				{
					global $pmfacebookbot_settings;
					?>
                    <div class="wrap">
                        <h1>Push Woocommerce Orders to Facebook Messenger - Phongmy.vn</h1>
                        <form method="post" action="options.php" novalidate="novalidate">
							<?php settings_fields($this->_optionGroup); ?>

                            <div class="">
                                <h2>Callmebot API Setting</h2>
								<?php require 'donate.php';?>
                            </div>
                            <div class="type_api_table">
                                <table class="form-table">
                                    <tbody>
                                    <tr>
                                        <th scope="row"><label
                                                    for="tokenbot"><?php _e('Admin Callmebot API', 'phongmy.vn') ?></label>
                                        </th>
                                        <td>
                                            <input type="text" name="<?php echo esc_html($this->_optionName); ?>[tokenbot]"
                                                   id="tokenbot"
                                                   value="<?php echo esc_html($pmfacebookbot_settings['tokenbot']); ?>"/>
                                        </td>
										<br/>
                                        <p><strong>Create new API:</strong> Chat with BOT - <a href="https://m.me/api.callmebot" target="_blank">https://m.me/api.callmebot</a>
                                            <br/>
                                            Type <b>create apikey</b> and copy key insert to input</p>
                                    </tr>

                                    <tr>
                                        <th scope="row"><label
                                                    for="usertoken"><?php _e('Technical API Backup', 'phongmy.vn') ?></label>
                                        </th>
                                        <td>
                                            <input type="text" name="<?php echo esc_html($this->_optionName); ?>[usertoken]"
                                                   id="usertoken"
                                                   value="<?php echo esc_html($pmfacebookbot_settings['usertoken']); ?>"/>
                                        </td>
                                    </tr>

                                    </tbody>
                                </table>
                            </div>

                            <table class="form-table">
                                <tbody>
                                <tr>
                                    <th scope="row"><label
                                                for="enable_woo"><?php _e('Active', 'phongmy.vn') ?></label>
                                    </th>
                                    <td>
                                        <input type="checkbox" name="<?php echo esc_html($this->_optionName); ?>[enable_woo]"
                                               id="enable_woo"
                                               value="1" <?php checked('1', intval($pmfacebookbot_settings['enable_woo']), true); ?>/>
                                        Allow plugin send orders to facebook
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Send Content', 'phongmy.vn') ?></th>
                                    <td>
                                        <table class="woo_setting_mess">
                                            <tbody>
                                            <tr>
                                                <td>
                                                    <label><input type="checkbox"
                                                                  name="<?php echo esc_html($this->_optionName); ?>[order_creat]"
                                                                  id="order_creat"
                                                                  value="1" <?php checked('1', intval($pmfacebookbot_settings['order_creat']), true); ?>/>
                                                        Notify when has a new order</label><br>
                                                    <textarea placeholder="Main content"
                                                              name="<?php echo esc_html($this->_optionName); ?>[order_creat_mess]"><?php echo sanitize_textarea_field($pmfacebookbot_settings['order_creat_mess']) ?></textarea>
                                                </td>
                                                <td>
                                                    <label><input type="checkbox"
                                                                  name="<?php echo esc_html($this->_optionName); ?>[woo_status_complete]"
                                                                  id="woo_status_complete"
                                                                  value="1" <?php checked('1', intval($pmfacebookbot_settings['woo_status_complete']), true);
														?>/> Gotify when has a completed order.</label><br>
                                                    <textarea placeholder="Your content"
                                                              name="<?php echo esc_html($this->_optionName); ?>[woo_status_complete_mess]"><?php echo sanitize_textarea_field($pmfacebookbot_settings['woo_status_complete_mess']) ?></textarea>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <label><input type="checkbox"
                                                                  name="<?php echo $this->_optionName ?>[woo_status_cancelled]"
                                                                  id="woo_status_cancelled"
                                                                  value="1" <?php checked('1', intval($pmfacebookbot_settings['woo_status_cancelled']), true); ?>/>
                                                        Notify when has a cancel order</label><br>
                                                    <textarea placeholder="Main content"
                                                              name="<?php echo $this->_optionName ?>[woo_status_cancelled_mess]"><?php echo sanitize_textarea_field($pmfacebookbot_settings['woo_status_cancelled_mess']) ?></textarea>
                                                </td>
                                                <td>
                                                    <label><input type="checkbox"
                                                                  name="<?php echo $this->_optionName ?>[woo_status_refunded]"
                                                                  id="woo_status_refunded"
                                                                  value="1" <?php checked('1', intval($pmfacebookbot_settings['woo_status_refunded']), true); ?>/>
                                                        Notify when has a refund order</label><br>
                                                    <textarea placeholder="Main content"
                                                              name="<?php echo $this->_optionName ?>[woo_status_refunded_mess]"><?php echo sanitize_textarea_field($pmfacebookbot_settings['woo_status_refunded_mess']) ?></textarea>
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                        <div class="desc_wctelegrambot_bot">
                                            <p>You can use the codes below</p>
                                            <br/>
                                            Your order ID: <span style="color: blue;">#%%order_id%%</span><br>
                                            Products name: <span style="color: red;">%%product_name%%</span><br>
                                            First name: <span style="color: blue;">%%first_name%%</span><br>
                                            Last name: <span style="color: red;">%%last_name%%</span><br>
                                            Customer email: <span style="color: blue;">%%billing_email%%</span><br>
                                            Phone number: <span style="color: red;">%%billing_phone%%</span><br>
                                            Address: <span style="color: blue;">%%billing_address%%</span><br>
                                            Payment methods: <span style="color: red;">%%payment_method%%</span><br>
                                            Delivery method: <span style="color: blue;">%%shipping_method%%</span><br>
                                            New Order date: <span style="color: red;">%%created_date%%</span><br>
                                            Completed Order date: <span
                                                    style="color: blue;">%%completed_date%%</span><br>
                                            Customer note: <span style="color: red;">%%customer_note%%</span><br>
                                            Total money: <span style="color: blue;">%%total%%</span>
                                        </div>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
							
							
							<?php 
								do_settings_fields('pmfacebook-options-group', 'default'); ?>
							<?php do_settings_sections('pmfacebook-options-group', 'default'); ?>
							<?php submit_button(); ?>
                        </form>
                    </div>
					<?php
				}
				
				/*Start Woocommerce*/
				function pmfacebook_strs_replace($pmfacebook_message = '', $order = '', $order_id = '')
				{
					global $pmfacebookbot_settings;
					if (!$pmfacebook_message || !$order) return $pmfacebook_message;
					if (!$order_id) $order_id = $order->get_id();
					$order = wc_get_order($order_id);
					$items = $order->get_items();
					$productname = [];
					foreach ($items as $item) {
						$product = wc_get_product($item['product_id']);
						$soluongsanpham = $item['quantity'];
						$productname[] = $product->get_name() . ' × (' . $soluongsanpham . ')';
					}
					$productname = implode(', ', $productname);
					$billing_first_name = $order->get_billing_first_name();
					$billing_last_name = $order->get_billing_last_name();
					$billing_phone = $order->get_billing_phone();
					$billing_email = $order->get_billing_email();
					$billing_address = $order->get_billing_address_1();
					$payment_method = $order->get_payment_method_title();
					$shipping_method = $order->get_shipping_method();
					$customer_note = $order->get_customer_note();
					@$date_created = wp_date('d/m/Y - H:i', strtotime($order->get_date_created()));
					@$date_completed = wp_date('d/m/Y - H:i', strtotime($order->get_date_completed()));
					$total = $order->get_total();
					$formattedNum = number_format($total, 0, ',', '.');
					
					$str_replace['first_name'] = $billing_first_name;
					$str_replace['last_name'] = $billing_last_name;
					$str_replace['total'] = $formattedNum;
					$str_replace['billing_phone'] = $billing_phone;
					$str_replace['billing_email'] = $billing_email;
					$str_replace['order_id'] = $order_id;
					$str_replace['billing_address'] = $billing_address;
					$str_replace['product_name'] = $productname;
					$str_replace['payment_method'] = $payment_method;
					$str_replace['shipping_method'] = $shipping_method;
					$str_replace['customer_note'] = $customer_note;
					$str_replace['created_date'] = $date_created;
					$str_replace['completed_date'] = $date_completed;
					
					preg_match_all('/%%(\w*)\%%/', $pmfacebook_message, $matches);
					foreach ($matches[1] as $m) {
						$pattern = "/%%" . $m . "%%/";
						$pmfacebook_message = preg_replace($pattern, $str_replace[$m], $pmfacebook_message);
					}
					return $pmfacebook_message;
				}
				
				function pmfacebook_newOrder($order_id)
				{
					if (!get_post_meta($order_id, '_thankyou_action_done', true)) {
						global $pmfacebookbot_settings;
						$order = wc_get_order($order_id);
						$order_creat = $pmfacebookbot_settings['order_creat'];
						if ($order_creat && $pmfacebookbot_settings['order_creat_mess']) {
							$order_creat_mess = $this->pmfacebook_strs_replace($pmfacebookbot_settings['order_creat_mess'], $order);
							$this->send_callmebot_api($order_creat_mess);
						}
						$order->update_meta_data('_thankyou_action_done', true);
						$order->save();
					}
				}
				
				function pmfacebook_order_status_changed($order_id, $tatus_from, $status_to)
				{
					global $pmfacebookbot_settings;
					$order = wc_get_order($order_id);
					if ($order):
						switch ($status_to):
							case 'completed':
								if ($pmfacebookbot_settings['woo_status_complete'] && $pmfacebookbot_settings['woo_status_complete_mess']) {
									$order_creat_mess = $this->pmfacebook_strs_replace($pmfacebookbot_settings['woo_status_complete_mess'], $order);
									$this->send_callmebot_api($order_creat_mess);
								}
								break;
							case 'cancelled':
								if ($pmfacebookbot_settings['woo_status_cancelled'] && $pmfacebookbot_settings['woo_status_cancelled_mess']) {
									$order_creat_mess = $this->pmfacebook_strs_replace($pmfacebookbot_settings['woo_status_cancelled_mess'], $order);
									$this->send_callmebot_api($order_creat_mess);
								}
								break;
							case 'refunded':
								if ($pmfacebookbot_settings['woo_status_refunded'] && $pmfacebookbot_settings['woo_status_refunded_mess']) {
									$order_creat_mess = $this->pmfacebook_strs_replace($pmfacebookbot_settings['woo_status_refunded_mess'], $order);
									$this->send_callmebot_api($order_creat_mess);
								}
								break;
						endswitch;
					endif;
				}
				
				private function send_callmebot_api($Content = '')
				{
					global $pmfacebookbot_settings;
					$Token = $pmfacebookbot_settings['tokenbot'];
					$Usertoken = $pmfacebookbot_settings['usertoken'];
					
					//send to admin site
					if (!$Content || !$Token) return false;
					$SendContent = urlencode($Content);
					$data = "https://api.callmebot.com/facebook/send.php?apikey=$Token&text=$SendContent&disable_web_page_preview=true";
					wp_remote_request($data);
					
					//send to Technical backup
					if (!$Usertoken) return false;
					$data1 = "https://api.callmebot.com/facebook/send.php?apikey=$Usertoken&text=$SendContent&disable_web_page_preview=true";
					wp_remote_request($data1);
				}
				
				public function admin_scripts()
				{
					$current_screen = get_current_screen();
					if (isset($current_screen->base) && $current_screen->base == 'settings_page_pm-facebook-noti-option') {
						wp_enqueue_style('pm-admin-css', plugins_url('/assets/css/admin.css', __FILE__), array(), $this->_version, 'all');
					}
				}
			}
			
			new Class_Pmfacebookbot();
		}
	}


