=== SS Quiz ===
Contributors: ssvadim
Donate link: http://100vadim.com/ssquiz/
Tags: quiz, quizzes, question, answer, test, learning, education
Requires at least: 3.0
Tested up to: 3.6
Stable tag: 2.0.5

With this plugin administrator can easily and fast create complicated tests and user can't relax, answering this questions.

== Description ==

With [SSQuiz](http://www.100vadim.com/ssquiz/) you can make quizzes really fast. Add questions/quizzes, rearrange questions, edit answers, insert multimedia in questions - all of this can be done on single page within several seconds. Also one can edit welcome/finish/email templates using html if it's needed. Quiz automatically determines what type of test user creates (choose-correct, fill-blank or question with several correct answers). Once quiz is over, user can walk through their answers and see correct answers.

Features include:

* Easy and fast quiz creation
* Multimedia in questions
* Multiple types of questions
* Timer
* Email sending to user or teacher
* Plugin API (See FAQ)
* Localization ready (Currently added Russian and Persian translation)

== Installation ==

SSQuiz 2.0 is rethought and rewritten compared to previous versions.
If you were using previous version, then your SSQuiz data will be converted to a new format on activation.

If you have problems with updating to 2.0 version you can downgrade it to 1.12.c

If you're uninstalling plugin, quizzes will stay on database. To delete them, Delete tables with names like 'ssquiz' in Wordpress database.
== Screenshots ==

1. User Answers
2. User checks correct answer
3. Statistics Page
4. Adding Question
5. Quizzes on Admin end

== Frequently Asked Questions ==

= What should I know about front end =

When answering to questions, user must be connected to internet, as every answer is sent to server and checked there, making cheating harder.
Quiz accepts empty answers from users.
API and email sending trigger only if user went through all questions, however quiz saves every attempt to the statistics page.

= What shortcodes can I use =

You can use following arguments:

* all - to show all questions on once
* not_correct - not showing correct answers after quiz is finished
* show_correct - to show weather user ansered correctly after each answer
* qrandom - to randomize order of questions
* arandom - to randomize order of  answers
* name - to request user name at quiz start. For logged in users this field will be pre-filled
* email - to request user email at quiz start and to send him email when quiz is done. For logged in users this field will be pre-filled
* timer - set timer in seconds. For example: timer=12
* one_chance - for registered users sets only one attempt to pass test. To start again you should delete their attempt from quiz's history
* total - number of questions to display. It is useful with 'qrandom' to change questions each time.

For example: [ssquiz id=1 qrandom timer=20] means insert on page quiz with id = 1, with shuffled questions. In 20 seconds after user clicks 'Start' exit will be triggered.

= What should I know about back end =

Quiz uses ajax requests for all operations on admin side. So the pages will not reload on each update, making working with quiz more convenient. Max number of questions on page is 20.

On 'Manage quizzes' page selected quiz and page is saved, so when you leaved and then went back it would show the questions you recently had been working with.

For questions type 'choise': if you write many answers, but only one of those is correct, then it will be "multiple choice" test, and user will be able to choose only one answer.

If you checks more than one correct answer to question, then it would be possible, to choose several answers at once.

While working with quizzes you can:

* Leave answers blank to delete them
* Drag question within quiz to change order of questions
* Delete quizzes or questions while editing them. There will be button 'delete' on the buttom

== Changelog ==

= 2.0.5 =

* Fixed bug with update from 1.* version

= 2.0.4 =

* Added Spanish language
* Fixed html content type in emails
* Changed 2 short PHP opening tags to <?php

= 2.0.3 =

* Using html content type in emails
* Fixed bug with radiobox names
* Percent is counting over all questions, not only answered

= 2.0.2 =

* Small improvements
* added total argument

= 2.0.1 =

* Fixed problems with multisite
* Fixed some php notices

= 2.0 =
* Total rewriting with fixing security holes
* Paginations added on admin side
* Improved Statistics Page
* Added Documentation Page

= 1.12 =
* Some bugs and security issues solved
* Internationalized and russian language added
* Added 'one_chance' feature to shortcode
* Added wordpress editor to to some templates

= 1.11 =
* Bug fixed with Insert Media
* More compatibility with UTF8

= 1.10 =
* Fixed bug with email
* Input fields are now case insensitive

= 1.09 =
* Bug with multiple choices fixed
* Bug with API fixed

= 1.08 =
* Quiz can be placed anywhere on the page from now
* Other little improvements

= 1.07 =
* Bug with user name and email fixed
* Minor interface improvement
* Email header can be changed for email from now

= 1.06 =
* Timer improved
* New user's buttons
* Added ability to send emails to teachers
* Optional sending email to user
* Fixed bug with "exit button" in the start of quiz
* Loading animation added
* "OK" Button is called "Next" from now
* On the last question "Next" button is renamed to "Finish" now
* Other things fixed

= 1.05 =
* Fixed bug with editing questions in Firefox
* Fixed bug with right answers counting
* Fixed bug with ordering answers
* Layout Improvement
* Button "Clear History List" added in User's Page of Dashboard
* API added (see FAQ)

= 1.041 =
* Fixed bug with user's email

= 1.04 =
* Quiz now has no background
* Bug fixed with user's inputting information about them
* Email verification added
* Solved two errors with Internet Explorer
* Now users can be deleted from history
* Little improvements on admin side

= 1.03 =
* Bug fixed with adding media on another admin pages
* Showing correct answers is optional now

= 1.02 =
* Fixed reactivation bug

= 1.01 = 
* Fixed several bugs
* Ok button is disabled while loading

= 1.0 =
* Fixed some bugs
* Right answers are shown
* Optional Timer added

= 0.9 =
* Initial release
