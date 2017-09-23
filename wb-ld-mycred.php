<?php
/**
 *Plugin Name: WBCOM Learndash MyCred Addon
 *Plugin URI: https://wbcomdesigns.com/
 *Description: Plugin Adds Filters of LearnDash for MyCred Points
 *Version: 1.0.0
 *Author: WBCOM DESIGNS
 *Author URI: https://wbcomdesigns.com/
 *Text Domain: wb-ld-mycred-addon
 *Domain Path: /languages.
 */


 //Exit if accessed directly
 if (!defined('ABSPATH')) {
     exit;
 }

/**
* Add Admin Notices
* @since 1.0
* @version 1.0
*/
add_action('admin_notices', 'addon_mycred_ld_dependency_check');
function addon_mycred_ld_dependency_check(){
	if (!in_array('sfwd-lms/sfwd_lms.php', apply_filters('active_plugins', get_option('active_plugins')))) {
		echo "<div class='error'><p><b>".__('LearnDash LMS', 'wb-ld-mycred-addon').'</b> '.__('plugin is not active. In order to make', 'wb-ld-mycred-addon').' <b>'.__("WBCOM Learndash MyCred Addon", 'wb-ld-mycred-addon').'</b> '.__('plugin work, you need to install and activate', 'wb-ld-mycred-addon').' <b>'.__('LearnDash LMS', 'wb-ld-mycred-addon').'</b> '.__('first', 'wb-ld-mycred-addon').'.</p></div>';
		deactivate_plugins(plugin_basename(__FILE__));
		if (isset($_GET['activate'])) {
				unset($_GET['activate']);
		}
		return false;
	}
	if (!in_array('mycred/mycred.php', apply_filters('active_plugins', get_option('active_plugins')))) {
		echo "<div class='error'><p><b>".__('myCRED', 'wb-ld-mycred-addon').'</b> '.__('plugin is not active. In order to make', 'wb-ld-mycred-addon').' <b>'.__("WBCOM Learndash MyCred Addon", 'wb-ld-mycred-addon').'</b> '.__('plugin work, you need to install and activate', 'wb-ld-mycred-addon').' <b>'.__('myCRED', 'wb-ld-mycred-addon').'</b> '.__('first', 'wb-ld-mycred-addon').'.</p></div>';
		deactivate_plugins(plugin_basename(__FILE__));
		if (isset($_GET['activate'])) {
				unset($_GET['activate']);
		}
		return false;
	}
	return true;
}
/**
* Load Language Domain
* @since 1.0
* @version 1.0
*/
add_action('plugins_loaded','wb_ld_setup_textdomain');
function wb_ld_setup_textdomain() {
  $domain  = 'wb-ld-mycred-addon';
  $locale  = apply_filters( 'plugin_locale', get_locale(), $domain );
  //first try to load from wp-content/languages/plugins/ directory
  load_textdomain( $domain, WP_LANG_DIR . '/plugins/' . $domain . '-' . $locale . '.mo' );
  //if not load from languages directory of plugin
  load_plugin_textdomain( 'wb-ld-mycred-addon', false, basename( dirname( __FILE__ ) ) . '/languages' );
}
/**
 * Register Custom myCRED Hook
 * @since 1.0
 * @version 1.0
 */
add_filter( 'mycred_setup_hooks', 'wbld_mycred_hook' );
function wbld_mycred_hook( $installed ) {

	$installed['learndash_hook'] = array(
		'title'       => __( 'LearnDash', 'mycred' ),
		'description' => __( 'Awards %_plural% for LearnDash actions.', 'wb-ld-mycred-addon' ),
		'callback'    => array( 'wbmycred_hook_learndash' )
	);

	return $installed;

}
add_filter( 'mycred_all_references',  'wb_add_ld_references' );
/**
 * Register Custom myCRED References
 * @since 1.0
 * @version 1.0
 */
function wb_add_ld_references( $references ) {
	// LearnDash References
	$references['course_completed'] = 'Completed Course';
	$references['lesson_completed'] = 'Completed Lesson';
	$references['topic_completed'] = 'Completed Topic';
	$references['quiz_completed'] = 'Completed Quiz';
	return $references;
}

/**
 * Hook for LearnDash
 * @since 1.0
 * @version 1.0
 */
add_action( 'mycred_pre_init', 'load_wb_ld_mycred_hook' );
function load_wb_ld_mycred_hook() {
	if ( ! class_exists( 'wbmycred_hook_learndash' ) && class_exists( 'myCRED_Hook' ) ) {
		class wbmycred_hook_learndash extends myCRED_Hook {

			/**
			 * Construct
			 */
			function __construct( $hook_prefs, $type = 'mycred_default' ) {
				parent::__construct( array(
					'id'       => 'learndash_hook',
					'defaults' => array(
						'course_completed'    => array(
							'creds' => 1,
							'log'   => '%plural% for Completing a Course'
						),
						'lesson_completed'    => array(
							'creds' => 1,
							'log'   => '%plural% for Completing a Lesson'
						),
						'topic_completed'    => array(
							'creds' => 1,
							'log'   => '%plural% for Completing a Topic'
						),
						'quiz_completed' => array(
							'creds' => 1,
							'log'   => '%plural% for Completing a Quiz'
						)
					)
				), $hook_prefs, $type );
			}

			/**
			 * Run
			 * @since 1.0
			 * @version 1.1
			 */
			public function run() {

				// Course Completed
				if ( $this->prefs['course_completed']['creds'] != 0 )
					add_action( 'learndash_course_completed',    array( $this, 'wb_ld_course_completed' ), 10, 1 );

				// Lesson Completed
				if ( $this->prefs['lesson_completed']['creds'] != 0 )
					add_action( 'learndash_lesson_completed',    array( $this, 'wb_ld_lesson_completed' ), 10, 1 );

				// Topic Completed
				if ( $this->prefs['topic_completed']['creds'] != 0 )
					add_action( 'learndash_topic_completed',    array( $this, 'wb_ld_topic_completed' ), 10, 1 );

				// Quiz Completed
				if ( $this->prefs['quiz_completed']['creds'] != 0 )
					add_action( 'learndash_quiz_completed',    array( $this, 'wb_ld_quiz_completed' ),10, 1 );


			}

			/**
			 * Course Completed
			 * @since 1.0
			 * @version 1.1
			 */
			public function wb_ld_course_completed( $data ) {

				$course_id = $data['course']->ID;

				// Must be logged in
				if ( ! is_user_logged_in() ) return;

				$user_id = get_current_user_id();

				// Check if user is excluded
				if ( $this->core->exclude_user( $user_id ) ) return;

				// Make sure this is unique event
				if ( $this->core->has_entry( 'course_completed', $course_id, $user_id ) ) return;

				// Execute
				$this->core->add_creds(
					'course_completed',
					$user_id,
					$this->prefs['course_completed']['creds'],
					$this->prefs['course_completed']['log'],
					$course_id,
					array( 'ref_type' => 'post' ),
					$this->mycred_type
				);
			}

			/**
			 * Lesson Completed
			 * @since 1.0
			 * @version 1.1
			 */
			public function wb_ld_lesson_completed( $data ) {
				$lesson_id = $data['lesson']->ID;

				// Must be logged in
				if ( ! is_user_logged_in() ) return;

				$user_id = get_current_user_id();

				// Check if user is excluded
				if ( $this->core->exclude_user( $user_id ) ) return;

				// Make sure this is unique event
				if ( $this->core->has_entry( 'lesson_completed', $lesson_id, $user_id ) ) return;

				// Execute
				$this->core->add_creds(
					'lesson_completed',
					$user_id,
					$this->prefs['lesson_completed']['creds'],
					$this->prefs['lesson_completed']['log'],
					$lesson_id,
					array( 'ref_type' => 'post' ),
					$this->mycred_type
				);
			}

			/**
			 * Topic Completed
			 * @since 1.0
			 * @version 1.1
			 */
			public function wb_ld_topic_completed( $data ) {

				$topic_id = $data['topic']->ID;

				// Must be logged in
				if ( ! is_user_logged_in() ) return;

				$user_id = get_current_user_id();

				// Check if user is excluded
				if ( $this->core->exclude_user( $user_id ) ) return;

				// Make sure this is unique event
				if ( $this->core->has_entry( 'topic_completed', $topic_id, $user_id ) ) return;

				// Execute
				$this->core->add_creds(
					'topic_completed',
					$user_id,
					$this->prefs['topic_completed']['creds'],
					$this->prefs['topic_completed']['log'],
					$topic_id,
					array( 'ref_type' => 'post' ),
					$this->mycred_type
				);
			}

			/**
			 * Quiz Completed
			 */
			public function wb_ld_quiz_completed( $data ) {

				$quiz_id = $data['quiz']->ID;

				// Must be logged in
				if ( ! is_user_logged_in() ) return;

				$user_id = get_current_user_id();

				// Check if user is excluded
				if ( $this->core->exclude_user( $user_id ) ) return;

				// Make sure this is unique event
				if ( $this->core->has_entry( 'quiz_completed', $quiz_id, $user_id ) ) return;

				// Execute
				$this->core->add_creds(
					'quiz_completed',
					$user_id,
					$this->prefs['quiz_completed']['creds'],
					$this->prefs['quiz_completed']['log'],
					$quiz_id,
					array( 'ref_type' => 'post' ),
					$this->mycred_type
				);
			}

				/**
				 * Preferences for LearnDash
				 * @since 1.1
				 * @version 1.0
				 */
				public function preferences() {
					$prefs = $this->prefs; ?>
	        <label class="subheader" for="<?php echo $this->field_id( array( 'course_completed' => 'creds' ) ); ?>"><?php _e( 'Completing a Course', 'wb-ld-mycred-addon' ); ?></label>
	        <ol>
	        	<li>
	        		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'course_completed' => 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'course_completed' => 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['course_completed']['creds'] ); ?>" size="8" /></div>
	        	</li>
	        </ol>
	        <label class="subheader" for="<?php echo $this->field_id( array( 'course_completed' => 'log' ) ); ?>"><?php _e( 'Log Template', 'wb-ld-mycred-addon' ); ?></label>
	        <ol>
	        	<li>
	        		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'course_completed' => 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'course_completed' => 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['course_completed']['log'] ); ?>" class="long" /></div>
	        		<span class="description"><?php echo $this->available_template_tags( array( 'general', 'post' ) ); ?></span>
	        	</li>
	        </ol>

	        <label class="subheader" for="<?php echo $this->field_id( array( 'lesson_completed' => 'creds' ) ); ?>"><?php _e( 'Completing a Lesson', 'wb-ld-mycred-addon' ); ?></label>
	        <ol>
	        	<li>
	        		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'lesson_completed' => 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'lesson_completed' => 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['lesson_completed']['creds'] ); ?>" size="8" /></div>
	        	</li>
	        </ol>
	        <label class="subheader" for="<?php echo $this->field_id( array( 'lesson_completed' => 'log' ) ); ?>"><?php _e( 'Log Template', 'wb-ld-mycred-addon' ); ?></label>
	        <ol>
	        	<li>
	        		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'lesson_completed' => 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'lesson_completed' => 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['lesson_completed']['log'] ); ?>" class="long" /></div>
	        		<span class="description"><?php echo $this->available_template_tags( array( 'general', 'post' ) ); ?></span>
	        	</li>
	        </ol>

	        <label class="subheader" for="<?php echo $this->field_id( array( 'topic_completed' => 'creds' ) ); ?>"><?php _e( 'Completing a Topic', 'wb-ld-mycred-addon' ); ?></label>
	        <ol>
	        	<li>
	        		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'topic_completed' => 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'topic_completed' => 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['topic_completed']['creds'] ); ?>" size="8" /></div>
	        	</li>
	        </ol>
	        <label class="subheader" for="<?php echo $this->field_id( array( 'topic_completed' => 'log' ) ); ?>"><?php _e( 'Log Template', 'wb-ld-mycred-addon' ); ?></label>
	        <ol>
	        	<li>
	        		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'topic_completed' => 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'topic_completed' => 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['topic_completed']['log'] ); ?>" class="long" /></div>
	        		<span class="description"><?php echo $this->available_template_tags( array( 'general', 'post' ) ); ?></span>
	        	</li>
	        </ol>

	        <label class="subheader" for="<?php echo $this->field_id( array( 'quiz_completed' => 'creds' ) ); ?>"><?php _e( 'Completing a Quiz', 'wb-ld-mycred-addon' ); ?></label>
	        <ol>
	        	<li>
	        		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'quiz_completed' => 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'quiz_completed' => 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['quiz_completed']['creds'] ); ?>" size="8" /></div>
	        	</li>
	        </ol>
	        <label class="subheader" for="<?php echo $this->field_id( array( 'quiz_completed' => 'log' ) ); ?>"><?php _e( 'Log Template', 'wb-ld-mycred-addon' ); ?></label>
	        <ol>
	        	<li>
	        		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'quiz_completed' => 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'quiz_completed' => 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['quiz_completed']['log'] ); ?>" class="long" /></div>
	        		<span class="description"><?php echo $this->available_template_tags( array( 'general', 'post' ) ); ?></span>
	        	</li>
	        </ol>
	<?php
				}
		}
	}
}
