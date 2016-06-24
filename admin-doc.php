<?php

function draw_docs_page() {
	?>
	<div class="ono_wrap">
		<div id="wrap" class="ssquiz_settings">

			<h2 class="heading"><?php echo __("SSQuiz Documentation", 'ssquiz'); ?></h2>
	
			<div class="bs-docs-sidebar">
				<ul class="nav nav-list bs-docs-sidenav affix">
					<li><a href="#general_usage">General Usage</a></li>
					<li><a href="#for_developers">For Developers</a></li>
				</ul>
			</div>

			<section>
			<span id="general_usage"></span>
			<form>
				<fieldset>
					<legend><i class="icon-align-justify"></i> <?php echo __("General Usage", 'ssquiz'); ?></legend>
					<p style="text-indent:40px;">SSQuiz 2.0 is rethought and rewritten compared to previous versions. 
						If you were using previous version, then your SSQuiz data will be converted to a new format on activation. If you're uninstalling plugin, quizzes will stay on database. To delete them, Delete tables with names like <em>'ssquiz'</em> in Wordpress database.</p>
					<p>When answering to questions, user must be connected to internet, as every answer is sent to server and checked there, making cheating harder.</p>
					<p>Quiz accepts empty answers from users.</p>
					<p>API and email sending trigger only if user went through all questions, however quiz saves every attempt to the statistics page.</p>
					<h4>Shortcodes</h4>
					<div>
						<p>You can use following arguments:</p>
						<dl class="dl-horizontal">
							<dt>all</dt><dd>to show all questions on once</dd>
							<dt>not_correct</dt><dd>not showing correct answers after quiz is finished</dd>
							<dt>show_correct</dt><dd>to show weather user ansered correctly after each answer</dd>
							<dt>qrandom</dt><dd>to randomize order of questions</dd>
							<dt>arandom</dt><dd>to randomize order of  answers</dd>
							<dt>name</dt><dd>to request user name at quiz start. For logged in users this field will be pre-filled.</dd>
							<dt>email</dt><dd>to request user email at quiz start and to send him email when quiz is done. For logged in users this field will be pre-filled.</dd>
							<dt>timer</dt><dd>set timer in seconds. For example: timer=12</dd>
							<dt>one_chance</dt><dd>for registered users sets only one attempt to pass test
								To start again you should delete their attempt from quiz's history</dd>
							<dt>total</dt><dd>number of questions to display. It is useful with <em>'qrandom'</em> to change questions each time.</dd>
						</dl>
						<p>For example: <em>[ssquiz id=1 qrandom timer=20]</em> means insert on page quiz with id = 1, with shuffled questions. In 20 seconds after user clicks 'Start' exit will be triggered.</p>
					</div>
					<h4>Admin Interface</h4>
					<div>
						<p>Quiz uses ajax requests for all operations on admin side. So the pages will not reload on each update, making working with quiz more convenient. Max number of questions on page is <em>20</em>.</p>
						<p>On 'Manage quizzes' page selected quiz and page is saved, so when you leaved and then went back it would show the questions you recently had been working with.
						<p><strong>For questions type 'choise': </strong>if you write many answers, but only one of those is correct, then 
							it will be "multiple choice" test, and user will be able to choose only one answer.</p>
						<p>If you checks more than one correct answer to question, then it would be possible, to choose several answers at once.</p>
						<p>While working with quizzes you can:</p>
						<ul style="list-style:square;">
							<li>Leave answers blank to delete them</li>
							<li>Drag question within quiz to change order of questions</li>
							<li>Delete quizzes or questions while <em>editing</em> them. There will be button 'delete' on the buttom.</li>
						</ul>
					</div>
				</fieldset>
			</form>
			</section>
			
			<section>
			<span id="for_developers"></span>
			<form>
				<fieldset>
					<legend><i class="icon-align-justify"></i> <?php echo __("For Developers", 'ssquiz'); ?></legend>
					<h4>API</h4>
					<div>
						<p>You can add following piece of code into your plugin:</p>
						<p style="text-indent:40px;">hook is triggered when SSQuiz is finished by the user</p>
						<pre>
add_action('ssquiz_finished', 'after_quiz_done', 10, 4);

function after_quiz_done( $quiz_id, $user_id, $questions_right, $total_questions )
{
	// do stuff here
}
						</pre>
					</div>
					<h4>Enhancements</h4>
					<div>
						<p>CSS styles that are applied to client side of quiz are written in <em>ssquiz-style.css</em>. 
							You can make some changes to.</p>
					</div>
				</fieldset>
			</form>
			</section>
		</div> <!-- #wrap -->
	</div>
	
	<?php
	ssquiz_print_footer();
}