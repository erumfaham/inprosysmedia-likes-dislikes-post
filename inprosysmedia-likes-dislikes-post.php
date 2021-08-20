<?php  
/**
 * Plugin Name: Likes and Dislikes Plugin
 * Description: It helps to like or dislike post as well as store its value in database against user ID and post ID.
 * Author: Erum Fahma
 * Version: 1.0.0
 * License: GPL2
 * Text Domain: inprosysmedia-likes-dislikes-post
 */

/*
	Copyright (C) 2021  erumfaham@gmail.com

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * Snippet Wordpress Plugin Boilerplate based on:
 *
 * - https://github.com/purplefish32/sublime-text-2-wordpress/blob/master/Snippets/Plugin_Head.sublime-snippet
 * - http://wordpress.stackexchange.com/questions/25910/uninstall-activate-deactivate-a-plugin-typical-features-how-to/25979#25979
 *
 * By default the option to uninstall the plugin is disabled,
 * to use uncomment or remove if not used.
 *
 * This Template does not have the necessary code for use in multisite.
 *
 * Also delete this comment block is unnecessary once you have read.
 *
 * Version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;
define('INPROSYS_PLUGIN_PATH', plugin_dir_path( __FILE__ ));
define('INPROSYS_PLUGIN_URL',plugin_dir_url( __FILE__ ));
define('INPROSYS_TABLE_NAME','inprosys_ef_likes_dislikes');

class inprosys_likes_dislikes {

	private static $instance = null;

	/**
	 * Creates or returns an instance of this class.
	 * @since  1.0.0
	 * @return inprosys_likes_dislikes A single instance of this class.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	private function __construct(){
			add_filter( 'the_content',array($this,'inprosys_add_likes_dislikes'),1);
			add_action( 'wp_enqueue_scripts', array($this,'inprosys_enqueue_scripts' ));
			add_action('wp_ajax_my_likes_dislikes_action',array($this,'inprosys_do_ajax_action'));
			add_action('wp_ajax_nopriv_my_likes_dislikes_action',array($this,'inprosys_do_ajax_action'));
			


		}

		public function inprosys_do_ajax_action(){
			if(isset($_POST['action'])):
				global $wpdb;
				$state = sanitize_text_field($_POST['state']) ;
				$post_id = sanitize_text_field($_POST['post']);
				$table = $wpdb->prefix.INPROSYS_TABLE_NAME;
				$user_id = get_current_user_id();
				$row = $wpdb->get_row("SELECT * FROM `{$table}` WHERE `post_id` = {$post_id} AND `user_id`= {$user_id}", ARRAY_A);
				if($row == null){
					$wpdb->insert($table, [
						'user_id' => $user_id,
						'post_id' => $post_id,
						$state => 1,
					]);
				}elseif($row['user_id'] == $user_id && $row[$state] == 0){
					$wpdb->delete($table, [
						'post_id' => $post_id,
						'user_id' => $user_id
					]);
					$wpdb->insert($table, [
						'user_id' => $user_id,
						'post_id' => $post_id,
						$state => 1,
					]);
				}else{
					$wpdb->delete($table,[
						'post_id' => $post_id,
						'user_id' => $user_id
 					]);
				}
			endif;

			$likes = $wpdb->get_row("SELECT COUNT(*)as likes FROM `{$table}` WHERE `post_id` = {$post_id} AND `like` > 0",ARRAY_A);
			$dislikes = $wpdb->get_row("SELECT COUNT(*) as dislikes FROM `{$table}` WHERE `post_id` = {$post_id} AND `dislike` > 0",ARRAY_A);
			echo json_encode(array(
				'like' => $likes['likes'],
				'dislike' => $dislikes['dislikes']
			));
			wp_die();
		}
		

		function inprosys_enqueue_scripts() {
			global $post;
			wp_enqueue_style( 'like-dislike-style',INPROSYS_PLUGIN_URL.'css/app.css');
			wp_enqueue_script( 'like-dislike-script',INPROSYS_PLUGIN_URL.'js/app.js',array('jquery'),'1.0',true);	
			wp_localize_script( 'like-dislike-script', 'ajax_object', array('url'=> admin_url('admin-ajax.php'),'post'=> $post->ID));

			

		}
		
		

		
		

		public function inprosys_add_likes_dislikes($content){

			
    		global $post;
			if(is_user_logged_in() && 'post' == $post->post_type){

				

				global $wpdb;
				global $post;
				$table = $wpdb->prefix.INPROSYS_TABLE_NAME;
				$post_id = $post->ID;

				$likes = $wpdb->get_row("SELECT COUNT(*)as likes FROM `{$table}` WHERE `post_id` = {$post_id} AND `like` > 0",ARRAY_A);
				$dislikes = $wpdb->get_row("SELECT COUNT(*)as dislikes FROM `{$table}` WHERE `post_id` = {$post_id} AND `dislike` > 0",ARRAY_A);

				$description ="
					<ul class='likes-dislikes' id='likes-dislikes'>
						<li class='first'><a data-val='like' href='javascript:;'>Like </a><span>[".$likes['likes']."]</span></li>
						<li class='second'><a data-val='dislike' href='javascript:;'>Dislike </a><span>[".$dislikes['dislikes']."]</span></li>

					</ul>

				";
				
				return $content.$description;
    			}
    		


		 return $content;
		}




	public static function activate() {

		if(!current_user_can( 'activate_plugins' ))
			return;
		global $wpdb;
		$table = $wpdb->prefix.INPROSYS_TABLE_NAME;
		$collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE IF NOT EXISTS `{$table}`(
				`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				`user_id` int(11) DEFAULT NULL,
				`post_id` int(11) NOT NULL,
				`like` int(11) DEFAULT 0,
				`dislike` int(11) DEFAULT 0,
				`data_created` timestamp DEFAULT NOW(),
				PRIMARY KEY (`id`)
		) {$collate};";
		require_once(ABSPATH.'wp-admin/includes/upgrade.php');
		dbDelta( $sql);

		//$plugin = isset($_REQUEST['plugin']) ? $_REQUEST['plugin']:'';
		//check_admin_referer( "$activate-plugin_{$plugin}");
		
	}

	

	public static function deactivate() {
		}



	public static function uninstall() {
		
			if(!current_user_can( 'activate_plugins' ))
			return;
		global $wpdb;
		$table = $wpdb->prefix.INPROSYS_TABLE_NAME;
		$sql = "DROP TABLE `{$table}`";
		$wpdb->query($sql);

		//check_admin_referer( 'bluk-plugins');

		if ( __FILE__ != WP_UNINSTALL_PLUGIN )
			return;


	}

}

add_action( 'plugins_loaded', array( 'inprosys_likes_dislikes', 'get_instance' ) );
register_activation_hook( __FILE__, array( 'inprosys_likes_dislikes', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'inprosys_likes_dislikes', 'deactivate' ) );
register_uninstall_hook( __FILE__, array( 'inprosys_likes_dislikes', 'uninstall' ) );


 ?>