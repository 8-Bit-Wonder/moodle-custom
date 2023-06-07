<?php
    /* 06/06/2023
        - Add a HTML message box. And a function to hide track page
        - custom_disable_choose_option_box()
        - custom_disabled_multiplates() 
    */
    public function custom_disabled_multiplates(){
        $css = '';
        $css .= html_writer::start_tag('style');
        $css .= 'div.qn_buttons.clearfix.multipages { display:none!important; }';
        $css .= '#mod_quiz_navblock_title { display:none!important; }';
        $css .= html_writer::end_tag('style');
        echo $css;
    }
    public function custom_disable_choose_option_box($question,$answered){ 
        $css = ''; $html = '';
        // rendering data
        $alert_message = $question;
        $alert_message .= html_writer::empty_tag('br');
        $alert_message .= html_writer::empty_tag('br');
        $alert_message .= "Câu trả lời:";
        $alert_message .= html_writer::empty_tag('br');
        $alert_message .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"; // spacing between line
        $alert_message .= html_writer::start_tag('strong').$answered.html_writer::end_tag('strong')."&nbsp;✔️";
        $alert_message .= html_writer::empty_tag('br');
        $alert_message .= html_writer::empty_tag('br');
        $alert_message .= "Bấm ".html_writer::start_tag('strong')
        ."Câu tiếp theo".html_writer::end_tag('strong')." để tiếp tục.";
        //
        //$html .= html_writer::_tag('',array());
        $css .= html_writer::start_tag('style');
        $css .= '.alert { padding: 20px; background-color: #e7f3f5; color: black; }'; // #04AA6D = Green
        $css .= html_writer::end_tag('style');
        /* */
        $html .= html_writer::start_tag('div',array('class'=>'alert'));
        $html .= $alert_message;
        $html .= html_writer::end_tag('div');
        return $css.' '.$html;
    }


    /* 19/05/2023
        - Customizing the Next Page button can only be visible if certain conditions are met.
        - disable_next_page_sequential_behaviour()
        - cheating() <-- disable this after testing
    */
    /*
    public function cheating($id,$slot){
        global $DB;
        $qas = $DB->get_record_sql('SELECT qa.rightanswer cheat_answer
        FROM {question_usages} quba
        INNER JOIN {question_attempts} qa ON qa.questionusageid = quba.id 
        INNER JOIN {quiz_attempts} qas ON qas.uniqueid = quba.id 
        WHERE qas.id = ? AND qa.slot = ?',array('id'=>$id,'slot'=>$slot));
        return $qas->cheat_answer;
    }
    */
    /* 06/06/2023
        - replace input with disabled using Javascript.
    */
    public function custom_disable_input_using_attribute_set($quiz_attempts_id, $slot, $answer_pos){
        $id = "q".$quiz_attempts_id.":".$slot."_answer".$answer_pos;
        $plain_javascript = '';
        $plain_javascript .= html_writer::start_tag('script');
        $plain_javascript .= "document.getElementById('".$id."').setAttribute('disabled', 'disabled'); ";
        $plain_javascript .= html_writer::end_tag('script');
        return $plain_javascript;
    }
    public function custom_disable_the_choose_answer_button($quiz_attempts_id, $slot){
        // q1408492:17_-submit
        $id = "q".$quiz_attempts_id.":".$slot."_-submit";
        $plain_javascript = '';
        $plain_javascript .= html_writer::start_tag('script');
        $plain_javascript .= "document.getElementById('".$id."').setAttribute('disabled', 'disabled'); ";
        $plain_javascript .= html_writer::end_tag('script');
        return $plain_javascript;
    }


    public function disable_next_page_sequential_behaviour($slot_num,$userid,$attempt){
        global $DB;
        $q = array();
        $hide_choose_option = false;;
        $quiz_user_result = $DB->get_record_sql('SELECT 
        q.preferredbehaviour, q.navmethod,q.questionsperpage,
        qa.rightanswer, qa.responsesummary, que.questiontext
        FROM mdl232x0_quiz_attempts qz
        INNER JOIN mdl232x0_quiz q ON q.id = qz.quiz 
        INNER JOIN mdl232x0_course_modules cms ON cms.instance = q.id 
        INNER JOIN mdl232x0_question_usages quba ON quba.id = qz.uniqueid
        INNER JOIN mdl232x0_question_attempts qa   ON qa.questionusageid    = quba.id
        INNER JOIN mdl232x0_question_attempt_steps qas  ON qas.questionattemptid = qa.id
        INNER JOIN mdl232x0_question que ON que.id = qa.questionid
        WHERE quba.id = ? AND qa.slot = ? AND qas.userid = ?
        GROUP BY q.preferredbehaviour, q.navmethod,q.questionsperpage,
        qa.rightanswer, qa.responsesummary, que.questiontext',
        array('id'=>$attempt,'slot'=>$slot_num,'userid'=>$userid));
        $q['preferredbehaviour']=$quiz_user_result->preferredbehaviour;
        $q['navmethod']=$quiz_user_result->navmethod;
        $q['questionsperpage']=$quiz_user_result->questionsperpage;
        $q['rightanswer']=$quiz_user_result->rightanswer;
        $q['responsesummary']=$quiz_user_result->responsesummary;
        // add questiontext to notify message
        $q['questiontext']=$quiz_user_result->questiontext;

        if($q['preferredbehaviour']=='adaptivenopenalty'   /*'adaptive'*/
        &&$q['navmethod']=='free'    /*'sequential'*/
        &&$q['questionsperpage']==1){ /*If conditions are met, start the custom function to
         block Next Page*/
            // . hide Next Page button.
            $disableNextPage = true;
            // . enabled choose option form
            $disableChooseForm = false; // default if set to false if answer has not been updated yet.
            // . Compare the result and answer. If matched => enable the Next Page button
            if($q['rightanswer']==$q['responsesummary']){
                $disableNextPage = false; // update status
                $disableChooseForm = true; // lock the form, forbidding user from choose option 2nd time.
            }
            if($disableNextPage){
                $disableNextPageCSS = '';
                $disableNextPageCSS .= html_writer::start_tag('style');
                $disableNextPageCSS .= '#mod_quiz-next-nav{display:none;!important}';
                $disableNextPageCSS .= html_writer::end_tag('style');
                echo $disableNextPageCSS;
            }
            if($disableChooseForm){
                $hide_choose_option = true;
                //$hide_choose_option = $this->custom_disable_choose_option_box($q['questiontext'],$q['responsesummary']);
            }
            
            /*
            if($disableChooseForm){
                // CSS Magic here : $disabledChooseOptionCSS .= '';
                $disabledChooseOptionCSS .= html_writer::start_tag('style');
                $disabledChooseOptionCSS .= 'div.info {display:none;!important}';
                $disabledChooseOptionCSS .= 'div.formulation.clearfix {display:none;!important}';
                $disabledChooseOptionCSS .= html_writer::end_tag('style');
                echo $disabledChooseOptionCSS;

                $hide_choose_option = $this->custom_disable_choose_option_box($q['questiontext'],$q['responsesummary']);
            }
            */
            // 2.
        }
        return $hide_choose_option;
    }
    public function attempt_form($attemptobj, $page, $slots, $id, $nextpage) {
        $output = '';
        // Start the form.
        $output .= html_writer::start_tag('form',
                array('action' => new moodle_url($attemptobj->processattempt_url(),
                array('cmid' => $attemptobj->get_cmid())), 'method' => 'post',
                'enctype' => 'multipart/form-data', 'accept-charset' => 'utf-8',
                'id' => 'responseform'));
        $output .= html_writer::start_tag('div');
        /*
            - Customized add an alert box to notify user option is no longer be able to choose
            custom_disable_choose_option_box()
            custom_disabled_multiplates()
         */
        $this->custom_disabled_multiplates();
        $answer_pos = array(0,1,2,3); // is the answer position represents a, b, c, d
        $blank_javascript = '';
        // Print all the questions.
        // call user id logged in.
        global $USER;
        foreach ($slots as $slot) {
            /*
            $output .= html_writer::start_tag('b',array('style'=>'color:red'));
            $output .= $this->cheating($attemptobj->get_attemptid(),$slot);
            $output .= html_writer::end_tag('b');
            */
            $custom_result_next_page = $this->disable_next_page_sequential_behaviour($slot,$USER->id,$attemptobj->get_uniqueid());
            $output .= $attemptobj->render_question($slot, false, $this,
                    $attemptobj->attempt_url($slot, $page), $this);
            $output .=  html_writer::start_tag('p',array('style'=>'display:none!important')).$custom_result_next_page.html_writer::end_tag('p');
            if($custom_result_next_page){ // if condition is valid. Trigger the code to stop option choose
                // Add a for loop answer here to replay the answer option 4 times
                foreach($answer_pos as $a){
                    $blank_javascript .=$this->custom_disable_input_using_attribute_set($attemptobj->get_uniqueid(),$slot, $a);
                }
                $output .= html_writer::start_tag('style').'.qtype_multichoice_clearchoice{display:none!important;}'.html_writer::end_tag('style');
                $blank_javascript .= $this->custom_disable_the_choose_answer_button($attemptobj->get_uniqueid(),$slot);
            }
        }
        $output .= $blank_javascript;
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
