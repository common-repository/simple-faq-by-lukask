<?php
/*
Plugin Name: Simple FAQ by LukasK
Plugin URI: https://wordpress.org/plugins/simple-faq-by-lukask/
Description: Simple plugin for FAQ (Q&A). Allows  you to define HTML skeleton and adds FAQ post-like section to admin panel. You can add question and answer using WordPress admin panel. You can display FAQ using shortcode [lukask_faq].
Version: 1.0
Author: Łukasz Kirylak
Author URI: http://lukask.pl/
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: lukask-faq
Domain Path: /languages
*/

defined( 'ABSPATH' ) or die( "I like bread. Who don't?" );

class lukask_faq_class {
	
	public static $options = array();
	public static $options_defaults = array();
	//public static class_name = 'lukask_faq_class';
	
	public static function INIT()
	{		
		//Load text domain
		add_action( 'plugins_loaded', array( get_called_class(), 'loadTextDomain' ) );
		
		//Register custom post type
		add_action( 'init', array( get_called_class(), 'registerPostType' ) );
		
		//Register all plugin settings
		add_action( 'admin_init', array( get_called_class(), 'initSettings' ) );
		
		//Add admin page
		add_action( 'admin_menu', array( get_called_class() , 'addAdminPage' ) );
		
		//Register shortcode to render FAQ
		add_shortcode( 'lukask_faq', array(get_called_class(), 'renderFAQ') );
		
		//Set the default options
		self::$options_defaults = array(
			'before_question' 	=> '<h3>',
			'after_question' 	=> '</h3>',
			'before_answer' 	=> '<p>',
			'after_answer' 		=> '</p>',
			'before_block' 		=> '<div>',
			'after_block' 		=> '</div>',
			'before_all' 		=> '<div>',
			'after_all' 		=> '</div>'
		);
		
		//Get the options from wordpress database
		self::$options = get_option( 'faq_html_settings' );
		
		//Set options to default if not existing
		self::$options = wp_parse_args( self::$options, self::$options_defaults );
	}
	
	public static function loadTextDomain()
	{
		load_plugin_textdomain( 'lukask-faq', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
	}
	
	public static function registerPostType()
	{
		$args = array(
			'label' 				=> __( 'FAQ' , 'lukask-faq' ),
			'labels' 				=> array(
			
				'name' 					=> __( 'FAQ' , 'lukask-faq' ),
				'singular_name' 		=> __( 'FAQs' , 'lukask-faq' ),
				'add_new_item' 			=> __( 'Add new question' , 'lukask-faq' ),
				'edit_item' 			=> __( 'Edit question' , 'lukask-faq' ),
				'new_item' 				=> __( 'New question' , 'lukask-faq' ),
				'view_item' 			=> __( 'View question' , 'lukask-faq' ),
				'view_items' 			=> __( 'View questions' , 'lukask-faq' ),
				'search_items' 			=> __( 'Search questions' , 'lukask-faq' ),
				'not_found' 			=> __( 'No questions found' , 'lukask-faq' ),
				'not_found_in_trash'	=> __( 'No questions found in trash' , 'lukask-faq' ),
				'parent_item_colon' 	=> __( 'Parent question:' , 'lukask-faq' ),
				'all_items' 			=> __( 'All questions' , 'lukask-faq' ),
				'attributes' 			=> __( 'Question attributes' , 'lukask-faq' ),
				'insert_into_item' 		=> __( 'Insert into question' , 'lukask-faq' ),
				'uploaded_to_this_item' => __( 'Uploaded to this question' , 'lukask-faq' ),
				'menu_name' 			=> __( 'FAQ' , 'lukask-faq' )
				
			),
			'description' 			=> __( 'Post type to create questions and answers.' , 'lukask-faq' ),
			'public' 				=> false,
			'exclude_from_search' 	=> true,
			'publicly_queryable' 	=> false,
			'show_ui' 				=> true,
			'show_in_nav_menus' 	=> false,
			'show_in_menu' 			=> true,
			'show_in_admin_bar' 	=> true,
			'menu_position'			=> 26,
			'menu_icon' 			=> 'dashicons-megaphone',
			'capability_type' 		=> 'post',
			'hierarchical' 			=> false,
			'supports' 				=> array ( 'title' , 'editor' ),
			'has_archive' 			=> false,
			'rewrite' 				=> false,
			'can_export' 			=> true,
			'delete_with_user' 		=> false			
		);
		
		register_post_type( 'lukask_faq', $args );
	}
	
	//[lukask-faq] shortcode
	public static function renderFAQ( $args )
	{
		$ret = '';
		
		$the_query = new WP_Query( array( 
			'post_type' => 'lukask_faq',
			'orderby'   => 'date',
			'order' => 'ASC',
			'posts_per_page' => -1 
		));

		if ( $the_query->have_posts() ) {
			
			//Render FAQ
			
			$ret = self::$options['before_all'];
			
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				
				$ret .= self::$options['before_block'];
				
				$ret .= self::$options['before_question'].get_the_title().self::$options['after_question'];
				$ret .= self::$options['before_answer'].do_shortcode(get_the_content()).self::$options['after_answer'];
				
				$ret .= self::$options['after_block'];
			}
			
			$ret .= self::$options['after_all'];
			
			wp_reset_postdata();
		} else {
			//No questions in database
			$ret = __( 'No questions aswered yet!' , 'lukask-faq');
		}
		
		return $ret;
	}
	
	public static function addAdminPage()
	{
		add_submenu_page( 'edit.php?post_type=lukask_faq', __( 'FAQ settings' , 'lukask-faq' ), __( 'FAQ settings' , 'lukask-faq' ), 'edit_plugins', 'lukask_faq_settings', array( get_called_class() , 'renderAdminPAge' ) );
	}
	
	public static function initSettings()
	{
		register_setting( 'lukask-faq-settings', 'faq_html_settings' );
		
		add_settings_section(
			'lukask-faq-html',
			__('FAQ HTML options', 'lukask-faq' ),
			array( get_called_class(), 'renderAdminPageSetting' ),
			'lukask_faq_settings'
		);
		
		add_settings_field(
			'before-question',
			__( 'Before question', 'lukask-faq' ),
			array( get_called_class(), 'GenerateTextField' ),
			'lukask_faq_settings',
			'lukask-faq-html',
			array( 'setting' => 'before_question' )
		);
		
		add_settings_field(
			'after-question',
			__('After question', 'lukask-faq' ),
			array( get_called_class(), 'GenerateTextField' ),
			'lukask_faq_settings',
			'lukask-faq-html',
			array( 'setting' => 'after_question' )
		);
		
		add_settings_field(
			'before-answer',
			__('Before answer', 'lukask-faq' ),
			array( get_called_class(), 'GenerateTextField' ),
			'lukask_faq_settings',
			'lukask-faq-html',
			array( 'setting' => 'before_answer' )
		);
		
		add_settings_field(
			'after-answer',
			__('After answer', 'lukask-faq' ),
			array( get_called_class(), 'GenerateTextField' ),
			'lukask_faq_settings',
			'lukask-faq-html',
			array( 'setting' => 'after_answer' )
		);
		
		add_settings_field(
			'before-block',
			__('Before block', 'lukask-faq' ),
			array( get_called_class(), 'GenerateTextField' ),
			'lukask_faq_settings',
			'lukask-faq-html',
			array( 'setting' => 'before_block' )
		);
		
		add_settings_field(
			'after-block',
			__('After block', 'lukask-faq' ),
			array( get_called_class(), 'GenerateTextField' ),
			'lukask_faq_settings',
			'lukask-faq-html',
			array( 'setting' => 'after_block' )
		);
		
		add_settings_field(
			'before-all',
			__('Before FAQ section', 'lukask-faq' ),
			array( get_called_class(), 'GenerateTextField' ),
			'lukask_faq_settings',
			'lukask-faq-html',
			array( 'setting' => 'before_all' )
		);
		
		add_settings_field(
			'after-all',
			__('After FAQ section', 'lukask-faq' ),
			array( get_called_class(), 'GenerateTextField' ),
			'lukask_faq_settings',
			'lukask-faq-html',
			array( 'setting' => 'after_all' )
		);
	}
	
	public static function renderAdminPage()
	{
		?>
		<h1><?php echo __( 'FAQ settings' , 'lukask-faq' ); ?></h1>
		<?php settings_errors(); ?>
		<form action='options.php' method='post'>
			<?php 
			settings_fields( 'lukask-faq-settings' );
			do_settings_sections( 'lukask_faq_settings' );
			submit_button();
			?>
		</form>
		<?php
	}
	
	public static function renderAdminPageSetting()
	{
		echo __( 'Determine how the questions and answers are displayed on site. (You can display your FAQ using shortcode [lukask_faq])', 'lukask-faq' );
	}
	
	public static function GenerateTextField( $args )
	{
		//$args = array( 'setting' => 'SETTING NAME' )
		echo '<input type="text" size="70" name="faq_html_settings['.$args['setting'].']" value="'.esc_html(self::$options[$args['setting']]).'" placeholder="'.self::$options_defaults[$args['setting']].'">';
	}
	
}

lukask_faq_class::INIT();

?>