jQuery(document).ready(function( $ ) {

	$('#ssquiz_question_modal .modal-body, #ssquiz_quiz_modal .modal-body').css('max-height', $(window).height() * 0.7);

	if( $(".ssquiz_manager").length > 0 ) {
		var start_dragging;
		var finish_dragging;
		run_sorting();
		$("[data-toggle='tab']").click( function() {
			$("[data-toggle='tab']").removeClass('active');	
			$(this).addClass('active');	
		});
		$('.modal-body .mceLast').css( 'display', 'none' );
		$('.modal-body #wp-question-editor-container').css( 'margin-bottom', '20px' );
	}

	function run_sorting() {
		// Sorting questions
		$( "#ssquiz_sortable" ).sortable({
			items: "li:not(.ui-state-disabled)",
			cancel: ".ui-state-disabled, a, button",
			start: function( event, ui ) {
				start_dragging = $(ui.item).prevAll('.ui-state-default').not('ui-state-disabled').length;
			},
			update: function( event, ui ) {
				finish_dragging = $(ui.item).prevAll('.ui-state-default').not('ui-state-disabled').length;
				questions_reorder(ui.item);
			},
			revert: 100
		});
	}

	function quiz_data( subject, state, object, id ) {
		if ( subject == 'quiz' ) {
			//switchEditors.switchto( document.getElementById("description-tmce") );
			if ( state == 'add') {
				quiz_data( subject, 'clear', object, id );
			}
			if ( state == 'clear' ) {
				$("#quiz_name").val('');
				$("#quiz_type").val(null);
				$("#tag_free").val(null);
				$("#prerequisites").val([]);
				$("#next_link").val('');
				$("#upload_questions").val('');
				$("#description_ifr").contents().find("body").html('');
				
				$("#ssquiz_quiz_modal .ssquiz_ask_delete").css('display', 'none');
				$('.confirm_delete').css('left', '-585px');
				$("#ssquiz_quiz_modal .ssquiz_save").attr('onclick', "jQuery.fn.crud_quiz('quiz', 'edit', -1); return false;");
				$("#ssquiz_quiz_modal .modal_title").html(ssquiz.add_quiz);
				$("#upload_questions").css('display', 'inline-block').prev().css('display', 'inline-block');
			} 
			else if ( state == 'get' ) {
				var temp = new Object();
				temp.name = $("#quiz_name").val();
				temp.type = $("#quiz_type").val();
				temp.tag_free = $("#tag_free").val();
				temp.meta = new Object();
				temp.meta.prerequisites = $("#prerequisites").val();
				if ( $("#next_link").val() > '' ) {
					temp.meta.next_link = $("#next_link").val();
				}
				temp.meta.description = $("#description_ifr").contents().find("body").html();
				return temp;
			} 
			else if ( state == 'fill' ) {
				$("#quiz_name").val(object.name);
				$("#quiz_type").val(object.type);
				$("#tag_free").val(object.tag_free);
				$("#prerequisites").val(object.meta.prerequisites);
				$("#next_link").val(object.meta.next_link);
				$("#upload_questions").val('');
				$("#description_ifr").contents().find("body").html(object.meta.description);
				
				$("#upload_questions").css('display', 'none').prev().css('display', 'none');
				$(".confirm_delete").css ( 'left', '-585px');
				$("#ssquiz_quiz_modal .ssquiz_ask_delete").css('display', 'inline-block');
				$("#ssquiz_quiz_modal .ssquiz_delete").attr('onclick', "jQuery.fn.crud_quiz('quiz', 'delete', "+id+"); return false;");
				$("#ssquiz_quiz_modal .ssquiz_save").attr('onclick', "jQuery.fn.crud_quiz('quiz', 'edit', "+id+"); return false;");
				
				$("#ssquiz_quiz_modal .modal_title").html(ssquiz.edit_quiz);
			}
		} else { //question
			//switchEditors.switchto( document.getElementById("ssquiz_question-tmce") );
			if ( state == 'add') {
				quiz_data( 'question', 'clear' );
				$('.ssquiz_add_answer').trigger('click');
				$("#ssquiz_question_modal .ssquiz_delete").css('display', 'none');
				$("#ssquiz_question_modal .ssquiz_save").attr('onclick', "jQuery.fn.crud_quiz('question', 'edit_new', "+id+"); return false;");
				$("#ssquiz_question_modal .modal_title").html(ssquiz.add_question);
				try {$("#tab a").first().trigger('click');}catch(err){}
			}			
			else if ( state == 'clear' ) {
				$("#ssquiz_question_ifr").contents().find("body").html('');
				$(".ssquiz_answers .ssquiz_answer").not(".ssquiz_blank").remove();
				$(".tab-pane.active .ssquiz_answer .answer").val('');
			} 
			else if ( state == 'get' ) {
				var temp = new Object();
				temp.question = $("#ssquiz_question_ifr").contents().find("body").html();
				temp.type = $(".tab-pane.active").prop('id');
				var inputs = $(".tab-pane.active .ssquiz_answer").not(".ssquiz_blank")
					.filter(function(){return $(this).find('.answer').first().val() != ''});
				temp.answers = [];

				if ( temp.type == 'fill' ) {
					if ( inputs.find(".answer").val() > '' )
						temp.answers = inputs.find(".answer").val().split('|');
				}
				else if ( temp.type == 'choise' ) {
					var count = 0;
					inputs.each(function() {
						var temp2 = new Object();
						temp2.answer = $(this).find('.answer').val();
						if( $(this).find('.correct').attr('checked') == 'checked' ) {
							temp2.correct = true;
							count++;
						}
						else
							temp2.correct = false;
					   temp.answers.push(temp2);
					});
					temp.type = ( count > 1 ) ? 'multi' : 'single';
				}
				return temp;
			}
			else if ( state == 'fill' ) {
				quiz_data( 'question', 'clear' );
				
				$("#ssquiz_question_modal .ssquiz_delete").css('display', 'inline-block')
					.attr('onclick', "jQuery.fn.crud_quiz('question', 'delete', "+id+"); return false;");
				$("#ssquiz_question_modal .ssquiz_save").attr('onclick', "jQuery.fn.crud_quiz('question', 'edit', "+id+"); return false;");
				
				$("#ssquiz_question_modal .modal_title").html(ssquiz.edit_question);
				if( object.type == 'single' || object.type == 'multi' )
					object.type = 'choise';
				try {$("[href='#"+object.type+"']").trigger('click');}catch(err){}
				$("#ssquiz_question_ifr").contents().find("body").html(object.question);
				
				if ( object.type == 'fill' ) {
					$(".tab-pane.active .ssquiz_answer .answer").val(object.answers.join('|'));
				}
				else if ( object.type == 'choise' ) {
					for(var i in object.answers) {
						var temp = return_answer($('#choise_add'));
						temp.find('.answer').first().val(object.answers[i].answer);
						if (object.answers[i].correct == true)
							temp.find('.correct').first().attr('checked', 'checked');
					}
				}
			}
		}
	}
	
	$('.ssquiz_ask_delete, .ssquiz_cancel').click(function() {
		var $lefty = $('.confirm_delete');
		$lefty.animate({left: parseInt($lefty.css('left'),10) == 0 ? '-585px' : 0 });
		return false;
	});
	
	$(".ssquiz_add_answer").click(function() {
		return_answer ($(this));
		return false;
	});

	function return_answer(button) {
		var temp = button.siblings('.ssquiz_blank').clone().removeClass('ssquiz_blank');
		return temp.css('display', 'block').insertBefore(button);
	}
	
	$('select#select_quiz').change(function() {
		jQuery.fn.go_to_page ( -1, $(this).val() );
	});
	
	jQuery.fn.go_to_page = function ( offset, quiz_id) {
		$("#ssquiz_questions_container").css('opacity', '0.5');
		$.post(ssquiz.ajaxurl, {
				action: "ssquiz_go_to_page",
				offset: offset,
				quiz_id: quiz_id
		}, function (results) {
			$( "#ssquiz_sortable" ).sortable( "destroy" );
			$("#ssquiz_questions_container").html(results);
			run_sorting();
			$("#ssquiz_questions_container").css('opacity', '1.0');
		});
	}
	
	function questions_reorder (item1) {
		var info = new Object();
		info.start = start_dragging;
		info.finish = finish_dragging;
		info.number = $(item1).attr('data-number');
		info.quiz_id = $(item1).attr('data-quiz_id');
		$("#ssquiz_questions_container").css('opacity', '0.5');
		$.post(ssquiz.ajaxurl, {
				action: "ssquiz_crud_quiz",
				object: 'question',
				type_action: 'reorder',
				id: $(item1).attr('data-id'),
				info: JSON.stringify(info)
		}, function (results) {
				$( "#ssquiz_sortable" ).sortable( "destroy" );
				$("#ssquiz_questions_container").html(results);
				run_sorting();
				$("#ssquiz_questions_container").css('opacity', '1.0');
		});
	}
	
	jQuery.fn.crud_quiz = function ( object, type_action, id ) {
		var info = new Object();
		if ( type_action == 'add' ) {
			quiz_data( object, 'add', null, id );
			return;
		}
		if ( type_action != 'read' ) {
			$("#ssquiz_questions_container").css('opacity', '0.5');
		}
		if ( type_action == 'edit' || type_action == 'edit_new' ) {
			info = quiz_data( object, 'get' );
		}
		var sendData = {
			action: "ssquiz_crud_quiz", 
			object: object, 
			type_action: type_action, 
			id: id, 
			info: JSON.stringify(info) 
		};
		if ($("#upload_questions").val() > '') {
			var file_data = $("#upload_questions").prop("files")[0];
			var reader = new FileReader();
			reader.onload = function(theFile) {
				info.file = reader.result;
				sendData.info = JSON.stringify(info);
				$.post(ssquiz.ajaxurl, sendData, function (results) {
					// add quiz
					$( "#ssquiz_sortable" ).sortable( "destroy" );
					$("#ssquiz_questions_container").html(results);
					run_sorting();
					$("#ssquiz_questions_container").css('opacity', '1.0');
					$("#upload_questions").val('');
				});
			}
			reader.readAsText(file_data);
		
		} else {
			$.post(ssquiz.ajaxurl, sendData, function (results) {
				if ( type_action == 'read' ) {
					quiz_data( object, 'fill', $.parseJSON(results), id );
				} else { // 'edit'or 'delete'
					$( "#ssquiz_sortable" ).sortable( "destroy" );
					$("#ssquiz_questions_container").html(results);
					run_sorting();
					$("#ssquiz_questions_container").css('opacity', '1.0');
				}
				$("#upload_questions").val('');
			});
		}
	}

	// Statistics page
	$('.ssquiz_ask_delete_history, .ssquiz_cancel_history').click(function() {
		$('.confirm_delete_history').slideToggle(100);
		return false;
	});
	
	function ajax_history( object ) {
		$("#ssquiz_history_container").css('opacity', '0.5');
		$.post(ssquiz.ajaxurl, object, function (results) {
				$("#ssquiz_history_container").html(results);
				$("#ssquiz_history_container").css('opacity', '1.0');
			});
	}
	
	jQuery.fn.crud_history = function( id ) {
		ajax_history ({ action: "ssquiz_delete_history", id: id });
		$('.confirm_delete_history').hide();
	}
	
	jQuery.fn.go_to_history_page = function ( offset ) {
		ajax_history ({ action: "ssquiz_go_to_history_page", offset: offset });
	}

	$('#find_name').bind('input keyup', function(){
		var $this = $(this);
		clearTimeout( $this.data('timer') );
		$this.data('timer', setTimeout(function(){
			$this.removeData('timer');
			ajax_history ({ action: "ssquiz_filter_history", filter: $this.val() });
		}, 600));
	});

	$('#find_completed').change(function(e) {
		ajax_history ({ action: "ssquiz_filter_history", completed: $('#find_completed').is(':checked') });
	});

	// Settings page
	jQuery.fn.crud_template = function(type) {
		var settings = new Object();
		$("#"+type).html('<span>Saving</span>'); //<span class="ssspinner"></span>
		$("#"+type).attr("disabled", "disabled");
		switch (type) {
		case 'btn_report':
			settings.pdf_template = $("#pdf_template_ifr").contents().find("body").html();
			break;
		case 'btn_teacher_email':
			settings.teacher_email_address = $("#teacher_email_address").val();
			settings.teacher_email_subject = $("#teacher_email_subject").val();
			settings.teacher_email_template = $("#teacher_email_template_ifr").contents().find("body").html();
			settings.teacher_will_receive = $("#teacher_will_receive").prop("checked");
			break;
		case 'btn_user_email':
			settings.user_email_subject = $("#user_email_subject").val();
			settings.user_email_template = $("#user_email_template_ifr").contents().find("body").html();
			settings.user_will_receive = $("#user_will_receive").prop("checked");
			break;
		case 'btn_finish':
			settings.finish_template = $("#finish_template_ifr").contents().find("body").html();
			break;
		case 'btn_start':
			settings.start_template = $("#start_template_ifr").contents().find("body").html();
			break;
		}

		$.post(ssquiz.ajaxurl, {
				action: "ssquiz_crud_template",
				type: type,
				subject: JSON.stringify(settings)
		}, function (results) {
				$("#"+type).html(ssquiz.save);
				$("#"+type).removeAttr("disabled");
			});
	}

	// Quiz FRONTEND ------------------------------------------------------------------------------------
	var answers = [];
	var ssquiz_backup = [];
	if ( $.browser.mozilla ) { // hack for firefox
		$('html, body').css('overflow-y', 'visible');
	}

	var saveInterval = setInterval(save_progress, 60*1000);

	function save_progress(){
		getAnswers();
		var main = $('.ssquiz');
		var status = $.parseJSON(main.find(".ssquiz_hidden_status").html());
		var info = main.find(".ssquiz_hidden_info").html();

		status.answers = answers;

		var statusCopy = JSON.parse(JSON.stringify(status));
		delete statusCopy.results;
		$.post(ssquiz.ajaxurl, {
				action: "self_ssquiz_save",
				info: info,
				status: JSON.stringify(statusCopy)
		}, function (results) {
		});
		answers = [];
	}
	
	$(document).delegate(".ssquiz_ok, .ssquiz_exit", "click", function () {
		getAnswers();
		var main = $(this).parents(".ssquiz");
		var status = $.parseJSON(main.find(".ssquiz_hidden_status").html());
		var info = main.find(".ssquiz_hidden_info").html();

		// retrieve backup
		if(ssquiz_backup.length === 0){
			$.post(ssquiz.ajaxurl, {
				action: "self_ssquiz_get_backup",
				info: info
			},function (backup) {
				if($.parseJSON(backup).length > 0)
					ssquiz_backup = $.parseJSON(backup);
			});
		}

		var button = $(this);
		var temp = true;
		var temp2 = true;
		status.answers = answers;
		if(button.hasClass("ssquiz_exit"))
			status.exit = true;
		if ( status.just_started )
			temp = start_quiz( main, status );
		else {
			// if (status.results != null && status.results !='0' && !status.resuming)
			// 	ssquiz_backup[status.current_page-1] = status.results;
			if(status.results != null && status.results !='0')
				status.resuming = false;
			delete status.results;
			temp2 = check_answers( main, status );
		}
		if ( !temp || !temp2 )
			return false;
		status.just_started = false;

		button.attr("disabled", "disabled");
		if ((status.total_questions == status.questions_counter) || status.exit)
			status.finished = true;
	
		$.post(ssquiz.ajaxurl, {
				action: "ssquiz_response",
				info: info,
				status: JSON.stringify(status)
		}, function (results) {
			main.find(".ssquiz_body").fadeOut(100, function (){
				main.find(".ssquiz_body").html(results);
				// finished
				if ( true == status.finished ) {
					clearInterval(saveInterval);
					main.find(".ssquiz_ok, .ssquiz_exit").remove();
					$('<div class="ssquiz_history"></div>').insertBefore(main.find(".history_list"));
					var status1 = $.parseJSON($(".ssquiz_hidden_status").html());
					if (status1.results != null && status1.results !='0' && !status.exit) //!
						ssquiz_backup[status1.current_page] = status1.results;
				}
				// about to finish
				if (status.total_questions <= status.questions_counter + status.paging) {
					main.find(".ssquiz_exit").css('display', 'inline-block');
					button.html(ssquiz.finish);
				// not finishing
				} else {
					if ( status.all_at_once ) {
						main.find(".ssquiz_exit").css('display', 'inline-block').html(ssquiz.finish);
						main.find(".ssquiz_ok").hide();
					} else {
						main.find(".ssquiz_exit").css('display', 'inline-block');
						main.find(".ssquiz_ok").html(ssquiz.next);
					}
				}
				// store ssquiz_backup for recording results for each question
				var status2 = $.parseJSON(main.find(".ssquiz_hidden_status").html());
				if ( !status.just_started && false == status.finished )
					if (status2.results != null && status2.results !='0' && !status.resuming)
						ssquiz_backup[status2.current_page-1] = status2.results;
				$.post(ssquiz.ajaxurl, {
					action: "self_ssquiz_store_backup",
					info: info,
					backup: JSON.stringify(ssquiz_backup)
				});
				main.find(".ssquiz_body").fadeIn(100, function(){ button.removeAttr("disabled"); });
			});
		});
		main.find(".ssquiz_body").html("<div class='ssquiz_loading'><img src='"+ssquiz.assets+"loader.gif'/></div>");
		return false;
	});

	jQuery.fn.history_walk = function( number ) {
		$(".history_list > a").removeClass('active');
		if ( number == -1 ) {
			$(".ssquiz_finish, .history_list h4, .ssquiz_get_results").show();
			$(".history_list .ssquiz_back").hide();
			$(".ssquiz_history").html('');
		} else {
			$(".alert, .ssquiz_get_results, .ssquiz_finish, .history_list h4").hide();
			$(".history_list .ssquiz_btn").show();
			$(".history_list > a[id='ssquiz_"+number+"']").addClass('active');
			$(".ssquiz_history").html(ssquiz_backup[number].replace(/&amp;/g, "&").replace(/&lt;/g, "<").replace(/&gt;/g, ">"));
		}
	}

	jQuery.fn.ssquiz_restart = function() {
		var info = $(".ssquiz_hidden_info").html();
		var status = new Object();
		status.restart = true;
		$.post(ssquiz.ajaxurl, {
				action: "ssquiz_response",
				info: info,
				status: JSON.stringify(status)
		}, function (results) {
				$(".ssquiz").replaceWith(results);
		});
	}

	function check_answers( main, status ) {
		return true;
	}

	function start_quiz( main, status ) {
		var input_field = main.find(".ssquiz_user_name");
		$('.ssquiz input[type="text"]').css('border-color', '#CCC');
		$('.ssquiz span.help-error').hide();
		
		if (status.name == true) {
			var name = input_field.val();
			if ( name == null || name == "" ) {
				input_field.css('border-color', '#B94A48');
				input_field.next().show();
				return false;
			}
			status.name = name;
		}
		
		input_field = main.find(".ssquiz_user_email");
		if (status.email == true) {
			var email_regex = /^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/;
			if ( false == email_regex.test(input_field.val()) ) {
				input_field.css('border-color', '#B94A48');
				input_field.next().show();
				return false;
			}
			status.email = input_field.val();
		}
		
		if ( status.timer ) {
			quiz_timer (status.timer, 0, main );
		}
		return true;
 	}
	
	function quiz_timer (left, timer_id, main ){
		if ( main.find(".ssquiz_ok").length == 0)
			return;
		if (left < 0) {
			main.find(".ssquiz_exit").trigger("click");
			return;
		}
		var date = new Date(left * 1000);
		var time = date.toTimeString().substr(3, 5);
		$(".ssquiz_timer span").html(time);
		if (left < 10) {
			$(".ssquiz_timer span").addClass('time_alert');
		}
		timer_id = setTimeout(function(){
			quiz_timer( --left, timer_id, main );
		}, 1000);
	}

	// Quiz Types ------------------------------------------------------------------------------------
	//------------- All at once
	jQuery.fn.run_all_at_once = function() {
		answers = [];
		
		$(".ssquiz_answer_span").click(function() {
			$(this).prev().prop('checked', function(i, oldVal) { return !oldVal; });
			$('.ssquiz_answer').first().trigger('change');
		});
		
		$(".ssquiz_question").each(function (i, val) {
			answers[i] = [];
		});
		$(".ssquiz_answer").change(function () {
			$(".ssquiz_question").each(function (i) {
				$(this).find(".ssquiz_answer").each(function (j, val) {
					if( $(this).attr('type') == 'text' )
						answers[i][j] = { answer: $(this).val() };
					else {
						answers[i][j] = { answer: $(this).next().html(), correct: $(this).is(':checked') };
					}
				});
			});
		})
	}

	//------------- Standard types
	jQuery.fn.run_standard_types = function() {
		answers = [];
		
		$(".ssquiz_answer_span").click(function() {
			$(this).prev().prop('checked', function(i, oldVal) { return !oldVal; });
		});
		
		$(".ssquiz_answer").change(function () {
			getAnswers();
		})

		setTimeout(function () {
			$('.ssquiz_answer[type="text"]').on("keydown", function(event){
				if (event.keyCode == 13)
					$(this).parents(".ssquiz").find(".ssquiz_ok").click();
			})
			.first().focus();
		}, 100);
	}
	
	function getAnswers(){
		$(".ssquiz_answer").each(function (i) {
			answers[i] = new Object();
			if( $(this).attr('type') == 'text' )
				answers[i].answer = $(this).val();
			else {
				answers[i].answer = $(this).next().html();
				answers[i].correct = $(this).is(':checked');
			}
		});
	}
});