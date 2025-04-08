<?php
session_start();
require_once '../model/test.class.php';
require_once '../model/student.class.php';
require_once 'function.php';

if ($_GET['action'] == 'getQuestions'){
  $cls = new test();
  $question = $cls->getMyQuestions();
  echo $question;
}else if (($_GET['action'] == 'getQuestionAnswers') && isset($_GET['id'])){
  $cls = new test();
  $answers = $cls->getQuestionAnswers($_GET['id']);
  echo $answers;
}else if (($_GET['action'] == 'initiateTest')){
  $_test = new test();
  if($_test->checkTestTaken()){
    echo 'You have already taken this test';
  }elseif($_test->checkActiveTest() != 0){
    echo 'You have an active test.., You can\'t take more then one test at the same time';
  }else {
    $_test->InitiateTest(); // بدء الاختبار
    $_test->InitiateFixed(); // تهيئة الاختبار بالأسئلة الثابتة فقط
    echo 'success'; // طباعة رسالة النجاح
}
}else if ($_GET['action'] == 'submitAnswers'){
  $_student = new student;
  if(empty($_POST['questions'])){
    echo 'You don\'t have answers to submit';
  }elseif(!$_student->checkSession($_SESSION['student']->id)){
    echo 'You signed in from different Device';
  }else{
    $_test = new test();
    $_test->submitAnswers($_POST['questions']);
    $_test->FinishTest();
    $_test->reviewAnswers();
    $_test->sendResultMails();
   echo 'success';
  }
}
