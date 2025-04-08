<?php

class Result extends Dbh { // الكلاس يرث من كلاس قاعدة البيانات Dbh

    // دالة لجلب جميع النتائج
    public function getAll() {
        // تحديد الاستعلام بناءً على صلاحية المستخدم
        if ($_SESSION['mydata']->isAdmin) {
            // استعلام للمدير
            $query = "SELECT r.id, r.testID, t.name AS testName, s.name AS studentName, 
                      r.studentID, r.startTime, r.endTime, 
                      ipaddr, hostname, 
                      getResultGrade(r.id) AS FinalGrade, 
                      getResultMaxGrade(r.id) AS TestDegree
                      FROM result r
                      INNER JOIN test t ON t.id = r.testID
                      INNER JOIN student s ON s.id = r.studentID
                      GROUP BY t.id, r.id
                      ORDER BY r.endTime DESC";
            $statement = $this->connect()->prepare($query);
        } else {
            // استعلام للمدرس
            $query = "SELECT r.id, r.testID, t.name AS testName, s.name AS studentName, 
                      r.studentID, r.startTime, r.endTime, 
                      ipaddr, hostname, 
                      getResultGrade(r.id) AS FinalGrade, 
                      getResultMaxGrade(r.id) AS TestDegree
                      FROM result r
                      INNER JOIN test t ON t.id = r.testID
                      INNER JOIN student s ON s.id = r.studentID
                      WHERE t.instructorID = :aid AND !r.isTemp AND getResultMaxGrade(r.id) > 0
                      GROUP BY t.id, r.id
                      ORDER BY r.endTime DESC";
            $statement = $this->connect()->prepare($query);
            $statement->bindParam(":aid", $_SESSION['mydata']->id);
        }
        
        $statement->execute();
        $results = $statement->fetchAll(PDO::FETCH_OBJ);
        return $results;
    }

    // دالة لجلب النتائج غير المقدمة
    public function getUnsubmitted() {
        // استعلام لجلب النتائج غير المكتملة للمدرس
        $query = "SELECT r.id, r.testID, t.name AS testName, s.name AS studentName, 
                  r.studentID, r.startTime, r.endTime,
                  (SELECT name FROM student WHERE id = r.studentID) AS student, 
                  ipaddr, hostname,
                  getResultGrade(r.id) AS FinalGrade,
                  getResultMaxGrade(r.id) AS TestDegree
                  FROM result r
                  INNER JOIN test t ON t.id = r.testID
                  INNER JOIN student s ON s.id = r.studentID
                  WHERE t.instructorID = :aid AND !r.isTemp AND getResultMaxGrade(r.id) = 0
                  GROUP BY t.id, r.id
                  ORDER BY r.endTime DESC";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":aid", $_SESSION['mydata']->id); // ربط معرف المدرس
        $statement->execute(); // تنفيذ الاستعلام
        $results = $statement->fetchAll(PDO::FETCH_OBJ); // جلب النتائج
        return $results; // إرجاع النتائج
    }

    // دالة لجلب نتائج اختبار معين
    public function getTestResults($testID) {
        // استعلام لجلب نتائج اختبار محدد
        $query = "SELECT r.id, r.testID, t.name AS testName, s.name AS studentName, 
                  r.studentID, r.startTime, r.endTime,
                  (SELECT name FROM student WHERE id = r.studentID) AS student, 
                  ipaddr, hostname,
                  getResultGrade(r.id) AS FinalGrade,
                  getResultMaxGrade(r.id) AS TestDegree
                  FROM result r
                  INNER JOIN test t ON t.id = r.testID
                  INNER JOIN student s ON s.id = r.studentID
                  WHERE t.instructorID = :aid AND !r.isTemp AND t.id = :testID AND getResultMaxGrade(r.id) > 0
                  GROUP BY t.id, r.id
                  ORDER BY r.endTime DESC";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":testID", $testID); // ربط معرف الاختبار
        $statement->bindParam(":aid", $_SESSION['mydata']->id); // ربط معرف المدرس
        $statement->execute(); // تنفيذ الاستعلام
        $results = $statement->fetchAll(PDO::FETCH_OBJ); // جلب النتائج
        return $results; // إرجاع النتائج
    }

    // دالة لجلب نتائج مجموعة معينة
    public function getGroupResults($groupID) {
        // استعلام لجلب نتائج مجموعة محددة
        $query = "SELECT r.id, r.testID, t.name AS testName, s.name AS studentName, 
                  r.studentID, r.startTime, r.endTime,
                  (SELECT name FROM student WHERE id = r.studentID) AS student, 
                  ipaddr, hostname,
                  getResultGrade(r.id) AS FinalGrade,
                  getResultMaxGrade(r.id) AS TestDegree
                  FROM result r
                  INNER JOIN test t ON t.id = r.testID
                  INNER JOIN student s ON s.id = r.studentID
                  WHERE t.instructorID = :aid AND !r.isTemp AND r.groupID = :groupID
                  GROUP BY t.id, r.id
                  ORDER BY r.endTime DESC";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":groupID", $groupID); // ربط معرف المجموعة
        $statement->bindParam(":aid", $_SESSION['mydata']->id); // ربط معرف المدرس
        $statement->execute(); // تنفيذ الاستعلام
        $results = $statement->fetchAll(PDO::FETCH_OBJ); // جلب النتائج
        return $results; // إرجاع النتائج
    }

    // دالة لجلب نتيجة معينة بناءً على المعرف
    public function getByID($resultID) {
        // استعلام لجلب تفاصيل نتيجة محددة
        $query = "SELECT r.id, t.name AS testName, t.id AS testID, r.startTime, r.endTime, ts.endTime AS testEnd,
                  TIMESTAMPDIFF(MINUTE, r.startTime, r.endTime) AS resultDuration, ipaddr, hostname,
                  ts.passPercent, ts.duration AS testDuration,
                  s.name AS studentName, s.id AS studentID, s.email AS studentMail, s.phone AS studentPhone,
                  (SELECT COUNT(DISTINCT (questionID)) FROM result_answers WHERE resultID = r.id) AS Questions,
                  Result_CorrectQuestions(r.id) AS RightQuestions,
                  Result_WrongQuestions(r.id) AS WrongQuestions,
                  getResultGrade(r.id) AS FinalGrade,
                  getResultMaxGrade(r.id) AS TestDegree
                  FROM result r
                  INNER JOIN test t ON t.id = r.testID
                  INNER JOIN student s ON s.id = r.studentID
                  LEFT JOIN test_settings ts ON ts.id = r.settingID
                  WHERE r.id = :rid
                  GROUP BY t.id, r.id";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":rid", $resultID); // ربط معرف النتيجة
        $statement->execute(); // تنفيذ الاستعلام
        $results = $statement->fetchAll(PDO::FETCH_OBJ); // جلب النتائج
        return $results[0]; // إرجاع أول نتيجة (كائن واحد)
    }

    // دالة لجلب إجابات نتيجة معينة
    public function getResultAnswers($resultID) {
        // استعلام لجلب إجابات الطالب في نتيجة معينة
        $query = "SELECT q.id, q.question, type, q.isTrue,
                  CASE type WHEN 4 THEN TRIM(ROUND(SUM(ra.points), 1)) + 0
                  ELSE TRIM(ROUND(MIN(ra.points), 1)) + 0 END AS points
                  FROM result_answers ra
                  LEFT JOIN question q ON q.id = ra.questionID
                  WHERE resultID = :rid
                  GROUP BY q.id";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":rid", $resultID); // ربط معرف النتيجة
        $statement->execute(); // تنفيذ الاستعلام
        $results = $statement->fetchAll(PDO::FETCH_OBJ); // جلب النتائج
        return $results; // إرجاع النتائج
    }

    // دالة لجلب الإجابات الصحيحة لسؤال معين
    public function getCorrectAnswers($questionID) {
        // استعلام لجلب الإجابات الصحيحة لسؤال
        $query = "SELECT * FROM question_answers WHERE questionID = :qid AND isCorrect ORDER BY id";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":qid", $questionID); // ربط معرف السؤال
        $statement->execute(); // تنفيذ الاستعلام
        $results = $statement->fetchAll(PDO::FETCH_OBJ); // جلب النتائج
        return $results; // إرجاع النتائج
    }

    // دالة لجلب الإجابات التي قدمها الطالب لسؤال معين
    public function getGivenAnswers($resultID, $questionID) {
        // استعلام لجلب إجابات الطالب لسؤال في نتيجة معينة
        $query = "SELECT answer, textAnswer, ra.isCorrect, TRIM(ra.points) + 0 AS points, isTrue 
                  FROM result_answers ra
                  LEFT JOIN question_answers qa ON qa.id = ra.answerID
                  WHERE ra.questionID = :qid AND ra.resultID = :rid 
                  ORDER BY qa.id";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":qid", $questionID); // ربط معرف السؤال
        $statement->bindParam(":rid", $resultID); // ربط معرف النتيجة
        $statement->execute(); // تنفيذ الاستعلام
        $results = $statement->fetchAll(PDO::FETCH_OBJ); // جلب النتائج
        return $results; // إرجاع النتائج
    }

    // دالة لجلب الأسئلة التي تحتاج إلى مراجعة
    public function questionsNeedsReview() {
        // استعلام لجلب الأسئلة التي تحتاج إلى تقييم يدوي
        $query = "SELECT ra.id, s.name AS StudentName, s.id AS StudentID, q.question, ra.textAnswer, q.points 
                  FROM result_answers ra
                  INNER JOIN student s ON s.id = (SELECT studentID FROM result WHERE id = ra.resultID)
                  INNER JOIN question q ON q.id = ra.questionID
                  WHERE ra.points < 0 AND q.instructorID = :instructorID";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":instructorID", $_SESSION['mydata']->id); // ربط معرف المدرس
        $statement->execute(); // تنفيذ الاستعلام
        $results = $statement->fetchAll(PDO::FETCH_OBJ); // جلب النتائج
        return $results; // إرجاع النتائج
    }

    // دالة لجلب تقرير سؤال معين
    public function getQuestionReport($questionID) {
        // استدعاء إجراء مخزن لجلب تقرير السؤال
        $query = "CALL `Question_getQuestionReport`(:aid)";
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->execute(array(":aid" => $questionID)); // تنفيذ الاستعلام مع تمرير معرف السؤال
        $results = $statement->fetchAll(PDO::FETCH_OBJ); // جلب النتائج
        return $results[0]; // إرجاع أول نتيجة (كائن واحد)
    }

    // دالة لقبول أو تعديل إجابة
    public function acceptAnswer($answerID, $accept = 0, $points = 0) {
        // استعلام لتحديث حالة الإجابة ونقاطها
        $query = "UPDATE result_answers SET
                  isCorrect = :accept,
                  points = :points
                  WHERE id = :id";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":id", $answerID); // ربط معرف الإجابة
        $statement->bindParam(":accept", $accept); // ربط حالة القبول
        $statement->bindParam(":points", $points); // ربط النقاط
        $statement->execute(); // تنفيذ الاستعلام
    }
}

?>