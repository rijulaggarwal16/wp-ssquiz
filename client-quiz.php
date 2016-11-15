<?php 

function ssquiz_return_quiz_body ( $title, $content = '' , $footer = '' ) {
	ob_start();
	?>
    <div class="ssquiz">
		<div class="ssquiz_header">
			<?php echo $title; ?>
		</div>
		<div class="ssquiz_body">
			<?php echo $content; ?>
		</div>
		<div class="ssquiz_footer">
			<?php echo $footer; ?>
		</div>
    </div>
	<?php
	$output = ob_get_contents();
	ob_end_clean();
	return $output;
}

function ssquiz_add_hidden(&$new_screen, &$status, &$info ) {
	$new_screen .= '<div class="ssquiz_hidden_status" style="display:none;">'. htmlspecialchars( json_encode( $status ), ENT_NOQUOTES ) .'</div>';
	$new_screen .= '<div class="ssquiz_hidden_info" style="display:none;">'. base64_encode(gzcompress(serialize($info))) .'</div>';
	return $new_screen;
}

function checkPrereq($quiz_id){
	global $wpdb;
    $user_id = get_current_user_id();
	$quiz_meta = $wpdb->get_var("SELECT meta FROM {$wpdb->base_prefix}ssquiz_quizzes WHERE id=".$quiz_id);
    $quiz_meta = unserialize($quiz_meta);
    if($quiz_meta->prerequisites > 0){
       $prereq_status = $wpdb->get_row("SELECT questions_right,total,question_offset FROM {$wpdb->base_prefix}self_ssquiz_response_history AS s JOIN {$wpdb->base_prefix}ssquiz_history AS h ON s.quiz_id=h.quiz_id WHERE s.user_id=".$user_id." AND s.quiz_id=".$quiz_meta->prerequisites." order by timestamp desc limit 1");
    }
	$pass_percent = 85;
	if(is_super_admin() || (null != $quiz_status && $quiz_status->question_offset < $quiz_status->total) || (null == $quiz_status && null != $prereq_status && $prereq_status->question_offset >= $prereq_status->total && ($prereq_status->questions_right/$prereq_status->total)*100 >= $pass_percent) || (null == $quiz_status && $quiz_meta->prerequisites <= 0)){
		return true;
	}
	return false;
}

// Called by 'ssquiz' shortcode
function ssquiz_start( $params ) {
	global $wpdb;
	if(false === checkPrereq($params["id"]))
		return ssquiz_return_quiz_body("<h2>You must successfully complete the Prerequisite for this course first.</h2>","","");
	$settings = get_option( 'ssquiz_settings' );
	$info = new stdClass();
	$status = new stdClass();

	// Paging Settings
	$info->paging = 10;
	$info->current_page = 0;
	$status->paging = $info->paging;
	$info->params = $params;
	
	foreach ($params as $name)
		$info->$name = true;
		
	$quiz_id = intval( $params["id"] );
	// quiz_id must be set
	if( $quiz_id < 1 )
		return ssquiz_return_quiz_body( __('Quiz is not selected', 'ssquiz') );
	unset( $params["id"] );
	
	$info->quiz = $wpdb->get_row("SELECT * FROM {$wpdb->base_prefix}ssquiz_quizzes WHERE id = $quiz_id;");
	$info->questions = $wpdb->get_results("SELECT * FROM {$wpdb->base_prefix}ssquiz_questions WHERE quiz_id = $quiz_id ORDER BY number ASC;");
	$info->quiz->meta = unserialize( $info->quiz->meta );
	
	if ( $info->all )
		$status->all_at_once = true;
	
	if ( $info->qrandom )
		shuffle( $info->questions );
	
	if ( intval( $params["total"] ) > 0 )
		$info->questions = array_slice( $info->questions, 0, intval( $params["total"] ) );
	
	foreach ( $info->questions as $question ) {
		$question->meta = unserialize( $question->meta );
		$question->answers = unserialize( $question->answers );
	}
	$info->total_questions = count( $info->questions );
	
	if( ! isset($info->quiz->name) )
		return ssquiz_return_quiz_body( __('Quiz doesn\'t exist', 'ssquiz') );
	
	if( isset( $params["timer"] ) ) {
		$info->timer = intval( $params["timer"] );
		$status->timer = intval( $params["timer"] );
		unset( $params["timer"] );
	}
	
	global $current_user;
	get_currentuserinfo();
	$info->user = new stdClass();
	$info->user->id = $current_user->ID;
	$info->user->email = $current_user->user_email;
	$info->user->name = $current_user->display_name;
	$info->user->role = array_shift($current_user->roles);

	$start_screen = $settings->start_template;
	ssquiz_tag_replace($start_screen, $info, 'start');

	$status->total_questions = $info->total_questions;

	// Resuming quiz
	$quiz_history = $wpdb->get_row("SELECT * FROM {$wpdb->base_prefix}self_ssquiz_response_history WHERE user_id={$info->user->id} && quiz_id={$info->quiz->id};");
	if(null == $quiz_history){
		 basicStartInitialize($info, $status);
	} elseif((!is_super_admin()) && (($info->two_chance && intval($quiz_history->question_offset) >= $info->total_questions && intval($quiz_history->attempts) >= 2) || ($info->one_chance && intval($quiz_history->question_offset) >= $info->total_questions && intval($quiz_history->attempts) >= 1) || (has_passed($info->user->id, $info->quiz->id)))){
		$status->questions_counter = $info->total_questions;
		$info->questions_counter = $info->total_questions;
		$info->questions_right = intval($quiz_history->questions_right);
		$info->current_page = intval($quiz_history->page_offset-1);
		$info->just_started = false;
		$status->just_started = false;
		$status->current_page = $info->current_page;
		return ssquiz_return_quiz_body( '<h2>'. $info->quiz->name .'</h2>', unserialize( gzuncompress( base64_decode($quiz_history->finish_screen))), '<script>document.getElementsByClassName("history_list")[0].insertAdjacentHTML("beforebegin",\'<div class="ssquiz_history"></div>\');</script>' );
	} elseif(intval($quiz_history->question_offset) >= $info->total_questions){
		if(is_super_admin() || !($info->two_chance || $info->one_chance)){
			$wpdb->delete($wpdb->base_prefix.'self_ssquiz_response_history',array('user_id'=>$info->user->id,'quiz_id'=>$info->quiz->id),array('%d','%d'));
		}
		else{
			$wpdb->update($wpdb->base_prefix.'self_ssquiz_response_history',array('attempts' => intval($quiz_history->attempts) + 1), array('user_id'=>$info->user->id,'quiz_id'=>$info->quiz->id), array('%d'), array('%d','%d'));
		}
		basicStartInitialize($info, $status);
	}else{
		if(intval($quiz_history->question_offset) <= 0){
			$status->questions_counter = 0;
			$info->questions_counter = 0;
		}else{
			$status->questions_counter = intval($quiz_history->question_offset);
			$info->questions_counter = intval($quiz_history->question_offset);
		}
		$info->questions_right = intval($quiz_history->questions_right);
		$info->current_page = intval($quiz_history->page_offset);
		$info->page_answers = json_decode(base64_decode( $quiz_history->page_responses ));
		$info->just_started = false;
		$status->just_started = false;
		$status->resuming = true;
		$status->current_page = $info->current_page;
	}
	$status->finished = false;
	$status->exit = false;
	$status->save_processed = false;
			
	// if logged in fill the input
	if ( $info->name ) {
		$temp = ( $info->user->name > '') ? $info->user->name : '';
		$start_screen .='
			<div>
				<label for="user_name">' . __("Name", 'ssquiz') . ': </label>
				<input type="text" class="ssquiz_user_name" name="user_name" value="'.$temp.'" />
				<span class="help-error" style="display:none;">' . __("Please write your name", 'ssquiz') . '</span>
			</div>';
		$status->name = true;
	}
	
	if ( $info->email ) {
		$temp = ( $info->user->email > '') ? $info->user->email : '';
		$start_screen .='
			<div>
				<label for="user_email">' . __("E-Mail", 'ssquiz') . ': </label>
				<input type="text"  class="ssquiz_user_email" name="user_email" value="'.$temp.'" />
				<span class="help-error" style="display:none;">' . __("Not a valid e-mail address", 'ssquiz') . '</span>
			</div>';
		$status->email = true;
	}

	$footer = '
		<button class="ssquiz_ok ssquiz_btn">' . __("Start", 'ssquiz') . '</button>
		<button class="ssquiz_exit ssquiz_btn" style="display: none;">' .'Save & Exit' . '</button>';

	$header = '<h2>'. $info->quiz->name .'</h2>';
	if ( isset( $info->timer ) ) {
		$header .= '<div class="ssquiz_timer">
						<img class="check_img"src="' . SSQUIZ_URL . 'assets/time.png" /><span>' . gmdate( "i:s", $info->timer ) . '</span></div>';
	}

	return ssquiz_return_quiz_body( $header, ssquiz_add_hidden($start_screen, $status, $info ), $footer );
}

function basicStartInitialize(&$info, &$status){
	$status->questions_counter = 0;
	$info->questions_counter = 0;
	$info->questions_right = 0;
	$info->just_started = true;
	$status->just_started = true;
	$status->resuming = false;
	$status->current_page = $info->current_page;
}

function has_passed($user_id, $quiz_id){
	global $wpdb;
	$quiz_status = $wpdb->get_row("SELECT questions_right,total,question_offset FROM {$wpdb->base_prefix}self_ssquiz_response_history AS s JOIN {$wpdb->base_prefix}ssquiz_history AS h ON s.quiz_id=h.quiz_id WHERE s.user_id=".$user_id." AND s.quiz_id=".$quiz_id." order by timestamp desc limit 1");
	$pass_percent = 85;
	if (null != $quiz_status && $quiz_status->question_offset >= $quiz_status->total && ($quiz_status->questions_right/$quiz_status->total)*100 >= $pass_percent) {
		return true;
	}
	return false;
}

// Will be executed after cookie data is successfully stored in DB
function self_ssquiz_store_backup(){
	global $wpdb;
	$info = unserialize( gzuncompress( base64_decode( $_REQUEST['info'] )  ));
	$data['result_backup'] = base64_encode(gzcompress($_REQUEST['backup'])) ;
	$where = array("user_id"=>$info->user->id,"quiz_id"=>$info->quiz->id);
	$wpdb->update("{$wpdb->base_prefix}self_ssquiz_response_history",$data,$where,array("%s"),array('%d','%d'));
	wp_die();
}

add_action('wp_ajax_self_ssquiz_store_backup', 'self_ssquiz_store_backup');

function self_ssquiz_get_backup(){
	global $wpdb;
	$info = unserialize( gzuncompress( base64_decode( $_REQUEST['info'] )  ));
	$result_backup = $wpdb->get_var("SELECT result_backup FROM {$wpdb->base_prefix}self_ssquiz_response_history WHERE user_id={$info->user->id} && quiz_id={$info->quiz->id};");
	if(NULL == $result_backup || $result_backup === false){
		echo json_encode(array());
	} else
		echo stripslashes(gzuncompress(base64_decode($result_backup)));
	wp_die();
}

add_action('wp_ajax_self_ssquiz_get_backup', 'self_ssquiz_get_backup');

function self_ssquiz_save(){
	$info = unserialize( gzuncompress( base64_decode( $_REQUEST['info'] )  ));
	$status = json_decode( stripslashes( $_REQUEST['status'] ) );

	self_helper_save($info, $status, true);
	wp_die();
}
add_action('wp_ajax_self_ssquiz_save', 'self_ssquiz_save');

function self_helper_save(&$info, &$status, $same_page){
	// Store recent responses
	$recent_answers = "";
	if($status->answers != null && count($status->answers) > 0){
		$i = ($info->current_page-1)*$info->paging;
		$i = $i<0?0:$i;
		$answerIndex = 0;
		$grouped_answers = array();
		for ( ; $i < ($info->current_page)*$info->paging && $i < $info->total_questions; $i++ ) {
			$question = $info->questions[$i];
			$temp = array();
			for ( $j = 0; $j < count( $question->answers ); $j++ ) {
				$temp[] = $status->answers[$answerIndex];
				$answerIndex++;
			}
			$grouped_answers[] = $temp;
		}
		$recent_answers = base64_encode(json_encode($grouped_answers));
	}

	if(false == $same_page){
		// Store All Responses
		$store = array();
		if(count($status->answers) > 0 && !$status->exit){
			if(!$status->save_processed)
				$store[] = $status->answers;
			else {
				$store[count($store) - 1] = $status->answers;
			}
			$status->save_processed = $same_page;		// Staying on the same page or moving to next 
		}
		if(count($store) > 0){
			// save responses
			checkin_responses($info, base64_encode(gzcompress(serialize($store))),$same_page);
		}
	}
	// save current page (After saving all responses as it generates user_id,quiz_id mapping)
	if(!empty($recent_answers))
		checkin_current_responses($info, $recent_answers);
}

function ssquiz_response() {
	$info = unserialize( gzuncompress( base64_decode( $_REQUEST['info'] )  ));
	$status = json_decode( stripslashes( $_REQUEST['status'] ) );

	// Restart?
	if ( true == $status->restart )
		wp_die( ssquiz_start( $info->params ) );
	
	// First question
	if( true == $info->just_started ) { 
		$info->started = time();
		if ( $info->name ) {
			$info->user->name = $status->name;
			unset($status->name);
		}
		if ( $info->email ) {
			$info->user->email = $status->email;
			unset($status->email);
		}
		$info->just_started = false;
	}
	// Checking
	else if(false == $status->resuming){
		if( ! $info->all && $info->paging <= 1 ) {
			$temp = ssquiz_check_answers( $info->questions_counter, $info, $status->answers);
			if( $info->show_correct )
			$new_screen .= $temp;
			$status->results = $info->results;
		}
		else if(! $info->all && $info->paging > 1){	// paging of questions
			$status->results = '';
			$i = ($info->current_page-1)*$info->paging;
			$i = $i < 0?0:$i;
			$answerIndex = 0;
			for ( ; $i < ($info->current_page)*$info->paging && $i < $info->total_questions; $i++ ) {
				$question = $info->questions[$i];
				$temp = array();
				if($question->type == 'fill'){
					$temp[] = $status->answers[$answerIndex];
					$answerIndex++;
				}else{
					for ( $j = 0; $j < count( $question->answers ); $j++ ) {
						$temp[] = $status->answers[$answerIndex];
						$answerIndex++;
					}
				}
				ssquiz_check_answers( $i+1, $info, $temp );
				$status->results .= $info->results;
			}
		}
		else { // check all questions
			$status->results = '';
			for ( $i = 0; $i < count( $info->questions ); $i++ ) {
				ssquiz_check_answers( $i+1, $info, $status->answers[$i] );
				$status->results .= $info->results;
				if ( $i != $info->total_questions -1 )
					$status->results .= '<hr />';
			}
		}
	} else{
		$status->results = '';
	}
	if ( false == $status->finished ) {
		if( ! $info->all && $info->paging <= 1 ) {
			$new_screen .= ssquiz_print_question( $info->questions[$info->questions_counter], $info, array() );
			$type = $info->questions[$info->questions_counter]->type;
			$info->questions_counter++;
		}
		else if(! $info->all && $info->paging > 1){	// paging of questions
			$index = 0;
			if(!$status->resuming)
				$info->current_page++;
			$status->current_page = $info->current_page;
			for($i = $info->questions_counter; $i < $info->current_page*$info->paging && $i < $info->total_questions; $i++){
				if($info->page_answers == null || count($info->page_answers) <= 0)
					$new_screen .= ssquiz_print_question( $info->questions[$i], $info, array() );
				else{
					$new_screen .= ssquiz_print_question( $info->questions[$i], $info, $info->page_answers[$index] );
					$index++;
				}
				$info->questions_counter++;
				if ( $info->questions_counter != $info->total_questions )
					$new_screen .= '<hr />';
			}
			$info->page_answers = array();
		}
		else { // print all questions at once
			foreach ($info->questions as $question) {
				$new_screen .= ssquiz_print_question( $question, $info, array() );
				$info->questions_counter++;
				if ( $info->questions_counter != $info->total_questions )
					$new_screen .= '<hr />';
			}
			$new_screen .= '<script>jQuery.fn.run_all_at_once();</script>';
		}
		$status->questions_counter = $info->questions_counter;
		self_helper_save($info, $status, false);
		wp_die ( ssquiz_add_hidden( $new_screen, $status, $info ) );
	}
	// finished
	else{
		if(!$status->exit)
			$info->current_page++;
		self_helper_save($info, $status, false);
		wp_die( ssquiz_finish( $new_screen, $status, $info ) );
	}
}

add_action('wp_ajax_ssquiz_response', 'ssquiz_response');

function checkin_current_responses($info,$response_data){
	global $wpdb;
	$sql = "INSERT INTO {$wpdb->base_prefix}self_ssquiz_response_history (question_offset,page_offset,user_id,quiz_id,page_responses) VALUES (%d,%d,%d,%d,%s) ON DUPLICATE KEY UPDATE page_responses = %s";
	$sql = $wpdb->prepare($sql,($info->current_page-1)*$info->paging,$info->current_page,$info->user->id,$info->quiz->id,$response_data,$response_data);
	$wpdb->query($sql);
}

/**
* To store the responses in the database (Append)
* @arg  cookie_data The zipped, base64 encoded and serialized cookie data to store
**/
function checkin_responses(&$info,$response_data,$reset_page){
	global $wpdb;
	if($response_data == null)
		return;
	$response_history = $wpdb->get_row("SELECT * FROM {$wpdb->base_prefix}self_ssquiz_response_history WHERE user_id={$info->user->id} && quiz_id={$info->quiz->id};");
	$data = array();
	$data['result_backup'] = $response_history->result_backup;
	if(true == $reset_page) 
		$data['page_responses'] = $response_history->page_responses;
	else
		$data['page_responses'] = '';
	$data['question_offset'] = ($info->current_page-1)*$info->paging;
	$data['page_offset'] = $info->current_page;
	$data['questions_right'] = $info->questions_right;
	if($response_history != NULL && !empty($response_history->response_meta))
		$array1 = unserialize(gzuncompress(base64_decode($response_history->response_meta)));
	$array2 = unserialize(gzuncompress(base64_decode($response_data)));
	$final_data = empty($array1)?$array2:array_merge($array1,$array2);
	$data['response_meta'] = base64_encode(gzcompress(serialize($final_data)));
	$data['user_id'] = $info->user->id;
	$data['quiz_id'] = $info->quiz->id;
	if(null == $response_history)
		$data['attempts'] = 1;
	else
		$data['attempts'] = $response_history->attempts;
	$wpdb->replace("{$wpdb->base_prefix}self_ssquiz_response_history",$data,array('%s','%s','%d','%d','%d','%s','%d','%d','%d'));
}

function ssquiz_check_answers( $number, &$info, &$answers ) {
	$question = &$info->questions[$number-1];
	$correct = true;
	ob_start();
	echo '<div style="margin-bottom:15px;">';
	echo "<strong>" . __("Question") . (( $info->all ) ? " $number" : " $number/{$info->total_questions}" ) . ":</strong><br />";
	echo '</div>';
	echo apply_filters( 'the_content', $question->question );
	if( $question->type == 'fill' ) {
		if ( true == in_array( strtolower( $answers[0]->answer ), array_map('strtolower', $question->answers ) ) )
			echo '<span class="help-ok">' . __("Your answer: ") . $answers[0]->answer . '</span></br />
					<span class="help-ok">' . __("You were right!") . '</span></br>';
		else {
			$temp = ( $answers[0]->answer > '' ) ? __("Your answer: ") . $answers[0]->answer : __("You didn't answer"); 
			echo '<span class="help-wrong">' . $temp . '</span></br />
					<span>' . __("Correct answer is") . ': <span class="help-ok">' . $question->answers[0] . '</span></span></br />';
			$correct = false;
		}
	}
	else if( $question->type == 'single' || $question->type == 'multi' ) {
		for ( $i = 0; $i < count( $question->answers ); $i++ ) {
			if ( true == $question->answers[$i]->correct )
				if( true == $answers[$i]->correct )
					echo '<img class="check_img" src="' . SSQUIZ_URL . 'assets/right.png' . '"/><span class="help-ok">';
				else {
					echo '<img class="check_img" src="' . SSQUIZ_URL . 'assets/right.png' . '"/><span">';
					$correct = false;
				}
			else {
				if( true == $answers[$i]->correct ) {
					echo '<img class="check_img" src="' . SSQUIZ_URL . 'assets/wrong.png' . '"/><span class="help-wrong">';
					$correct = false;
				}
				else
					echo '<img class="check_img" src="' . SSQUIZ_URL . 'assets/wrong.png' . '"/><span>';
				
			}
			echo $question->answers[$i]->answer . '</span></br>';
		}
	} 

	$output = ob_get_contents();
	$info->results = $output;
	ob_end_clean();

	if ( true == $correct ) {
		$info->questions_right++;
		$question->correct = true;
		$temp = "<div class='alert alert-success'>Question #{$info->questions_counter}: " . __("You were right!") . "</div>";
	} else {
		$temp = "<div class='alert alert-error'>Question #{$info->questions_counter}: " . __("You were wrong!") . "</div>";
		$question->correct = false;
	}
	return $temp;
}

function ssquiz_print_question( &$current_question, &$info, $page_responses = array() ) { 
	ob_start();
	$number = $info->questions_counter + 1;
	echo '<div class="ssquiz_question">';
	echo "<strong>" . __("Question") . (( $info->all ) ? " $number" : " $number/{$info->total_questions}" ) . ":</strong><br />";
	echo apply_filters( 'the_content', $current_question->question );
	
	if ( $current_question->type == 'fill' ) {
		$pre_answer = count($page_responses)>0?$page_responses[0]->answer:'';
		$input = '<input type="text" name="ssquiz_answer" class="ssquiz_answer" value="'.$pre_answer.'" style="width: 130px;" />';
			echo $input . '</br>';
		$run_js = '<script>jQuery.fn.run_standard_types();</script>';
	}
	if( $current_question->type == 'single' || $current_question->type == 'multi' ) {
		if( true == $info->arandom )
			shuffle($current_question->answers);
		foreach ( $current_question->answers as $answer ) {
			$pre_answer = isChecked($answer->answer, $page_responses)?'checked="checked"':'';
			if ($current_question->type == 'single' )
				echo '<input type="radio" name="ssquiz_answer'.$number.'" class="ssquiz_answer" '.$pre_answer.' /><span class="ssquiz_answer_span">'
					. $answer->answer . '</span></br>';
			else
				echo '<input type="checkbox" name="ssquiz_answer'.$number.'" class="ssquiz_answer" '.$pre_answer.' /><span class="ssquiz_answer_span">'
					. $answer->answer . '</span></br>';
		}
		$run_js = '<script>jQuery.fn.run_standard_types();</script>';
	}

	if ( ! $info->all )
		echo $run_js;
	echo '</div>';
	$output = ob_get_contents();
	ob_end_clean();
	return $output;
}

function isChecked($answer_value, $history_answers){
	foreach($history_answers as $answer){
		if(strcmp($answer_value, $answer->answer) == 0)
			return $answer->correct;
	}
	return false;
}

function ssquiz_finish( &$finish_screen, &$status, &$info ) {
	global $wpdb;
	$settings = get_option( 'ssquiz_settings' );
	
	if($status->exit){
		$finish_screen .= '<div class="ssquiz_finish">';
		$finish_screen .= '<h2>Your progress has been successfully saved! You can return back and continue later.</h2>';
		$finish_screen .= '</div>';
	}else{
		$finish_screen .= '<div class="ssquiz_finish">' . $settings->finish_template;
		ssquiz_tag_replace($finish_screen, $info, 'finish');
		$finish_screen .= '</div>';
	}
	
	ob_start();
	if ( ! isset ( $info->not_correct ) && $info->questions_counter > 0 ) {
		echo '<div class="history_list">';
		if ( $info->all ) {
			$i = $info->questions_counter -1;
			echo "<a href='#' id='ssquiz_$i' class='ssquiz_btn ssquiz_get_results' onclick='jQuery.fn.history_walk($i);return false;' style='margin-top: 10px;'>" 
				. __("Get Results", 'ssquiz') . '</a>';
		} 
		else {
			echo '<h4 style="margin: 7px;">' . __("Answered questions") . '</h4>';
			for ( $i = 1; $i < $info->current_page; $i++ ) {
				echo "<a href='#' id='ssquiz_$i' class='ssquiz_btn' onclick='jQuery.fn.history_walk($i);return false;'>". ( $i ) ."</a>";
			}
		}
		echo "<div>
				<a href='#' class='ssquiz_btn ssquiz_back' onclick='jQuery.fn.history_walk(-1);return false;' style='margin-top: 10px; display:none;'>" . __("Back", 'ssquiz') . '</a>
			</div>
		</div>';
	}
	$output = ob_get_contents();
	ob_end_clean();
	$finish_screen .= $output;	

	$temp = new stdClass();
	$temp->user_name = $info->user->name;
	$temp->user_email = $info->user->email;
	$temp->time_spent = time() - $info->started;
	$wpdb->insert("{$wpdb->base_prefix}ssquiz_history", 
					array( 
						'user_id' => $info->user->id,
						'quiz_id' => $info->quiz->id,
						'meta' => serialize( $temp ),
						'answered'=> $info->questions_counter,
						'correct' => $info->questions_right,
						'total' => $info->total_questions
					), 
					array( '%d', '%d', '%s', '%d', '%d', '%d' ) );

	if( $info->questions_counter == $info->total_questions ) {
		// API
		$percent = intval(strval($info->questions_right / $info->questions_counter * 100 ) ); //$info->questions_counter !=1
		do_action( 'ssquiz_finished', $info->quiz->id, $info->user->id, $info->questions_right, $info->total_questions );
		
		//Sending email to user
		if( !$status->exit && $settings->user_will_receive && $info->user->email > '' ) {
			ssquiz_tag_replace($settings->user_email_subject, $info, 'email_subject');
			ssquiz_tag_replace($settings->user_email_template, $info, 'user_email');
			add_filter( 'wp_mail_content_type', 'ono_set_html_content_type' );
			wp_mail( $info->user->email, $settings->user_email_subject, $settings->user_email_template );
			remove_filter( 'wp_mail_content_type', 'ono_set_html_content_type' );
		}
	
		//Sending email to teacher
		if( !$status->exit && $settings->teacher_will_receive && $settings->teacher_email_address > '' ) {
			ssquiz_tag_replace($settings->teacher_email_subject, $info, 'email_subject');
			ssquiz_tag_replace($settings->teacher_email_address, $info, 'teacher_email');
			ssquiz_tag_replace($settings->teacher_email_template, $info, 'teacher_email');
			add_filter( 'wp_mail_content_type', 'ono_set_html_content_type' );
			wp_mail( $settings->teacher_email_address, $settings->teacher_email_subject, $settings->teacher_email_template );
			remove_filter( 'wp_mail_content_type', 'ono_set_html_content_type' );
		}
	}
	$temp_screen = ssquiz_add_hidden($finish_screen, $status, $info );
	$wpdb->update($wpdb->base_prefix.'self_ssquiz_response_history',array('finish_screen'=>base64_encode(gzcompress(serialize($temp_screen)))),array('user_id'=>$info->user->id,'quiz_id'=>$info->quiz->id),array('%s'),array('%d','%d'));
	return $temp_screen;
}

function ssquiz_tag_replace( &$screen, &$info, $state ) {
	$search = array();
	$replace = array();
	$temp_var = ( $info->questions_counter == 0 ) ? -1 : $info->total_questions;
	$percent = intval(strval($info->questions_right / $temp_var * 100 ) );
	
	switch ( $state ) {
		case 'teacher_email':
			array_push($search, '%%EMAIL%%' );
			array_push($replace, $info->user->email );
		case 'user_email':
		case 'finish':
			array_push($search, '%%NUMBER%%', '%%TOTAL%%', '%%CORRECT%%', '%%PERCENT%%' );
			array_push($replace, $info->quiz->id, $info->questions_counter, $info->questions_right, $percent );
		case 'start':
			array_push($search, '%%DESCRIPTION%%', '%%QUESTIONS%%' );
			array_push($replace, $info->quiz->meta->description, $info->total_questions );
		case 'email_subject':
			array_push($search, '%%TITLE%%', '%%NAME%%' );
			array_push($replace, $info->quiz->name, $info->user->name );
	}
	$screen = str_replace( $search, $replace, $screen );
}

function ono_set_html_content_type()
{
	return 'text/html';
}