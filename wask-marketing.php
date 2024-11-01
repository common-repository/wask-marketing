<?php
/**
 * @package wask-marketing
 */
/*
Plugin Name: Wask Marketing
Plugin URI: http://wordpress.org/plugins/waskdeveloper/
Description: This plugin is designed to create a lookalike audience and adds Facebook pixel - Google analytic code your website.
Author: waskdeveloper
Version: 1.23
Author URI: https://wask.co
License: GNU
*/

defined('ABSPATH') or die();

if(!class_exists('wask')){

	class wask{
		function __construct(){
			$this->init();
		}

		private function init(){
			add_action('admin_menu', array($this, 'add_admin_menu'));
			add_action('admin_enqueue_scripts', array($this, 'assets'));
			add_action('wp_head', array($this, 'add_pixel'));
			add_action('wp_ajax_add_facebook_pixel', array($this, 'add_facebook_pixel'));
			add_action('wp_ajax_remove_facebook_pixel', array($this, 'remove_facebook_pixel'));
			add_action('wp_ajax_create_custom_audience', array($this, 'create_custom_audience'));
			add_action('wp_ajax_create_lookalike_audience', array($this, 'create_lookalike_audience'));
			add_action('wp_ajax_delete_audience', array($this, 'delete_audience'));

			define("facebook_api", "https://graph.facebook.com/v14.0/");
			define("wask_api", "https://api.wask.co/wordpress/");
		}

		public function activation(){}

		public function deactivation(){}

		public function add_admin_menu(){
			add_menu_page('Wask', 'Wask', 'manage_options', 'wask', array($this, 'view_create_audience'), '', null);

			add_submenu_page('wask', 'Create Audience', 'Create Audience', 'manage_options', 'wask', array($this, 'view_create_audience'));

			add_submenu_page('wask', 'Audience List', 'Audience List', 'manage_options', 'audience_list', array($this, 'view_audience_list'));

			add_submenu_page('wask', 'Facebook Pixel', 'Facebook Pixel', 'manage_options', 'facebook_pixel', array($this, 'view_facebook_pixel'));
		}

		public function view_create_audience(){
			require_once plugin_dir_path(__FILE__) . 'views/create_audience.php';
		}

		public function view_audience_list(){
			require_once plugin_dir_path(__FILE__) . 'views/audience_list.php';
		}

		public function view_facebook_pixel(){
			require_once plugin_dir_path(__FILE__) . 'views/facebook_pixel.php';
		}

		public function assets(){
			wp_enqueue_style('admincss', plugins_url('/assets/css/admin.css', __FILE__));
			wp_enqueue_style('sweetalert', plugins_url('/assets/css/sweetalert2.css', __FILE__));
			wp_enqueue_script('sweetalert', plugins_url('/assets/js/sweetalert2.js', __FILE__));
			wp_enqueue_script('facebook_countries', plugins_url('/assets/js/facebook_countries.js', __FILE__));
		}

		public function add_pixel(){
			print get_option("wask_facebook_pixel");
			print get_option("wask_google_analytic");
		}

		public function add_facebook_pixel(){
			$id = isset($_POST["id"]) ? sanitize_text_field($_POST["id"]) : "";
			$token = isset($_POST["token"]) ? sanitize_text_field($_POST["token"]) : "";

			if($id == "" || $token == "")
				wp_die();

			/* Get Pixel Code */
			$url = constant("facebook_api") . $id;
			$url .= "?fields=code";
			$url .= "&access_token=".$token;

			$response = wp_remote_get($url);
			$pixel = json_decode(wp_remote_retrieve_body($response));

			if(isset($pixel->error)){
				if(isset($pixel->error->error_user_msg)){
					$obj = (object)['error'=> (object)['message' => $pixel->error->error_user_msg]];
					echo json_encode($obj);
					wp_die();
				}
				else if(isset($pixel->error->message)){
					$obj = (object)['error'=> (object)['message' => $pixel->error->message]];
					echo json_encode($obj);
					wp_die();
				}
			}

			if(isset($pixel->code)){
				update_option("wask_facebook_pixel", $pixel->code, true);
				$obj = (object)['success'=> true];
				echo json_encode($obj);
				wp_die();
			}
			else{
				$obj = (object)['error'=> (object)['message' => 'Facebook pixel code not found']];
				echo json_encode($obj);
				wp_die();
			}
			/* Get Pixel Code */
		}

		public function remove_facebook_pixel(){
			delete_option("wask_facebook_pixel");
		}

		public function create_custom_audience(){
			$id = isset($_POST["id"]) ? sanitize_text_field($_POST["id"]) : "";
			$audience_name = isset($_POST["audience_name"]) ? sanitize_text_field($_POST["audience_name"]) : "";
			$token = isset($_POST["token"]) ? sanitize_text_field($_POST["token"]) : "";

			if($id == "" || $token == "" || $audience_name == "")
				wp_die();

			/* Check TOS Accepted */
			$url = constant("facebook_api") . $id;
			$url .= "?fields=tos_accepted";
			$url .= "&access_token=".$token;

			$response = wp_remote_get($url);
			$tos = json_decode(wp_remote_retrieve_body($response));

			if(isset($tos->error)){
				if(isset($tos->error->error_user_msg)){
					$obj = (object)['error'=> (object)['message' => $tos->error->error_user_msg]];
					echo json_encode($obj);
					wp_die();
				}
				else if(isset($tos->error->message)){
					$obj = (object)['error'=> (object)['message' => $tos->error->message]];
					echo json_encode($obj);
					wp_die();
				}
			}

			if(!isset($tos->tos_accepted->custom_audience_tos)){
				$obj = (object)['tos'=>false];
				echo json_encode($obj);
				wp_die();
			}
			/* Check TOS Accepted */


			/* STEP 1: Create Custom Audience */
			$response = wp_remote_post(constant("facebook_api") . $id . "/customaudiences", [
				"body" =>[
					"name" => "WASK_WP_".$audience_name."_".date("Y-m-d"),
					"subtype" => "CUSTOM",
					"customer_file_source" => "USER_PROVIDED_ONLY",
					"access_token" => $token
				]
			]);
			$custom_audience = json_decode(wp_remote_retrieve_body($response));

			if(isset($custom_audience->error)){
				if(isset($custom_audience->error->error_user_msg)){
					$obj = (object)['error'=> (object)['message' => $custom_audience->error->error_user_msg]];
					echo json_encode($obj);
					wp_die();
				}
				else if(isset($custom_audience->error->message)){
					$obj = (object)['error'=> (object)['message' => $custom_audience->error->message]];
					echo json_encode($obj);
					wp_die();
				}
			}

			/* STEP 1: Create Custom Audience */


			/* STEP 2: Seed Audience */ 
			$payload = (object)[
				'schema' => 'EMAIL_SHA256',
				'data' => []
			];

			global $wpdb;

			$results = $wpdb->get_results("
				SELECT 
					user_email as email
				FROM 
					".$wpdb->base_prefix."users
			");

			foreach($results as $r)
				$payload->data[] = hash("sha256", $r->email);

			$response = wp_remote_post(constant("facebook_api") . $custom_audience->id . "/users", [
				"body" =>[
					"payload" => json_encode($payload),
					"access_token" => $token
				]
			]);
			$custom_audience_users = json_decode(wp_remote_retrieve_body($response));

			if(isset($custom_audience_users->error)){
				if(isset($custom_audience_users->error->error_user_msg)){
					$obj = (object)['error'=> (object)['message' => $custom_audience_users->error->error_user_msg]];
					echo json_encode($obj);
					wp_die();
				}
				else if(isset($custom_audience_users->error->message)){
					$obj = (object)['error'=> (object)['message' => $custom_audience_users->error->message]];
					echo json_encode($obj);
					wp_die();
				}
			}
			/* STEP 2: Seed Audience */

			$obj = (object)['success' => true];
			echo json_encode($obj);
			wp_die();
		}

		public function create_lookalike_audience(){
			$audience_id = isset($_POST["audience_id"]) ? sanitize_text_field($_POST["audience_id"]) : "";
			$account_id = isset($_POST["account_id"]) ? sanitize_text_field($_POST["account_id"]) : "";
			$audience_name = isset($_POST["audience_name"]) ? sanitize_text_field($_POST["audience_name"]) : "";
			$country = isset($_POST["country"]) ? sanitize_text_field($_POST["country"]) : "";
			$ratio = isset($_POST["ratio"]) ? sanitize_text_field($_POST["ratio"]) : "";
			$token = isset($_POST["token"]) ? sanitize_text_field($_POST["token"]) : "";

			if($audience_id == "" || $account_id == "" || $audience_name == "" || $country == "" || $ratio == "" || $token == "")
				wp_die();

			/*  Create Lookalike */
			$lookalike_spec = (object)[
				"type" => "similarity",
				"ratio" => floatval($ratio),
				"location_spec" => (object)[
					"geo_locations" => (object)[
						"countries" => [$country]
					]
				]
			];

			$response = wp_remote_post(constant("facebook_api") . $account_id . "/customaudiences", [
				"body" =>[
					"name" => "WASK_WP_".$audience_name."_".date("Y-m-d"),
					"subtype" => "LOOKALIKE",
					"origin_audience_id" => $audience_id,
					"lookalike_spec"=> json_encode($lookalike_spec),
					"access_token" => $token
				]
			]);
			$lookalike_audience = json_decode(wp_remote_retrieve_body($response));

			if(isset($lookalike_audience->error)){
				if(isset($lookalike_audience->error->error_user_msg)){
					$obj = (object)['error'=> (object)['message' => $lookalike_audience->error->error_user_msg]];
					echo json_encode($obj);
					wp_die();
				}
				else if(isset($lookalike_audience->error->message)){
					$obj = (object)['error'=> (object)['message' => $lookalike_audience->error->message]];
					echo json_encode($obj);
					wp_die();
				}
			}

			$obj = (object)['success' => true];
			echo json_encode($obj);
			/*  Create Lookalike */

			wp_die();
		}

		public function delete_audience(){
			$audience_id = isset($_POST["audience_id"]) ? sanitize_text_field($_POST["audience_id"]) : "";
			$token = isset($_POST["token"]) ? sanitize_text_field($_POST["token"]) : "";

			if($audience_id == "" || $token == "")
				wp_die();

			$response = wp_remote_request(constant("facebook_api") . $audience_id, [
				"method" => "DELETE",
				"body" => [
					"access_token" => $token
				]
			]);
			$delete_audience = json_decode(wp_remote_retrieve_body($response));

			if(isset($delete_audience->error)){
				if(isset($delete_audience->error->error_user_msg)){
					$obj = (object)['error'=> (object)['message' => $delete_audience->error->error_user_msg]];
					echo json_encode($obj);
					wp_die();
				}
				else if(isset($delete_audience->error->message)){
					$obj = (object)['error'=> (object)['message' => $delete_audience->error->message]];
					echo json_encode($obj);
					wp_die();
				}
			}

			$obj = (object)['success' => true];
			echo json_encode($obj);
			/*  Create Lookalike */

			wp_die();
		}
	}

	$wask = new wask();

	register_activation_hook(__FILE__, array($wask, 'activation'));
	register_deactivation_hook(__FILE__, array($wask, 'deactivation'));
}