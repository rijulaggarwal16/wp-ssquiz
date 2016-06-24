<?php
/*
Plugin Name: SS Quiz
Description: With this plugin you can make quizzes really fast. Add questions/quizzes, rearrange questions, edit answers, insert multimedia in questions, - all of this can be done on single page within several seconds. Also one can edit welcome/finish/email templates using html if it's needed.  To insert quiz into page, use short code [ssquiz id='#']. Quiz automatically determines what type of test user creates (choose-correct, fill-blank or question with several correct answers)
Author: SSVadim
Plugin URI: http://100vadim.com/ssquiz/
Author URI: http://100vadim.com
Version: 2.0.5
License: GPL2
*/

define( 'SSQUIZ_URL', plugin_dir_url( __FILE__ ) . '/' );
define( 'SSQUIZ_CAP', 'edit_others_posts' );

global $ssquiz_db_version;
$ssquiz_db_version = '2.0.4';

function ssquiz_update() {
	global $ssquiz_db_version;
	$installed_ver = get_site_option( 'ssquiz_db_version' );
	if( $installed_ver != $ssquiz_db_version ) {
		ssquiz_install ();
	}
}

add_action( 'admin_init', 'ssquiz_update' );

function ssquiz_install () {
	global $wpdb;
	global $ssquiz_db_version;
	$installed_ver = get_site_option( 'ssquiz_db_version' );
	
	if( $installed_ver != $ssquiz_db_version ) {
		/*
		$wpdb->query("DROP TABLE {$wpdb->base_prefix}ssquiz_quizzes;");
		$wpdb->query("DROP TABLE {$wpdb->base_prefix}ssquiz_questions;");
		$wpdb->query("DROP TABLE {$wpdb->base_prefix}ssquiz_history;");
		delete_site_option( "ssquiz_migrated" );
		*/
		
		$sql = "
		CREATE TABLE {$wpdb->base_prefix}ssquiz_quizzes (
			id mediumint(9) UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(100) NOT NULL,
			type VARCHAR(50) DEFAULT '',
			meta text DEFAULT '' NOT NULL,
			created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			UNIQUE KEY id (id))
		DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
		
		CREATE TABLE {$wpdb->base_prefix}ssquiz_questions (
			id mediumint(9) UNSIGNED NOT NULL AUTO_INCREMENT,
			quiz_id mediumint(9) UNSIGNED NOT NULL,
			number mediumint(9) UNSIGNED,
			type VARCHAR(50) DEFAULT '' NOT NULL,
			question text DEFAULT '' NOT NULL,
			meta text DEFAULT '' NOT NULL,
			answers text DEFAULT '' NOT NULL,
			UNIQUE KEY id (id))
		DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
		
		CREATE TABLE {$wpdb->base_prefix}ssquiz_history (
			id mediumint(9) UNSIGNED NOT NULL AUTO_INCREMENT,
			quiz_id mediumint(9) UNSIGNED,
			user_id mediumint(9) UNSIGNED,
			meta text DEFAULT '' NOT NULL,
			answered tinyint UNSIGNED NOT NULL,
			correct tinyint UNSIGNED NOT NULL,
			total tinyint UNSIGNED NOT NULL,
			timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			UNIQUE KEY id (id))
		DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
		";
	
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta( $sql );
		
		if (false == get_option( "ssquiz_settings" ) )
			ssquiz_set_settings();

		if( $wpdb->get_var( "SHOW TABLES LIKE 'ssquiz_quizzes'") == 'ssquiz_quizzes' && true != get_site_option( "ssquiz_migrated" ) ) {
			ssquiz_migrate();
			update_site_option( "ssquiz_migrated", true );
		}
		elseif ( (! $installed_ver) && ($wpdb->get_var( "SHOW TABLES LIKE 'ssquiz_quizzes'") != 'ssquiz_quizzes') )
			ssquiz_install_data(); // First use of SSQuiz
		
		$wpdb->query( "DELETE FROM {$wpdb->prefix}ssquiz_questions WHERE number = 99999;" );  // deletes dummy questions
		
		update_site_option( "ssquiz_db_version", $ssquiz_db_version );
	}
	
	$temp->offset = 0;
	update_option( 'ssquiz_page', $temp );
}

function ssquiz_set_settings() {
	
	$settings = new stdClass();
	
	$settings->start_template = '
	<h2 align="center">Let\'s start the quiz "%%TITLE%%"!</h2><br />
	%%DESCRIPTION%%<br />
	You will be asked %%QUESTIONS%% questions';
	
	$settings->finish_template = '
	<h2 class="intro">Quiz is finished! Your correctly answered to %%PERCENT%%% of  %%TOTAL%% questions.</h2><br />';
	
	$settings->user_email_template = '
	You\'ve just done quiz. You correctly answered to %%PERCENT%%% of  %%TOTAL%% questions.';
	
	$settings->teacher_email_template = '
	User %%NAME%% correctly answered to %%CORRECT%% of %%TOTAL%% questions. His email: <%%EMAIL%%>.';
	
	$settings->teacher_email_address = '';
	$settings->teacher_will_receive = false;
	$settings->user_will_receive = false;
	$settings->user_email_subject = 'You completed quiz "%%TITLE%%"';
	$settings->teacher_email_subject = '%%NAME%% completed quiz "%%TITLE%%"';
	//$settings->pdf_template = '%%NAME%% completed quiz "%%TITLE%%"';
	
	update_option( "ssquiz_settings", $settings );

}

function ssquiz_delete_transients() {
	global $wpdb, $_wp_using_ext_object_cache;

	if($_wp_using_ext_object_cache)
		return;
	$time = isset ( $_SERVER['REQUEST_TIME'] ) ? (int)$_SERVER['REQUEST_TIME'] : time() ;
	$expired = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout%' AND option_value < {$time};" );

	foreach( $expired as $transient ) {
		$key = str_replace('_transient_timeout_', '', $transient);
		delete_transient($key);
	}
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_answer_sheet%';" );	
}

// For migrating from old version
function ssquiz_migrate() {
	global $wpdb;
	
	// Deleting junk transients from previous versions
	ssquiz_delete_transients();
	
	// Moving settings
	$settings = $wpdb->get_results( "SELECT name, value FROM ssquiz_settings ");
	
	$temp = get_option( "ssquiz_settings" );
	foreach ($settings as $setting) {
		switch ($setting->name) {
			case 'start_screen':
				$temp->start_template = $setting->value;
				break;
			case 'result_screen':
				$temp->finish_template = $setting->value;
				break;
			case 'email_screen':
				$temp->user_email_template = $setting->value;
				break;
			case 'ssteachers_template':
				$temp->teacher_email_template = $setting->value;
				break;
			case 'ssteachers_email':
				$temp->teacher_email_address = $setting->value;
				break;
			case 'should_receive':
				$temp->teacher_will_receive = ($setting->value === 'true');
				break;
			case 'user_receive':
				$temp->user_will_receive = ($setting->value === 'true');
				break;
			case 'users_header':
				$temp->user_email_subject = $setting->value;
				break;
			case 'teachers_header':
				$temp->teacher_email_subject = $setting->value;
				break;
			//case 'report_header':
			//	$temp->pdf_template = $setting->value;
			//	break;
		}
	}
	update_option( "ssquiz_settings", $temp );
	//$wpdb->query("DROP TABLE ssquiz_settings;");
	
	$wpdb->show_errors();
	
	// Moving History
	$attempts = $wpdb->get_results( "SELECT * FROM ssquiz_users;");
	foreach ($attempts as $attempt) {
		$temp = new stdClass();
		$temp->user_name = $attempt->user_name;
		$temp->user_email = $attempt->user_email;
		$wpdb->insert("{$wpdb->base_prefix}ssquiz_history", 
			array( 
				'id' => intval( $attempt->id ),
				'quiz_id' => intval( $attempt->quiz_id ), 
				'user_id' => intval( $attempt->user_id ), 
				'meta' => serialize( $temp ), 
				'answered'=> intval( $attempt->answered ), 
				'correct' => intval( $attempt->correct ), 
				'total' => intval( $attempt->total ),  
				'timestamp' => $attempt->date_stamp
			), 
			array( '%d', '%d', '%d', '%s', '%d', '%d', '%d', '%s' ) );
	}
	//$wpdb->query("DROP TABLE ssquiz_users;");
	
	// Moving Quizzes
	$quizzes = $wpdb->get_results( "SELECT * FROM ssquiz_quizzes;");
	foreach ($quizzes as $quiz) {
		$meta = new stdClass();
		$meta->description = $quiz->description;
		$meta->next_link = $quiz->next;
		$wpdb->insert("{$wpdb->base_prefix}ssquiz_quizzes", 
			array(
				'id' => intval( $quiz->id ),
				'name' => $quiz->title, 
				'type' => ( ( $quiz->type > '' ) ? $quiz->type : '' ),
				'meta' => serialize( $meta ),
				'created' => ($quiz->created != NULL) ? $quiz->created : date('Y-m-d H:i:s')
			), 
			array( '%d', '%s', '%s', '%s', '%s' ) );
	}
	//$wpdb->query("DROP TABLE ssquiz_quizzes;");
	
	// Moving Questions
	$questions = $wpdb->get_results( "SELECT * FROM ssquiz_questions;");
	foreach ($questions as $question) {
		$answers = $wpdb->get_results( "SELECT * FROM ssquiz_answers WHERE question_id = {$question->id} ORDER BY number ASC;");
		if( $question->number == '9999' || count($answers) < 1 )
			continue;
		$temp_answer = array();
		$number_correct = 0;
			foreach ( $answers as $answer ) {
				if( intval($answer->number) > 999 )
					continue;
				if ( $answer->correct === "1" ) $number_correct++;
				$temp = new stdClass();
				$temp->answer = $answer->answer;
				$temp->correct = ($answer->correct === "1") ? true : false;
				array_push($temp_answer, $temp);
			}
		$meta = new stdClass();
		$meta->hint = $question->hint;
		
		$type = false;
		if( 1 >= count($answers) ) // fill-the-gaps included
			$type = 'fill';
		elseif ( 1 < count($answers) && 1 == $number_correct )
			$type = 'single';
		elseif ( 1 < count($answers) && 1 < $number_correct )
			$type = 'multi';
		if (false == $type)
			continue;
			
		if( 1 == count( $temp_answer ) ) {
			$temp_answer = explode( '|', $temp_answer[0]->answer );
		}

		$wpdb->insert( "{$wpdb->base_prefix}ssquiz_questions", 
			array( 
				'id' => intval( $question->id ),
				'quiz_id' => intval($question->quiz_id), 
				'number' => intval($question->number), 
				'type' => $type, 
				'question' => $question->question,
				'answers' => serialize( $temp_answer ),
				'meta' => serialize( $meta )
			), 
			array( '%d', '%d', '%d', '%s', '%s', '%s', '%s' ) );
	}
	//$wpdb->query("DROP TABLE ssquiz_questions;");
}

function ssquiz_install_data() {
	global $wpdb;
	
	$meta = new stdClass();
	$meta->description = 'Some questions about geography';
	$wpdb->insert("{$wpdb->base_prefix}ssquiz_quizzes", 
		array( 
			'name' => __( 'Back to school', 'ssquiz' ),
			'meta' => serialize( $meta )
		), 
		array( '%s', '%s' ) );
		
	$quiz_id = $wpdb->insert_id;
	
	$wpdb->insert( "{$wpdb->base_prefix}ssquiz_questions", 
		array( 
			'quiz_id' => $quiz_id, 
			'number' => 1,
			'type' => 'fill',
			'question' => 'Highest Mountain?',
			'answers' => serialize( array( 'Everest' ) )
		), 
		array( '%d', '%d', '%s', '%s', '%s' ) );
		
	$wpdb->insert( "{$wpdb->base_prefix}ssquiz_questions", 
		array( 
			'quiz_id' => $quiz_id, 
			'number' => 2,
			'type' => 'single',
			'question' => 'Capital of Russia?',
			'answers' => serialize( array( (object) array( 'answer' => 'Moscow', 'correct' => true ), 
											(object) array( 'answer' => 'Beijing', 'correct' => false ) ) )
		), 
		array( '%d', '%d', '%s', '%s', '%s' ) );

	$wpdb->insert( "{$wpdb->base_prefix}ssquiz_questions", 
		array(
			'quiz_id' => $quiz_id, 
			'number' => 3,
			'type' => 'multi',
			'question' => 'African countries',
			'answers' => serialize( array( (object) array( 'answer' => 'Cameroon', 'correct' => true ), 
											(object) array( 'answer' => 'Sri Lanka', 'correct' => false ),
											(object) array( 'answer' => 'Algeria', 'correct' => true ) ) )
		), 
		array( '%d', '%d', '%s', '%s', '%s' ) );
		
	$temp = new stdClass();
	$temp->user_name = 'test';
	$temp->user_email = 'test@gmail.com';
	$wpdb->insert("{$wpdb->base_prefix}ssquiz_history", 
		array( 
			'user_id' => 1,
			'quiz_id' => 1,
			'meta' => serialize( $temp ),
			'answered'=> 2,
			'correct' => 2,
			'total' => 2
		), 
		array( '%d', '%d', '%s', '%d', '%d', '%d' ) );
}

function ssquiz_uninstall(){
	global $wpdb;
	
	//delete_site_option( "ssquiz_db_version" );
	//delete_option( 'ssquiz_settings' );
	delete_option( 'ssquiz_history' );
	delete_option( 'ssquiz_page' );
	//delete_site_option( 'ssquiz_migrated' );
	
	//$wpdb->query("
	//	DROP TABLE {$wpdb->base_prefix}ssquiz_quizzes;
	//	DROP TABLE {$wpdb->base_prefix}ssquiz_questions;
	//	DROP TABLE {$wpdb->base_prefix}ssquiz_history;
	//");
}

register_activation_hook( __FILE__, 'ssquiz_install' );
register_uninstall_hook( __FILE__, 'ssquiz_uninstall' );

function ssquiz_init() {
	load_plugin_textdomain( 'ssquiz', false, plugins_url( 'ssquiz/lang/' ) );
}
add_action( 'plugins_loaded', 'ssquiz_init' );

function ssquiz_menu_setup() {
	add_menu_page(__('SS Quiz', 'ssquiz'), __('SS Quiz', 'ssquiz'), SSQUIZ_CAP, 'ssquiz_menu', 'draw_main_page', SSQUIZ_URL . 'assets/icon.png' );
	add_submenu_page( 'ssquiz_menu', __('Statistics', 'ssquiz'), __('Statistics', 'ssquiz'), SSQUIZ_CAP, 'ssquiz_menu_statistics', 'draw_statistics_page');
	add_submenu_page( 'ssquiz_menu', __('Settings', 'ssquiz'), __('Settings', 'ssquiz'), SSQUIZ_CAP, 'ssquiz_menu_settings', 'draw_settings_page');
	add_submenu_page( 'ssquiz_menu', __('Documentation', 'ssquiz'), __('Documentation', 'ssquiz'), SSQUIZ_CAP, 'ssquiz_menu_docs', 'draw_docs_page');
}
add_action( 'admin_menu', 'ssquiz_menu_setup' );

require_once( 'admin-main.php' );
require_once( 'admin-statistics.php' );
require_once( 'admin-settings.php' );
require_once( 'admin-doc.php' );
require_once( 'client-quiz.php' );

add_shortcode( 'ssquiz', 'ssquiz_start' );

// For DEBUG
function save_error(){
	file_put_contents(ABSPATH. 'wp-content/error_activation.html', ob_get_contents());
}
//add_action('activated_plugin','save_error');

function ssquiz_load_admin_scripts() {
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'jquery-ui-sortable' );
	wp_enqueue_script('bootstrap_js', plugins_url('assets/bootstrap.min.js', __FILE__), array( 'jquery-ui-sortable' ) );
	
	wp_enqueue_script( 'ssquiz_script', SSQUIZ_URL . 'ssquiz.js', array( 'jquery', 'bootstrap_js' ) );
	wp_localize_script( "ssquiz_script", 
		"ssquiz", array('ajaxurl' => admin_url('admin-ajax.php'), 
						'assets' => SSQUIZ_URL . 'assets/',
						'add_quiz' => __('Add Quiz', 'ssquiz'),
						'edit_quiz' => __('Edit Quiz', 'ssquiz'),
						'add_question' => __('Add Question', 'ssquiz'),
						'edit_question' => __('Edit Question', 'ssquiz'),
						'save' => __('Save', 'ssquiz') 
				) );
	wp_enqueue_style('less-style', SSQUIZ_URL . 'assets/styles.css' );
	wp_enqueue_style( "admin_ssquiz_style", SSQUIZ_URL . 'admin-style.css' );
}
add_action('admin_enqueue_scripts', 'ssquiz_load_admin_scripts');

function ssquiz_load_front_scripts() {
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'json2', SSQUIZ_URL . 'assets/json2.js' );
	wp_enqueue_script( 'ssquiz_aux', SSQUIZ_URL . 'assets/touches-enabler.js', array( 'jquery' ) );
	wp_enqueue_script( 'ssquiz_script', SSQUIZ_URL . 'ssquiz.js', array( 'ssquiz_aux' ) );
	
	wp_localize_script( "ssquiz_script", 
		"ssquiz", array('ajaxurl' => admin_url('admin-ajax.php'),
						'assets' => SSQUIZ_URL . 'assets/',
						'finish' => __('Finish', 'ssquiz'),
						'next' => __('Next', 'ssquiz')
		) );
						
	wp_enqueue_style( "admin_ssquiz_style", SSQUIZ_URL . 'ssquiz-style.css' );
}
add_action( 'wp_enqueue_scripts', 'ssquiz_load_front_scripts' );

// Adding to the wordpress admin footer
function ssquiz_print_footer(){
	?>
	<div>
		<p><?php echo __( 'If you have questions, suggestions or want to know about my upcoming projects, visit', 'ssquiz')?> 
			<a href="http://www.100vadim.com">100vadim.com</a>
		</p>
	</div>
	<?php
}