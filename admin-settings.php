<?php

function ssquiz_crud_template(){
	if( ! current_user_can(SSQUIZ_CAP) )
		return;
	
	$subjects = json_decode(stripslashes($_REQUEST['subject']));
	$type = $_REQUEST['type'];
	
	$settings = get_option( 'ssquiz_settings' );
	switch ($type) {
	case 'btn_report':
		$settings->pdf_template = balanceTags($subjects->pdf_template);
		break;
	case 'btn_teacher_email':
		$settings->teacher_email_address = wp_kses($subjects->teacher_email_address, array());
		$settings->teacher_email_subject = wp_kses($subjects->teacher_email_subject, array());
		$settings->teacher_email_template = balanceTags($subjects->teacher_email_template);
		$settings->teacher_will_receive = $subjects->teacher_will_receive;
		break;
	case 'btn_user_email':
		$settings->user_email_subject = wp_kses($subjects->user_email_subject, array());
		$settings->user_email_template = balanceTags($subjects->user_email_template);
		$settings->user_will_receive = $subjects->user_will_receive;
		break;
	case 'btn_finish':
		$settings->finish_template = balanceTags($subjects->finish_template);
		break;
	case 'btn_start':
		$settings->start_template = balanceTags($subjects->start_template);
		break;
	}
	
	update_option( 'ssquiz_settings', $settings );
}

add_action('wp_ajax_ssquiz_crud_template', 'ssquiz_crud_template');

function draw_settings_page() {
	$settings = get_option( 'ssquiz_settings' );
	?>
	<div class="ono_wrap">
		<div id="wrap" class="ssquiz_settings">

			<h2 class="heading"><?php echo __("SSQuiz Settings", 'ssquiz'); ?></h2>
	
			<div class="bs-docs-sidebar">
				<ul class="nav nav-list bs-docs-sidenav affix">
					<!--<li><a href="#general_settings">General Settings</a></li>-->
					<li><a href="#start_screen"><?php echo __("Start Screen", 'ssquiz')?></a></li>
					<li><a href="#finish_screen"><?php echo __("Finish Screen", 'ssquiz')?></a></li>
					<li><a href="#email_template"><?php echo __("Email Template", 'ssquiz')?></a></li>
					<li><a href="#teachers_template"><?php echo __("Teacher's Template", 'ssquiz')?></a></li>
				</ul>
			</div>
			
			<!--
			<section>
			<span id="general_settings"></span>
			<form>
				<fieldset>
					<legend><i class="icon-align-justify"></i> <?php //echo __("General Settings", 'ssquiz'); ?></legend>
				</fieldset>
			</form>
			</section>
			-->
			
			<section>
			<span id="start_screen"></span>
			<form>
				<fieldset>
				<legend><i class="icon-align-justify"></i> <?php echo __("Start Screen", 'ssquiz'); ?>
					<small><?php echo __("Folowing content will appear when quiz is loaded on page", 'ssquiz'); ?></small>
				</legend>
				<?php wp_editor($settings->start_template, "start_template"); ?>
				<dl class="dl-horizontal">
					<dt>%%TITLE%%</dt><dd><?php echo __("Title of the quiz", 'ssquiz')?></dd>
					<dt>%%DESCRIPTION%%</dt><dd><?php echo __("Description of the quiz", 'ssquiz')?></dd>
					<dt>%%QUESTIONS%%</dt><dd><?php echo __("Total number of questions", 'ssquiz')?></dd>
				</dl>
				<button type="submit" class="btn btn-primary" id="btn_start" onclick="jQuery.fn.crud_template('btn_start'); return false;">Save</button>
				</fieldset>
			</form>
			</section>
			
			<section>
			<span id="finish_screen"></span>
			<form>
				<fieldset>
				<legend><i class="icon-align-justify"></i> <?php echo __("Finish Screen", 'ssquiz'); ?>
					<small><?php echo __("Folowing content will appear when quiz is competed or exited", 'ssquiz'); ?></small>
				</legend>
				<?php wp_editor($settings->finish_template, "finish_template"); ?>
				<dl class="dl-horizontal">
					<dt>%%NAME%%</dt><dd><?php echo __("User's name", 'ssquiz')?></dd>
					<dt>%%NUMBER%%</dt><dd><?php echo __("Quiz's ID", 'ssquiz')?></dd>
					<dt>%%TITLE%%</dt><dd><?php echo __("Title of the quiz", 'ssquiz')?></dd>
					<dt>%%DESCRIPTION%%</dt><dd><?php echo __("Description of the quiz", 'ssquiz')?></dd>
					<dt>%%TOTAL%%</dt><dd><?php echo __("Number of answered questions", 'ssquiz')?></dd>
					<dt>%%CORRECT%%</dt><dd><?php echo __("Number of correct answers", 'ssquiz')?></dd>
					<dt>%%PERCENT%%</dt><dd><?php echo __("Percent of correct answered over total questions", 'ssquiz')?></dd>
				</dl>
				<button type="submit" class="btn btn-primary" id="btn_finish" onclick="jQuery.fn.crud_template('btn_finish'); return false;">Save</button>
				</fieldset>
			</form>
			</section>
			
			<section>
			<span id="email_template"></span>
			<form>
				<fieldset>
				<legend><i class="icon-align-justify"></i> <?php echo __("Email Template to user", 'ssquiz'); ?>
					<small><?php echo __("Folowing content can be sent as a message to user", 'ssquiz'); ?></small>
				</legend>
				<label><?php echo __("Subject", 'ssquiz')?></label>
				<input type="text" style="width:500px;" id="user_email_subject" value="<?php echo esc_attr($settings->user_email_subject); ?>">
				<dl class="dl-horizontal">
					<dt>%%NAME%%</dt><dd><?php echo __("User's name", 'ssquiz')?></dd>
					<dt>%%TITLE%%</dt><dd><?php echo __("Title of the quiz", 'ssquiz')?></dd>
				</dl>
				<?php wp_editor($settings->user_email_template, "user_email_template"); ?>
				<dl class="dl-horizontal">
					<dt>%%NAME%%</dt><dd><?php echo __("User's name", 'ssquiz')?></dd>
					<dt>%%NUMBER%%</dt><dd><?php echo __("Quiz's ID", 'ssquiz')?></dd>
					<dt>%%TITLE%%</dt><dd><?php echo __("Title of the quiz", 'ssquiz')?></dd>
					<dt>%%DESCRIPTION%%</dt><dd><?php echo __("Description of the quiz", 'ssquiz')?></dd>
					<dt>%%TOTAL%%</dt><dd><?php echo __("Number of answered questions", 'ssquiz')?></dd>
					<dt>%%CORRECT%%</dt><dd><?php echo __("Number of correct answers", 'ssquiz')?></dd>
					<dt>%%PERCENT%%</dt><dd><?php echo __("Percent of correct answered over total questions", 'ssquiz')?></dd>
					<!--<dt>%%FILE%%</dt><dd><?php //echo __("Url to the report file", 'ssquiz')?></dd>-->
				</dl>
				<label class="checkbox">
					<input type="checkbox" id="user_will_receive" <?php if(true == $settings->user_will_receive) echo 'checked="checked"';?> > User will receive email
				</label>
				<button type="submit" class="btn btn-primary" id="btn_user_email" onclick="jQuery.fn.crud_template('btn_user_email'); return false;">Save</button>
				</fieldset>
			</form>
			</section>
			
			<section>
			<span id="teachers_template"></span>
			<form>
				<fieldset>
				<legend><i class="icon-align-justify"></i> <?php echo __("Teacher's Template", 'ssquiz')?>
					<small><?php echo __("Folowing content can be sent as a message to third person (teacher)", 'ssquiz'); ?></small>
				</legend>
				<label><?php echo __("Email address", 'ssquiz')?></label>
				<input type="text" id="teacher_email_address" value="<?php echo esc_attr($settings->teacher_email_address); ?>">
				<label><?php echo __("Subject", 'ssquiz')?></label>
				<input type="text" style="width:500px;" id="teacher_email_subject" value="<?php echo esc_attr($settings->teacher_email_subject); ?>">
				<dl class="dl-horizontal">
					<dt>%%NAME%%</dt><dd><?php echo __("User's name", 'ssquiz')?></dd>
					<dt>%%TITLE%%</dt><dd><?php echo __("Title of the quiz", 'ssquiz')?></dd>
				</dl>
				<?php wp_editor($settings->teacher_email_template, "teacher_email_template"); ?>
				<dl class="dl-horizontal">
					<dt>%%NAME%%</dt><dd><?php echo __("User's name", 'ssquiz')?></dd>
					<dt>%%EMAIL%%</dt><dd><?php echo __("User's email", 'ssquiz')?></dd>
					<dt>%%NUMBER%%</dt><dd><?php echo __("Quiz's ID", 'ssquiz')?></dd>
					<dt>%%TITLE%%</dt><dd><?php echo __("Title of the quiz", 'ssquiz')?></dd>
					<dt>%%DESCRIPTION%%</dt><dd><?php echo __("Description of the quiz", 'ssquiz')?></dd>
					<dt>%%TOTAL%%</dt><dd><?php echo __("Number of answered questions", 'ssquiz')?></dd>
					<dt>%%CORRECT%%</dt><dd><?php echo __("Number of correct answers", 'ssquiz')?></dd>
					<dt>%%PERCENT%%</dt><dd><?php echo __("Percent of correct answered over total questions", 'ssquiz')?></dd>
					<!--<dt>%%FILE%%</dt><dd><?php //echo __("Url to the report file", 'ssquiz')?></dd>-->
				</dl>
				<label class="checkbox">
					<input type="checkbox" id="teacher_will_receive" <?php if(true == $settings->teacher_will_receive) echo 'checked="checked"';?> > Teacher will receive email
				</label>
				<button type="submit" class="btn btn-primary" id="btn_teacher_email" onclick="jQuery.fn.crud_template('btn_teacher_email'); return false;">Save</button>
				</fieldset>
			</form>
			</section>
			
			<?php /*
			<form>
				<fieldset>
				<legend><i class="icon-align-justify"></i> <?php echo __("Report's Template", 'ssquiz')?></legend>
				<?php wp_editor($settings->pdf_template, "pdf_template", array('textarea_rows' => '10')); ?>
				<dl class="dl-horizontal" id="pdf_template">
					<dt>%%NAME%%</dt><dd><?php echo __("User's name", 'ssquiz')?></dd>
					<dt>%%TITLE%%</dt><dd><?php echo __("Title of the quiz", 'ssquiz')?></dd>
					<dt>%%DESCRIPTION%%</dt><dd><?php echo __("Description of the quiz", 'ssquiz')?></dd>
					<dt>%%TOTAL%%</dt><dd><?php echo __("Number of answered questions", 'ssquiz')?></dd>
					<dt>%%CORRECT%%</dt><dd><?php echo __("Number of correct answers", 'ssquiz')?></dd>
					<dt>%%PERCENT%%</dt><dd><?php echo __("Percent of correct answered over total questions", 'ssquiz')?></dd>
					<dt>%%DATE%%</dt><dd><?php echo __("Percent of correct answered over total questions", 'ssquiz')?></dd>
				</dl>
				<button type="submit" class="btn btn-primary" onclick="jQuery.fn.crud_template('btn_report'); return false;">Save</button>
				</fieldset>
			</form>
			*/
			?>
		</div>
	</div>
	
	<?php
	ssquiz_print_footer();
}