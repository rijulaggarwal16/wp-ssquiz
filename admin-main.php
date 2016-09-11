<?php

function ssquiz_crud_quiz(){
	global $wpdb;
	if( ! current_user_can(SSQUIZ_CAP) )
		return;

	$object_type = $_REQUEST['object'];
	$action = $_REQUEST['type_action'];
	$id = intval($_REQUEST['id']);
	$info = json_decode(stripslashes($_REQUEST['info']));
	$table = ( $object_type == 'quiz' ) ? "{$wpdb->base_prefix}ssquiz_quizzes" : "{$wpdb->base_prefix}ssquiz_questions";
	
	if ( $action == 'quiz_change' ) {
		ssquiz_print_questions( -1, $id );
		wp_die();
	}
	
	if( $action == 'reorder' ) {
		$number = intval($info->number);
		$quiz_id = intval($info->quiz_id);
		if ( $info->start > $info->finish ) {
			$distance = $number - ($info->start - $info->finish);
			if( $distance > 0) {
				$wpdb->query( "UPDATE $table SET number = number + 1 WHERE quiz_id = $quiz_id AND number BETWEEN $distance AND $number" );
				$wpdb->query( "UPDATE $table SET number = $distance WHERE  id = $id" );
			}
		} else {
			$distance = $number + $info->finish - $info->start;
			$temp = $wpdb->get_var( "SELECT count(*) FROM {$wpdb->base_prefix}ssquiz_questions WHERE quiz_id = $quiz_id" );
			if( $distance <= intval( $temp ) ) {
				$wpdb->query( "UPDATE $table SET number = number - 1 WHERE quiz_id = $quiz_id AND number BETWEEN $number AND $distance" );
				$wpdb->query( "UPDATE $table SET number = $distance WHERE  id = $id" );
			}
		}

		ssquiz_print_questions( -1, -1 );
		wp_die();
	}
	
	if( $action == 'delete') {
		if($object_type == 'question') {
			$wpdb->query( "UPDATE {$wpdb->base_prefix}ssquiz_questions SET number = number - 1 
							WHERE quiz_id = $quiz_id AND number > (SELECT number FROM {$wpdb->base_prefix}ssquiz_questions WHERE id = $id)" );
		} else { // quiz
			$wpdb->query( "DELETE FROM {$wpdb->base_prefix}ssquiz_questions WHERE quiz_id = $id;" );
		}
		$wpdb->query( "DELETE FROM $table WHERE id = $id;" );
		ssquiz_print_questions( -1, -1 );
		wp_die();
	}

 	if ( $action == 'read' ) {
		$object = $wpdb->get_row( "SELECT * FROM $table WHERE id = $id" );
		if ( $object_type == 'quiz' ) {
			$object->meta = unserialize( $object->meta );
		} else { // question
			$object->meta = unserialize( $object->meta );
			$object->answers = unserialize( $object->answers );
			
		}
		wp_die(json_encode($object));
	}

	// insert or update
	if($object_type == 'quiz') {
		//$type = implode('_', array( $info->type, $info->tag_free ) );
		$insert = array( 
				'name' => $info->name, 
				//'type' => $type, 
				'meta' => serialize( $info->meta ));
		$format = array( '%s', '%s', '%s' );
		
		if( $action == 'edit' && $id < 1 ) {
			$wpdb->insert( $table, $insert, $format );
		} else {
			$wpdb->update( $table, $insert, array( 'ID' => $id ), $format );
		}
 	}

	if($object_type == 'question') {
		$type = $info->type;
		$answers = $info->answers;
		$insert = array( 
				'type' => $type,
				'question' => $info->question,
				'answers' => serialize( $answers ),
				'meta' => serialize( $info->meta ) );
		$format = array( '%s', '%s', '%s', '%s' );
		
		if( $action == 'edit' ) {
			$wpdb->update( $table, $insert, array( 'ID' => $id ), $format );
		} 
		elseif ( $action == 'edit_new' ) {
			$number = 1 + intval( $wpdb->get_var( "SELECT MAX(number) FROM {$wpdb->base_prefix}ssquiz_questions WHERE quiz_id = $id" ) );
			$insert = array( 
					'quiz_id' => $id,
					'number' => $number,
					'type' => $type,
					'question' => $info->question,
					'answers' => serialize( $answers ),
					'meta' => serialize( $info->meta ) );
			$format = array( '%d', '%d', '%s', '%s', '%s', '%s' );
			$wpdb->insert( $table, $insert, $format );
		}
	}
	ssquiz_print_questions( -1, -1 );
	wp_die();
}

add_action('wp_ajax_ssquiz_crud_quiz', 'ssquiz_crud_quiz');

function print_answers(&$answers, $type) {
	$temp_answers = array();
	if ( 'fill' == $type ) {
		echo implode( ', ', $answers );
	}
	if ( 'single' == $type || 'multi' == $type ) {
		foreach ( $answers as $answer )
			array_push( $temp_answers, (
						($answer->correct == true) ? "<span class='text-success'>" : "<span class='text-error'>") . "{$answer->answer}</span>");
		echo implode( ', ', $temp_answers );
	}
}

add_action('wp_ajax_ssquiz_go_to_page', 'ssquiz_go_to_page');

function ssquiz_go_to_page() {
	$offset_temp = isset( $_REQUEST['offset'] ) ? intval($_REQUEST['offset']) : NULL;
	$quiz_id_temp = isset( $_REQUEST['quiz_id'] ) ?  intval($_REQUEST['quiz_id']) : NULL;
	ssquiz_print_questions( $offset_temp, $quiz_id_temp );
	wp_die();
}

function ssquiz_print_questions( $offset, $quiz_id ) {
	global $wpdb;
	if( ! current_user_can(SSQUIZ_CAP) )
		return;
		
	if( isset( $_GET['quiz_id'] ) && $_GET['quiz_id'] > '' )
		$quiz_id = intval( $_GET['quiz_id'] );

	if ( $offset === NULL ) $offset=-1;
	if ( $quiz_id === NULL ) $quiz_id=-1;

	$temp = get_option( 'ssquiz_page' );
	if ( $quiz_id == -1 )
		$quiz_id = $temp->quiz_id;
	if ( $temp->quiz_id != $quiz_id )
		$offset = 0;
	else if ( $offset == -1 )
		$offset = $temp->offset;

	
	$temp->offset = $offset;
	$temp->quiz_id = $quiz_id;
	update_option( 'ssquiz_page', $temp );
	
	$total_questions = $wpdb->get_var( "SELECT count(*) FROM {$wpdb->base_prefix}ssquiz_questions" );
	$total_quizzes = $wpdb->get_var( "SELECT count(*) FROM {$wpdb->base_prefix}ssquiz_quizzes" );
	
	$max = 20;
	$temp = ( $quiz_id != 0 ) ? "WHERE quizzes.id = $quiz_id" : '';

	$total_questions_in_list = $wpdb->get_var( "SELECT count(*) 
				FROM {$wpdb->base_prefix}ssquiz_quizzes as quizzes LEFT JOIN {$wpdb->base_prefix}ssquiz_questions as questions 
				ON questions.quiz_id = quizzes.id $temp" );

	$sql = "SELECT quizzes.id as quiz_id, quizzes.name as quiz_name, quizzes.meta as quiz_meta, questions.number as number, questions.type as type,
					questions.question as question, questions.meta as questions_meta, questions.answers as answers, questions.id as id
				FROM {$wpdb->base_prefix}ssquiz_quizzes as quizzes LEFT JOIN {$wpdb->base_prefix}ssquiz_questions as questions 
				ON questions.quiz_id = quizzes.id
				$temp
				ORDER BY quiz_id, number ASC LIMIT $offset, $max;";
	$questions = $wpdb->get_results( $sql );
	$quiz_id = -1;

	?>
	<div class="pagination pagination-centered" style="margin: 0 auto 10px 0;">
		<ul>
			<li class="<?php if ($offset==0) echo 'disabled';?>">
				<a href="#" onclick="jQuery.fn.go_to_page(<?php echo $offset - $max ?>)"><?php echo __("Prev", 'ssquiz'); ?></a></li>
			<?php
			for ( $i = 0; $i * $max < intval($total_questions_in_list); $i++ ) { 
				?><li class="<?php echo ($offset == $i * $max) ? 'disabled' : '' ?>" >
					<a href='#' onclick='jQuery.fn.go_to_page(<?php echo $i * $max ?>)'><?php echo $i+1 ?></a>
				</li><?php 
				}
			?><li class="<?php if ( intval($total_questions_in_list) <= $offset + $max ) echo 'disabled'; ?>">
				<a href="#" onclick="jQuery.fn.go_to_page(<?php echo $offset + $max ?>)"><?php echo __("Next", 'ssquiz'); ?></a></li>	
		</ul>
	</div>
	<ul id="ssquiz_sortable">
	<?php
	foreach ( $questions as $question ) {
		//print quiz
		try {
		if( $question->quiz_id != $quiz_id ) {
			
			?>	<li class='ui-state-default ui-state-disabled row'>
				<div class="sdiv1"><span class="badge badge-info">id=<?php echo $question->quiz_id; ?></span></div>
				<div class="sdiv2" style="font-style: italic;"><?php echo $question->quiz_name; ?></div>
				<div class="sdiv2">
					<a class="btn btn-primary" href="#" onclick="jQuery.fn.crud_quiz( 'question', 'add', <?php echo $question->quiz_id;?> );return false;"
						data-toggle="modal" data-target="#ssquiz_question_modal">
						<i class="icon-plus-sign"></i> <?php echo __("Add Question", 'ssquiz'); ?></a>
					<a class="btn btn-primary"  onclick="jQuery.fn.crud_quiz( 'quiz', 'read', <?php echo $question->quiz_id;?> );return false;"
						data-toggle="modal" data-target="#ssquiz_quiz_modal">
						<i class="icon-pencil"></i> <?php echo __("Edit", 'ssquiz'); ?></a>
				</div>
			</li>
			<?php
			$quiz_id = $question->quiz_id;
		}
		//print question 
		if ( ! isset($question->id ) )
			continue;
		$temp = strip_tags( $question->question );
		if (strlen($temp) > 100)
			$temp = substr($temp, 0, 97) . '...';
			
		?>	<li class='ui-state-default row' 
					data-id="<?php echo $question->id; ?>" 
					data-quiz_id="<?php echo $question->quiz_id; ?>"
					data-number="<?php echo $question->number; ?>">
			<div class="sdiv1"><?php echo $question->number; ?></div>
			<div class="sdiv2"><?php echo $temp; ?><span class="label"><?php echo $question->type; ?></span></div>
			<div class="sdiv2"><?php print_answers(unserialize($question->answers), $question->type); ?></div>
			<div class="sdiv3">
			<a class="btn btn-primary" href="#" onclick="jQuery.fn.crud_quiz( 'question', 'read', <?php echo $question->id ?> );return false;"
				data-toggle="modal" data-target="#ssquiz_question_modal">
				<i class="icon-pencil"></i> <?php echo __("Edit", 'ssquiz'); ?></a>
			</div>
		</li>
		<?php
		} catch (Exception $e) { echo $e->getMessage(), "\n";}
	}
	?>
	</ul> <!-- #ssquiz_sortable -->
	<div class="ssquiz_inforamtion">
		<span>Total Quizzes: <?php echo $total_quizzes; ?></span>
		<span style="margin-left:10px;"> Total Questions:  <?php echo $total_questions; ?></span>
	</div>
	<?php
	return;
}

function ssquiz_list_quizzes( $all = false ) {
	global $wpdb;
	$quizzes = $wpdb->get_results( "SELECT id, name FROM {$wpdb->base_prefix}ssquiz_quizzes ORDER BY id ASC" );
	
	$temp = get_option( 'ssquiz_page' );
	
	if( $_GET['quiz_id'] > '' )
		$temp->quiz_id = intval( $_GET['quiz_id'] );
	
	if ( true == $all ) {
		echo "<option value='0' $selected>[All]</option>";
	}
	foreach ( $quizzes as $quiz ) {
		$selected = ( $temp->quiz_id == $quiz->id ) ? 'selected' : '';
		echo "<option value='{$quiz->id}' $selected>{$quiz->name}</option>";
	}
}

function draw_main_page() {
	?>
	<div class="ono_wrap ssquiz_manager">
		<div id="wrap">
			<h2 class="heading"><?php echo __("Manage Quizzes", 'ssquiz'); ?></h2>

			<div class="cf" style="margin: 30px 0 0 10px">
				<div id="display_questions" style="float:left; width:30%;">
					<label style="display:inline-block;left: -10px; position: relative;"><?php echo __("Filter quizzes: ", 'ssquiz'); ?></label>
					<select style="display:inline-block" id="select_quiz">
						<?php ssquiz_list_quizzes(true); ?>
					</select>
				</div>

				<div style="float:right; margin-right: 30px;">
					<a class="btn btn-primary" href="#" onclick="jQuery.fn.crud_quiz( 'quiz', 'add', 0 );return false;"
						data-toggle="modal" data-target="#ssquiz_quiz_modal">
						<i class="icon-plus-sign"></i> <?php echo __("Add Quiz", 'ssquiz'); ?>
					</a>
				</div>
			</div>
			

			<div id="ssquiz_questions_container">
				<?php ssquiz_print_questions( -1, -1 ); ?>
			</div>
			
		</div>
	</div>
	<?php ssquiz_print_footer(); ?>
	
	<div class="ono_wrap">
		<div id="ssquiz_quiz_modal" class="modal hide fade">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h3 class="modal_title">Edit Quiz</h3>
			</div>
			<div class="modal-body">
			<?php ssquiz_quiz_template(); ?>
			</div>
			<div class="modal-footer">
				<div class="confirm_delete">
					<span>It will delete all questions belonging to this quiz. Continue?</span>
					<a href="#" class="btn btn-danger ssquiz_delete" data-dismiss="modal" aria-hidden="true">Confirm</a>
					<a href="#" class="btn btn-success ssquiz_cancel">Cancel</a>
				</div>
				<a href="#" class="btn btn-danger ssquiz_ask_delete">Delete</a>
				<span style="width: 80%;display: inline-block;">&nbsp;</span>
				<a href="#" class="btn btn-primary ssquiz_save" data-dismiss="modal" aria-hidden="true">Save</a>
			</div>
		</div>
		
		<div id="ssquiz_question_modal" class="modal hide fade">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h3 class="modal_title">Edit Question</h3>
			</div>
			<div class="modal-body">
			<?php ssquiz_question_template(); ?>
			</div>
			<div class="modal-footer">
				<a href="#" class="btn btn-danger ssquiz_delete" data-dismiss="modal" aria-hidden="true">Delete</a>
				<span style="width: 80%;display: inline-block;">&nbsp;</span>
				<a href="#" class="btn btn-primary ssquiz_save" data-dismiss="modal" aria-hidden="true">Save</a>
			</div>
		</div>
	</div>
<?php
}

function ssquiz_quiz_template() {
	?>
	<div class="cf">
		<form>
			<label>Quiz Title</label>
			<input type="text" class="span3" id="quiz_name" style="width: 270px;">
			<label>Description</label>
			<?php wp_editor('', 'description'); ?>
			<br/>
			<label>Pre-requisite</label>
			<select id="prerequisites">
				<option value='0' selected>--</option>
				<?php ssquiz_list_quizzes(false); ?>
			</select>
			<br/>
			<label>Credits</label>
			<input type="number" class="span3" id="quiz_credits">
		</form>
	</div>
	<?php
}

function ssquiz_question_template() {
	?>
	<form>
	<?php wp_editor('', 'ssquiz_question' ); ?>

	<div id="tab" class="btn-group" data-toggle="buttons-radio" style="padding: 25px 0 10px;">
		<a href="#fill" class="btn btn-info active" data-toggle="tab"><?php echo __("Fill", 'ssquiz'); ?></a>
		<a href="#choise" class="btn btn-info" data-toggle="tab"><?php echo __("Choise", 'ssquiz'); ?></a>
	</div>
	</legend>
	<div class="tab-content">
		<div class="tab-pane active" id="fill">
			<span class="ssquiz_answer">
				<input type="text" class="answer" placeholder="Answer" style="width: 300px;">
				<br/><em>User's answer will be case insensitive. You can use '|' as a delimiter, e.g., 'Color|Colour'</em>
			</span>
		</div>
		<div class="tab-pane" id="choise">
			<div class="ssquiz_answers">
				<span class="ssquiz_answer ssquiz_blank" style="display:none">
					<input type="text" class="answer" placeholder="Answer">
					<input type="checkbox" class="correct"> correct
				</span>
				<a class="btn btn-primary ssquiz_add_answer" id="choise_add" href="#">Add answer</a>
			</div>
		</div>
	</div> <!-- .tab-content -->
	</form>
	<?php
}