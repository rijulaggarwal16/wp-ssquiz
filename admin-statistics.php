<?php

function ssquiz_delete_history(){
	global $wpdb;
	if( ! current_user_can(SSQUIZ_CAP) )
		return;

	$id = intval($_REQUEST['id']);
	if ( $id == -1 ) {
		$wpdb->query( "DELETE FROM {$wpdb->base_prefix}ssquiz_history" );
	}
	else if ( $id > 0 )
		$wpdb->query( "DELETE FROM {$wpdb->base_prefix}ssquiz_history WHERE id = $id" );
	ssquiz_print_history( -1 );
	wp_die();
}

add_action('wp_ajax_ssquiz_delete_history', 'ssquiz_delete_history');

function ssquiz_go_to_history_page() {
	$offset_temp = isset( $_REQUEST['offset'] ) ? intval($_REQUEST['offset']) : NULL;
	ssquiz_print_history( $offset_temp );
	wp_die();
}

add_action('wp_ajax_ssquiz_go_to_history_page', 'ssquiz_go_to_history_page');

function ssquiz_filter_history() {
	global $wpdb;
	if( ! current_user_can(SSQUIZ_CAP) )
		return;
		
	$filter = '';
	$temp = get_option( 'ssquiz_history' );
	
	if ( isset($_REQUEST['filter']) ) {
		$filter_temp = like_escape( $_REQUEST['filter'] );
		$filter_temp = $wpdb->escape( $filter_temp );
		if ( $filter_temp > '')
			$temp2 = "AND ( quizzes.name LIKE '%$filter_temp%' OR users.display_name LIKE '%$filter_temp%' ) ";
		else
			$temp2 = '';
		$filter .= $temp2;
		$temp->filter = $temp2;
	}
	else
		$filter .= $temp->filter;

	if ( isset($_REQUEST['completed']) ) {
		if ('true' == $_REQUEST['completed'])
			$temp2 = "AND history.correct = history.total";
		else
			$temp2 = '';
		$filter .= $temp2;
		$temp->competed = $temp2;
	}
	else
		$filter .= $temp->competed;
	
	$temp->f = $filter;
	update_option( 'ssquiz_history', $temp );
	ssquiz_print_history( -1, $filter );
	wp_die();
}

add_action('wp_ajax_ssquiz_filter_history', 'ssquiz_filter_history');

function ssquiz_print_history( $offset, $filter = '' ) {
	global $wpdb;
	if( ! current_user_can(SSQUIZ_CAP) )
		return;

$temp = get_option( 'ssquiz_history' );
	if ( $filter == '' ) {
		$filter = $temp->f;
	} else if ( $filter == 'clear' ) {
		$filter = '';
		$temp->filter = '';
		$temp->competed = '';
		$temp->f = '';
		update_option( 'ssquiz_history', $temp );
	}
	
	if ( $offset === NULL ) $offset = -1;
	if ( $offset == -1 ) $offset = 0;
	$max = 20;
	$sql = "
		SELECT count(*) FROM 
		{$wpdb->base_prefix}ssquiz_history as history 
		LEFT OUTER JOIN {$wpdb->base_prefix}ssquiz_quizzes as quizzes 
		ON (history.quiz_id = quizzes.id)
		LEFT OUTER JOIN {$wpdb->base_prefix}users as users
		ON (history.user_id = users.id)";
	$total = $wpdb->get_var( $sql );
	$total1 = $total;
	if ( $filter > '' ) {
		$total_filtered = $wpdb->get_var( $sql . ' WHERE 1 ' . $filter);
		$total1 = $total_filtered;
	}
	$entries = $wpdb->get_results( "
		SELECT history.meta as meta, history.id as id, quizzes.name as name, history.correct as correct, 
			history.total as total, history.answered as answered, history.timestamp as timestamp, 
			history.user_id as user_id, history.quiz_id as quiz_id, users.display_name as user_name
		FROM {$wpdb->base_prefix}ssquiz_history as history 
			 LEFT OUTER JOIN {$wpdb->base_prefix}ssquiz_quizzes as quizzes 
			 ON (history.quiz_id = quizzes.id)
			 LEFT OUTER JOIN {$wpdb->base_prefix}users as users
			 ON (history.user_id = users.id)
		WHERE 1 $filter
		ORDER BY history.timestamp DESC
		LIMIT $offset, $max
	" );
	?>
	<div class="pagination pagination-centered" style="margin: 0 auto 10px 0;">
		<ul>
			<li class="<?php if ($offset == 0) echo 'disabled';?>">
				<a href="#" onclick="jQuery.fn.go_to_history_page(<?php echo $offset - $max ?>); return false"><?php echo __("Prev", 'ssquiz'); ?></a></li>
			<?php
			for ( $i = 0; $i * $max < intval($total1); $i++ ) { 
				?><li class="<?php echo ($offset == $i * $max) ? 'disabled' : '' ?>" >
					<a href='#' onclick='jQuery.fn.go_to_history_page(<?php echo $i * $max ?>); return false'><?php echo $i+1 ?></a>
				</li><?php 
				}
			?><li class="<?php if ( intval($total1) <= $offset + $max ) echo 'disabled'; ?>">
				<a href="#" onclick="jQuery.fn.go_to_history_page(<?php echo $offset + $max ?>); return false"><?php echo __("Next", 'ssquiz'); ?></a></li>	
		</ul>
	</div>
	
	<ul id="ssquiz_sortable">
		<li class='ui-state-default ui-state-disabled row'>
		<strong>
			<div class="sdiv4">User's name</div>
			<div class="sdiv4">User's email</div>
			<div class="sdiv4">Quiz</div>
			<div class="sdiv1">Answered</div>
			<div class="sdiv1">Correct</div>
			<div class="sdiv4">Time</div>
		</strong>
		</li>
	<?php
	foreach ( $entries as $entry ) {
		$entry->meta = unserialize( $entry->meta );
		$user_name = ( $entry->user_id != '0' ) ? $entry->user_name : $entry->meta->user_name;
		?>
		<li class='ui-state-default row<?php if( $entry->correct == $entry->total ) echo ' ssquiz_right';?>'>
			<div class="sdiv4">
				<?php echo ($user_name > '') ? $user_name : '&nbsp;'; ?>
			</div>
			<div class="sdiv4"><?php echo ($entry->meta->user_email > '') ? $entry->meta->user_email : '&nbsp;'; ?></div>
			<div class="sdiv4"><?php echo ($entry->name > '') ? $entry->name : '&nbsp;'; ?></div>
			<div class="sdiv1"><?php echo $entry->answered; ?></div>
			<div class="sdiv1"><?php echo $entry->correct; ?></div>
			<div class="sdiv4"><?php echo date( "F j, Y, g:i a", strtotime( $entry->timestamp ) ); ?></div>
			<div class="sdiv3">
				<a href="#" onclick="jQuery.fn.crud_history(<?php echo $entry->id; ?>);return false;"><?php echo __("delete", 'ssquiz'); ?></a>
			</div>
		</li>
		<?php
	}
	?>
	</ul> <!-- #ssquiz_sortable -->
	<div class="ssquiz_inforamtion">
		<span> Entries: <?php echo $total; ?></span>
	<?php
	if ( $filter > '' )
		echo '<span style="margin-left:10px;"> Filtered to: ' . $total_filtered . '</span>
	</div>';
}

function draw_statistics_page() {
	?>
	<div class="ono_wrap">
		<div id="wrap">
			<h2 class="heading"><?php echo __("SSQuiz Statistics", 'ssquiz'); ?></h2>
			
			<?php if( function_exists('ssquiz_print_aux_stats') ) ssquiz_print_aux_stats(); ?>
			
			<legend style="margin-top: 20px;"><i class="icon-group"></i> Quiz users</legend>
			
			<div class="cf" style="margin: 30px 0 0 10px; position: relative;">
				<div id="display_questions" style="float:left;">
					<label style="display:inline-block;left: -10px; position: relative;"><?php echo __("Find: ", 'ssquiz'); ?></label>
					<input type="text" id="find_name" placeholder="User/Quiz name" />
					<input type="checkbox" id="find_completed" style="position: relative;top: -6px;margin-left: 20px;" />
					<label style="display:inline-block;left: 15px; position: relative;"><?php echo __("only passed", 'ssquiz'); ?></label>
				</div>

				<div style="float:right;">
					<a class="btn ssquiz_ask_delete_history" href="#">
						<i class="icon-trash"></i> <?php echo __("Delete All Entries", 'ssquiz'); ?>
					</a>
					<div class="confirm_delete_history" style="right: 0px;top: -5px; display:none;">
						<a href="#" class="btn btn-danger ssquiz_delete_history" onclick="jQuery.fn.crud_history( -1 ); return false;">Confirm</a>
						<a href="#" class="btn btn-success ssquiz_cancel_history">Cancel</a>
						<span style="margin: 15px;">Are you sure you want to clear history of using SSQuiz?</span>
					</div>
				</div>
			</div>
			
			<div id="ssquiz_history_container">
				<?php ssquiz_print_history( -1, 'clear' ); ?>
			</div>
		</div> <!-- #wrap -->
	</div>
	
	<?php
	if(function_exists('print_user_info')) 
		print_user_info();
	ssquiz_print_footer();
}