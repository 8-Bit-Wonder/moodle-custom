<?php

/* 19/05/2023
        - Customizing the Next Page button can only be visible if certain conditions are met.
        - disable_next_page_sequential_behaviour()
        - cheating() <-- disable this after testing
    */
    public function cheating($id,$slot){
        global $DB;
        $qas = $DB->get_record_sql('SELECT qa.rightanswer cheat_answer
        FROM {question_usages} quba
        INNER JOIN {question_attempts} qa on qa.questionusageid = quba.id 
        INNER JOIN {quiz_attempts} qas on qas.uniqueid = quba.id 
        WHERE qas.id = ? and qa.slot = ?',array('id'=>$id,'slot'=>$slot));
        return $qas->cheat_answer;
    }
    public function disable_next_page_sequential_behaviour($slot_num,$userid,$attempt){
        global $DB;
        $q = array();
        $quiz_user_result = $DB->get_record_sql('SELECT 
        q.preferredbehaviour, q.navmethod,q.questionsperpage,
        qa.rightanswer, qa.responsesummary
        FROM mdl232x0_quiz_attempts qz
        INNER JOIN mdl232x0_quiz q ON q.id = qz.quiz 
        INNER JOIN mdl232x0_course_modules cms ON cms.instance = q.id 
        INNER JOIN mdl232x0_question_usages quba ON quba.id = qz.uniqueid
        INNER JOIN mdl232x0_question_attempts qa   ON qa.questionusageid    = quba.id
        INNER JOIN mdl232x0_question_attempt_steps qas  ON qas.questionattemptid = qa.id
        WHERE quba.id = ? AND qa.slot = ? AND qas.userid = ?
        GROUP BY q.preferredbehaviour, q.navmethod,q.questionsperpage,
        qa.rightanswer, qa.responsesummary',
        array('id'=>$attempt,'slot'=>$slot_num,'userid'=>$userid));
        $q['preferredbehaviour']=$quiz_user_result->preferredbehaviour;
        $q['navmethod']=$quiz_user_result->navmethod;
        $q['questionsperpage']=$quiz_user_result->questionsperpage;
        $q['rightanswer']=$quiz_user_result->rightanswer;
        $q['responsesummary']=$quiz_user_result->responsesummary;

        if($q['preferredbehaviour']=='adaptive'
        &&$q['navmethod']=='sequential'
        &&$q['questionsperpage']==1){ /*If conditions are met, start the custom function to
         block Next Page*/
            // 1. hide Next Page button.
            $disableNextPage = true;
            // 2. Compare the result and answer. If matched => enable the Next Page button
            if($q['rightanswer']==$q['responsesummary']){
                $disableNextPage = false; // update status
            }
            if($disableNextPage){
                $disableNextPageCSS = '';
                $disableNextPageCSS .= html_writer::start_tag('style');
                $disableNextPageCSS .= '#mod_quiz-next-nav{display:none;!important}'; 
                /* ✏️
                  hide the Next Button using CSS to avoid global Javascript collision
                  also because i suck at Javascript 🤡
                */
                $disableNextPageCSS .= html_writer::end_tag('style');
                echo $disableNextPageCSS;
            }
            // 2.
        }
    }
    public function attempt_form($attemptobj, $page, $slots, $id, $nextpage) {
        $output = '';
        echo 'get_uniqueid';
        // Start the form.
        $output .= html_writer::start_tag('form',
                array('action' => new moodle_url($attemptobj->processattempt_url(),
                array('cmid' => $attemptobj->get_cmid())), 'method' => 'post',
                'enctype' => 'multipart/form-data', 'accept-charset' => 'utf-8',
                'id' => 'responseform'));
        $output .= html_writer::start_tag('div');

        // Print all the questions.
        // call user id logged in.
        global $USER;
        foreach ($slots as $slot) {
            $output .= html_writer::start_tag('b',array('style'=>'color:red'));
            $output .= $this->cheating($attemptobj->get_attemptid(),$slot);     //✏️ cheating : display answer next to question
            $output .= html_writer::end_tag('b');
            $this->disable_next_page_sequential_behaviour($slot,$USER->id,$attemptobj->get_uniqueid()); 
            /* ✏️ 
              disable_next_page_sequential_behaviour : hide the Next Button if certain modes are set
            */
            $output .= $attemptobj->render_question($slot, false, $this,
                    $attemptobj->attempt_url($slot, $page), $this);
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

