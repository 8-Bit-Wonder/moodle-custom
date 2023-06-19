<?php
// Rework Next_Page_Custom()
/* Custom functions

	- triggered_custom_next_page
	- hide_progress_panel
	- hide_next_page_button
	- cripple_choose_button
	- cripple_option_form
	- count_answer_num
	- rendering_custom_next_page_option_frame
	- cheating🧽
	- hide_state_column
	- hide_summary_panel
	- hide_review_panel
	- count_total_questions
	- count_finished_questions
	- custom_progress_track
	- moving_custom_div_to_panel
*/

/* Main functions

	- attempt_form
	- summary_page
	- review_page
	- summary_table
*/
    protected $css_display_none = "{ display:none!important; }"; // >:D I am important
    protected $custom_css;
    protected $custom_javascript;
    protected $hide_next_page_button_and_option_inputs;
    protected $render_response;
    protected $option_question;
    protected $crits = array('adaptivenopenalty','free',1); // criterias required by user
    protected $start_custom_next_page;
    public function triggered_custom_next_page($usage,  $attempt,   $module){
        global $DB, $USER;
        $this->start_custom_next_page = false;
        $query = "";
        $query.=" SELECT
        q.preferredbehaviour, 
        q.navmethod,
        q.questionsperpage
        FROM {quiz_attempts} qz 
        INNER JOIN {quiz} q on q.id = qz.quiz
        INNER JOIN {question_usages} quba on quba.id = qz.uniqueid
        INNER JOIN {course_modules} cms on cms.instance = q.id
        WHERE quba.id = ".$usage." AND qz.id = ".$attempt." AND cms.id = ".$module." AND qz.userid = ".$USER->id."
        GROUP BY 
        q.preferredbehaviour, 
        q.navmethod,
        q.questionsperpage";
        $crit = $DB->get_record_sql($query,array());
        if($crit->preferredbehaviour==$this->crits[0]
        && $crit->navmethod==$this->crits[1]
        && $crit->questionsperpage==$this->crits[2]){
            $this->start_custom_next_page = true;   // Start customizing code.
        }
        return $this->start_custom_next_page;
    }
    protected $qn_buttons_clearfix_multipages = "qn_buttons clearfix multipages";
    protected $mod_quiz_navblock_title = "mod_quiz_navblock_title";
    
    public function hide_progress_panel(){

        $this->custom_css = "";
        $this->custom_css .= html_writer::start_tag('style');
        $this->custom_css .="#mod_quiz_navblock_title ".$this->css_display_none;
        $this->custom_css .=html_writer::end_tag('style');

        // 16/06/2023 change from css to Javascript.
        $this->custom_javascript = $this->custom_css."";
        $this->custom_javascript.= html_writer::start_tag('script');
        //$this->custom_javascript.= "document.getElementById('".$this->mod_quiz_navblock_title."').remove();"; Somehow this does not hide the span
        $this->custom_javascript.= "while (document.getElementsByClassName('".$this->qn_buttons_clearfix_multipages."')[0])";
        $this->custom_javascript.= "{ document.getElementsByClassName('".$this->qn_buttons_clearfix_multipages."')[0].remove(); }";
        $this->custom_javascript.= html_writer::end_tag('script');
        return $this->custom_javascript;
    }
    
    public function hide_next_page_button(){
        $this->custom_css ="";
        $this->custom_css .=html_writer::start_tag('style');
        $this->custom_css .="#mod_quiz-next-nav ".$this->css_display_none;
        $this->custom_css .=html_writer::end_tag('style');
        echo $this->custom_css;
    }
    
    public function cripple_choose_button($usage, $slot){
        $this->choose_button ="q".$usage.":".$slot."_-submit";
        $this->custom_javascript ="";
        $this->custom_javascript .= html_writer::start_tag('script');
        $this->custom_javascript .= "document.getElementById('".$this->choose_button."').setAttribute('disabled', 'disabled'); ";
        $this->custom_javascript .= html_writer::end_tag('script');
        $this->custom_css ="";
        $this->custom_css .=html_writer::start_tag('style');
        $this->custom_css .=".qtype_multichoice_clearchoice ".$this->css_display_none;
        $this->custom_css .=html_writer::end_tag('style');
        return $this->custom_javascript.' '.$this->custom_css;
    }
    public function cripple_option_form($attempt, $slot, $pos){
        $this->option_question ="q".$attempt.":".$slot."_answer".$pos;
        $this->custom_javascript ="";
        $this->custom_javascript .= html_writer::start_tag('script');
        $this->custom_javascript .= "document.getElementById('".$this->option_question."').setAttribute('disabled', 'disabled'); ";
        $this->custom_javascript .= html_writer::end_tag('script');
        return $this->custom_javascript;
    }
    public function count_answer_num($attempt, $slot){
        global $DB;
        $query = "";
        $query .="SELECT count(qas.id) pos_num
        FROM mdl232x0_quiz_attempts qz
        INNER JOIN {question_usages} quba on quba.id = qz.uniqueid 
        INNER JOIN {question_attempts} qa on qa.questionusageid = quba.id
        INNER JOIN {question} q on q.id = qa.questionid
        INNER JOIN {question_answers} qas on qas.question = q.id
        WHERE qz.id = ".$attempt." AND qa.slot = ".$slot;
        $answers = $DB->get_record_sql($query,array());
        return $answers->pos_num;
    }
    protected $disabled_choose_button;
    protected $hide_next_page_button;
    public function rendering_custom_next_page_option_frame($usage, $slot){
        global $DB, $USER;
        $this->disabled_choose_button = false;
        $this->hide_next_page_button = true;
        $this->render_response = false;
        $response_array = array();
        //$this->render_response = true;
        $query = "";
        $query .= " SELECT
        qa.rightanswer, 
        qa.responsesummary
        FROM {question_usages} quba
        INNER JOIN {question_attempts} qa ON qa.questionusageid = quba.id
        INNER JOIN {question_attempt_steps} qas ON qas.questionattemptid = qa.id
        WHERE quba.id = ".$usage." AND qas.userid = ".$USER->id." AND qa.slot = ".$slot."
        GROUP BY 
        qa.rightanswer, 
        qa.responsesummary";
        $user_data = $DB->get_record_sql($query,array());
        if($user_data->rightanswer == $user_data->responsesummary){
            $this->hide_next_page_button = false; // Show Next Page
            $this->disabled_choose_button = true;
            $this->render_response = true;
        }
        if($this->hide_next_page_button){ // Hide Next Page
            $this->hide_next_page_button(); // display none
        }
        if($this->disabled_choose_button){ // No longer allows to choose if answer is correct
            $response_array['choose_button_content'] = $this->cripple_choose_button($usage, $slot);   // setAttribute  = disabled
        }
        $response_array['disabled_option_form'] = $this->render_response;
        return $response_array;
    }
    //  End Next_Page_Custom()
    
    public function cheating($id,$slot){
        global $DB;
        $qas = $DB->get_record_sql('SELECT qa.rightanswer cheat_answer
        FROM {question_usages} quba
        INNER JOIN {question_attempts} qa on qa.questionusageid = quba.id 
        INNER JOIN {quiz_attempts} qas on qas.uniqueid = quba.id 
        WHERE qas.id = ? and qa.slot = ?',array('id'=>$id,'slot'=>$slot));
        return $qas->cheat_answer;
    }

public function attempt_form($attemptobj, $page, $slots, $id, $nextpage) 
{
        $output = '';

        // Start the form.
        $output .= html_writer::start_tag('form',
                array('action' => new moodle_url($attemptobj->processattempt_url(),
                array('cmid' => $attemptobj->get_cmid())), 'method' => 'post',
                'enctype' => 'multipart/form-data', 'accept-charset' => 'utf-8',
                'id' => 'responseform'));
        $output .= html_writer::start_tag('div');

        /* 14/06/2023 Next_Page_Custom()
            - Implement custom condition to original
        */
        
        $rendering_current_question_custom = false;
        if($this->triggered_custom_next_page($attemptobj->get_uniqueid(),$attemptobj->get_attemptid(),$attemptobj->get_cmid())){
            //$this->hide_progress_panel();  move hide_progress_panel down to bottom because it requires Javascript to remove an element
            $rendering_current_question_custom = true;
        }
        echo html_writer::start_tag('script').'console.log("🍩");'.html_writer::end_tag('script');
        foreach ($slots as $slot) {
            $output .= html_writer::start_tag('b',array('style'=>'color:red'));
            $output .= $this->cheating($attemptobj->get_attemptid(),$slot);
            $output .= html_writer::end_tag('b');
            $output .= $attemptobj->render_question($slot, false, $this,
                    $attemptobj->attempt_url($slot, $page), $this);
            if($rendering_current_question_custom){
                $outcome = array();
                    $outcome = $this->rendering_custom_next_page_option_frame($attemptobj->get_uniqueid(),$slot);
                    $output .= $outcome['choose_button_content'];
                    $question_quantity = $this->count_answer_num($attemptobj->get_attemptid(),$slot);
                    if($outcome['disabled_option_form']){
                        $pos = 0;
                        for($i=0; $i<$question_quantity; $i++){
                            $output.= $this->cripple_option_form($attemptobj->get_uniqueid(), $slot, $pos);
                            $pos = $pos + 1;
                        }
                    }           
                }
        }

        $navmethod = $attemptobj->get_quiz()->navmethod;
        $output .= $this->attempt_navigation_buttons($page, $attemptobj->is_last_page($page), $navmethod);

        // Some hidden fields to trach what is going on.
        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'attempt',
                'value' => $attemptobj->get_attemptid()));
        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'thispage',
                'value' => $page, 'id' => 'followingpage'));
        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'nextpage',
                'value' => $nextpage));
        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'timeup',
                'value' => '0', 'id' => 'timeup'));
        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey',
                'value' => sesskey()));
        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'scrollpos',
                'value' => '', 'id' => 'scrollpos'));

        // Add a hidden field with questionids. Do this at the end of the form, so
        // if you navigate before the form has finished loading, it does not wipe all
        // the student's answers.
        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'slots',
                'value' => implode(',', $attemptobj->get_active_slots($page))));

        // Finish the form.
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('form');

        $output .= $this->connection_warning();

        return $output;
}

public function summary_page($attemptobj, $displayoptions) {
        $output = '';
        $output .= $this->header();
        $output .= $this->heading(format_string($attemptobj->get_quiz_name()));
        $output .= $this->heading(get_string('summaryofattempt', 'quiz'), 3);
        $output .= $this->summary_table($attemptobj, $displayoptions);
        $output .= $this->summary_page_controls($attemptobj);
        $output .= $this->footer();
        
        if($this->triggered_custom_next_page($attemptobj->get_uniqueid(),$attemptobj->get_attemptid(),$attemptobj->get_cmid())) {
            $this->custom_progress_track($attemptobj->get_attemptid());
            
            $output.= $this->moving_custom_div_to_panel();
        }
        return $output;
}

public function review_page(quiz_attempt $attemptobj, $slots, $page, $showall,
                                $lastpage, mod_quiz_display_options $displayoptions,
                                $summarydata) {

        $output = '';
        $output .= $this->header();
        $output .= $this->review_summary_table($summarydata, $page);
        $output .= $this->review_form($page, $showall, $displayoptions,
                $this->questions($attemptobj, true, $slots, $page, $showall, $displayoptions),
                $attemptobj);

        $output .= $this->review_next_navigation($attemptobj, $page, $lastpage, $showall);
        $output .= $this->footer();

        if($this->triggered_custom_next_page($attemptobj->get_uniqueid(),$attemptobj->get_attemptid(),$attemptobj->get_cmid())) {
            $this->custom_progress_track($attemptobj->get_attemptid());
            $this->hide_review_panel();
            $output.= $this->moving_custom_div_to_panel();  
        }
        return $output;
}

/* 15/06/2023
        Custom code to erase the confusion
    */
    public function hide_state_column(){
        $this->custom_css ="";
        $this->custom_css .=html_writer::start_tag('style');
        $this->custom_css .="th.header.c1 ".$this->css_display_none;
        $this->custom_css .=" ";
        $this->custom_css .="td.cell.c1 ".$this->css_display_none;
        $this->custom_css .=html_writer::end_tag('style');
        echo $this->custom_css;
    }
    public function hide_summary_panel(){
        $this->custom_css ="";
        $this->custom_css .=html_writer::start_tag('style');
        $this->custom_css .="div.card-body.p-3 ".$this->css_display_none;
        $this->custom_css .=html_writer::end_tag('style');
        echo $this->custom_css;
    }
    public function hide_review_panel(){
        $this->custom_css ="";
        $this->custom_css .=html_writer::start_tag('style');
        $this->custom_css .="div.card-body.p-3 ".$this->css_display_none;
        $this->custom_css .=html_writer::end_tag('style');
        echo $this->custom_css;
    }
    protected $summary_panel_div = "mod_quiz_navblock";
    protected $custom_progress_track = "custom_progress_track";
    protected $custom_html; protected $completion_text = 'Đã hoàn thành';
    public function count_total_questions($attempt,$user){
        global $DB;
        $query = "";
        $query .= "SELECT q.id,qa.id,qa.rightanswer
        FROM {question_usages} quba
        INNER JOIN {question_attempts} qa ON qa.questionusageid = quba.id
        INNER JOIN {question_attempt_steps} qas ON qas.questionattemptid = qa.id
		INNER JOIN {quiz_attempts} qz ON qz.uniqueid = quba.id
		INNER JOIN {question} q ON q.id = qa.questionid
        WHERE qz.id = ".$attempt." AND qas.userid = ".$user."
        GROUP BY	q.id,qa.id,qa.rightanswer";
        $total = $DB->get_records_sql($query,array());
        return count($total);
    }
    public function count_finished_questions($attempt,$user){
        global $DB;
        $query = "";
        $query .= "SELECT q.id,qa.id,qa.rightanswer,qa.responsesummary
        FROM {question_usages} quba
        INNER JOIN {question_attempts} qa ON qa.questionusageid = quba.id
        INNER JOIN {question_attempt_steps} qas ON qas.questionattemptid = qa.id
		INNER JOIN {quiz_attempts} qz ON qz.uniqueid = quba.id
		INNER JOIN {question} q ON q.id = qa.questionid
        WHERE qz.id = ".$attempt." AND qas.userid = ".$user."
		AND qa.responsesummary is NOT NULL
        GROUP BY	q.id,qa.id,qa.rightanswer,qa.responsesummary";
        $quantity_finished = $DB->get_records_sql($query,array());
        return count($quantity_finished);
    }
    protected $finished_questions;
    protected $total_questions;
    public function custom_progress_track($attempt){
        global $USER;
        $this->finished_questions = $this->count_finished_questions($attempt,$USER->id);
        $this->total_questions = $this->count_total_questions($attempt,$USER->id);

        $this->custom_html = "";
        $this->custom_html .= html_writer::start_tag('div',array('id'=>$this->custom_progress_track,
        'style'=>'margin:10px;padding:3px'));
        $this->custom_html .= html_writer::start_tag('b').$this->completion_text.html_writer::end_tag('b');
        $this->custom_html .= html_writer::empty_tag('br');
        
        $this->custom_html .= html_writer::start_tag('b',array('style'=>'color:green')).$this->finished_questions.'/'.$this->total_questions.html_writer::end_tag('b');
        $this->custom_html .= html_writer::end_tag('div');
        echo $this->custom_html;
    }
    public function moving_custom_div_to_panel(){

        $this->custom_javascript = "";
        $this->custom_javascript .=html_writer::start_tag('script');
        $this->custom_javascript.="
        document.getElementById('".$this->summary_panel_div."').appendChild(document.getElementById('".$this->custom_progress_track."')
        );
        ";
        $this->custom_javascript .=html_writer::end_tag('script');
        return $this->custom_javascript;
    }
    public function summary_table($attemptobj, $displayoptions) {
        // Prepare the summary table header.
        $table = new html_table();
        $table->attributes['class'] = 'generaltable quizsummaryofattempt boxaligncenter';
        $table->head = array(get_string('question', 'quiz'), get_string('status', 'quiz'));
        $table->align = array('left', 'left');
        $table->size = array('', '');
        $markscolumn = $displayoptions->marks >= question_display_options::MARK_AND_MAX;
        if ($markscolumn) {
            $table->head[] = get_string('marks', 'quiz');
            $table->align[] = 'left';
            $table->size[] = '';
        }
        $tablewidth = count($table->align);
        $table->data = array();

        // Get the summary info for each question.
        $slots = $attemptobj->get_slots();
        foreach ($slots as $slot) {
            // Add a section headings if we need one here.
            $heading = $attemptobj->get_heading_before_slot($slot);
            if ($heading) {
                $heading = format_string($heading);
            }
            $sections = $attemptobj->get_quizobj()->get_sections();
            if (!is_null($heading) && empty($heading) && count($sections) > 1) {
                $heading = get_string('sectionnoname', 'quiz');
                $heading = \html_writer::span($heading, 'dimmed_text');
            }

            if ($heading) {
                $cell = new html_table_cell($heading);
                $cell->header = true;
                $cell->colspan = $tablewidth;
                $table->data[] = array($cell);
                $table->rowclasses[] = 'quizsummaryheading';
            }

            // Don't display information items.
            if (!$attemptobj->is_real_question($slot)) {
                continue;
            }

            // Real question, show it.
            $flag = '';
            if ($attemptobj->is_question_flagged($slot)) {
                // Quiz has custom JS manipulating these image tags - so we can't use the pix_icon method here.
                $flag = html_writer::empty_tag('img', array('src' => $this->image_url('i/flagged'),
                        'alt' => get_string('flagged', 'question'), 'class' => 'questionflag icon-post'));
            }
            /*  15/06/2023
                    - Add a condition checking if conditions are met, the custom code
                    will be added here
                    - hide_state_column()
                    - hide_summary_panel()
            */
            if($this->triggered_custom_next_page($attemptobj->get_uniqueid(), // <--- CUSTOM CODE
            $attemptobj->get_attemptid(),$attemptobj->get_cmid())){
                $this->hide_state_column();
                $this->hide_summary_panel();
            }

            if ($attemptobj->can_navigate_to($slot)) {
                $row = array(html_writer::link($attemptobj->attempt_url($slot),
                        $attemptobj->get_question_number($slot) . $flag),
                        $attemptobj->get_question_status($slot, $displayoptions->correctness));
            } else {
                $row = array($attemptobj->get_question_number($slot) . $flag,
                                $attemptobj->get_question_status($slot, $displayoptions->correctness));
            }
            
            if ($markscolumn) {
                $row[] = $attemptobj->get_question_mark($slot);
            }
            $table->data[] = $row;
            $table->rowclasses[] = 'quizsummary' . $slot . ' ' . $attemptobj->get_question_state_class(
                    $slot, $displayoptions->correctness);
        }

        // Print the summary table.
        $output = html_writer::table($table);

        return $output;
}
