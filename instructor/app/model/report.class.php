<?php
class report extends dbh {

  // دالة تعرض احصائيات الاجابات على سؤال معين
  public function questionAnswersStats($questionID, $type) {

    // لو نوع السؤال اختيار من متعدد
    if ($type == 0) {
      $sql = "SELECT 
                (SELECT answer FROM question_answers WHERE id = ra.answerID) AS answer,
                COUNT(answerID) AS c,
                MAX(isCorrect) AS isCorrect
              FROM result_answers ra
              WHERE ra.questionID = :questionID
              GROUP BY answerID
              ORDER BY c DESC";
    }
    // لو نوع السؤال صح وغلط
    elseif ($type == 1) {
      $sql = "SELECT 
                (CASE isTrue WHEN 0 THEN 'False' ELSE 'True' END) AS answer,
                COUNT(*) AS c,
                MAX(isCorrect) AS isCorrect
              FROM result_answers
              WHERE questionID = :questionID
              GROUP BY isTrue
              ORDER BY c DESC";
    }
    // لو اجابة نصية
    else {
      $sql = "SELECT 
                textAnswer AS answer,
                COUNT(*) AS c,
                MAX(isCorrect) AS isCorrect
              FROM result_answers
              WHERE questionID = :questionID 
              AND textAnswer IS NOT NULL
              GROUP BY textAnswer
              ORDER BY c DESC";
    }

    // تنفيذ الاستعلام
    $stmt = $this->connect()->prepare($sql);
    $stmt->bindParam(":questionID", $questionID);
    $stmt->execute();

    // استرجاع النتائج
    return $stmt->fetchAll(PDO::FETCH_OBJ);
  }

  // دالة تعرض الاسئلة الموجودة في اختبار معين
  public function getQuestionsInTest($testID) {

    $sql = "SELECT DISTINCT 
              q.id,
              q.question,
              q.type,
              q.isTrue,
              CASE 
                WHEN q.type = 4 THEN (SELECT SUM(qa.points) FROM question_answers qa WHERE qa.questionID = q.id)
                ELSE q.points
              END AS points
            FROM result_answers ra
            JOIN result r ON ra.resultID = r.id
            JOIN question q ON ra.questionID = q.id
            WHERE testID = :testID";

    $stmt = $this->connect()->prepare($sql);
    $stmt->bindParam(":testID", $testID);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_OBJ);
  }

  // دالة تعرض تقرير عن سؤال معين
  public function getQuestionReport($qID) {

    $sql = "SELECT 
              q.id,
              q.question,
              q.type,
              q.isTrue,
              q.difficulty,
              c.name AS course,
              COUNT(c.id) AS inTests,
              COUNT(DISTINCT ra.resultID) AS inResults,
              (SELECT COUNT(DISTINCT resultID) FROM result_answers WHERE questionID = q.id AND isCorrect) AS rightAnswers,
              (SELECT COUNT(DISTINCT resultID) FROM result_answers WHERE questionID = q.id AND !isCorrect) AS wrongAnswers
            FROM question q
            INNER JOIN course c ON c.id = q.courseID
            INNER JOIN result_answers ra ON ra.questionID = q.id
            WHERE q.id = :qID";

    $stmt = $this->connect()->prepare($sql);
    $stmt->bindParam(":qID", $qID);
    $stmt->execute();

    $result = $stmt->fetchAll(PDO::FETCH_OBJ);

    return $result[0];
  }
}
