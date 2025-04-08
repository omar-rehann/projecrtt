<?php
class question extends dbh {

    // الحصول على جميع الأسئلة
    public function getAll($courseID = null) {
        $db = $this->connect();
        
        if($courseID == null) {
            $query = "SELECT id, question, type, isTrue, 
                     CASE q.type 
                         WHEN 4 THEN (SELECT SUM(qa.points) FROM question_answers qa WHERE qa.questionID = q.id)
                         ELSE q.points 
                     END AS points, difficulty,
                     (SELECT name FROM course WHERE id = courseID) AS course
                     FROM question q 
                     WHERE instructorID = ? AND deleted = 0";
            $stmt = $db->prepare($query);
            $stmt->execute([$_SESSION['mydata']->id]);
        } else {
            $query = "SELECT id, question, type, isTrue, 
                     CASE q.type 
                         WHEN 4 THEN (SELECT SUM(qa.points) FROM question_answers qa WHERE qa.questionID = q.id)
                         ELSE q.points 
                     END AS points, difficulty,
                     (SELECT name FROM course WHERE id = courseID) AS course
                     FROM question q 
                     WHERE instructorID = ? AND !deleted AND courseID = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$_SESSION['mydata']->id, $courseID]);
        }
        
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // الحصول على الأسئلة المحذوفة
    public function getDeleted() {
        $db = $this->connect();
        $query = "SELECT id, question, type, isTrue,
                 CASE q.type 
                     WHEN 4 THEN (SELECT SUM(qa.points) FROM question_answers qa WHERE qa.questionID = q.id)
                     ELSE q.points 
                 END AS points, difficulty,
                 (SELECT name FROM course WHERE id = courseID) AS course,
                 (SELECT COUNT(*) FROM result_answers WHERE questionID = q.id) AS used
                 FROM question q 
                 WHERE instructorID = ? AND deleted";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['mydata']->id]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // الحصول على أسئلة مقرر معين
    public function getByCourse($courseID) {
        $db = $this->connect();
        $query = "SELECT id, question, type, isTrue,
                 CASE q.type 
                     WHEN 4 THEN (SELECT SUM(qa.points) FROM question_answers qa WHERE qa.questionID = q.id)
                     ELSE q.points 
                 END AS points, difficulty,
                 (SELECT name FROM course WHERE id = courseID) AS course
                 FROM question q 
                 WHERE instructorID = ? AND !deleted AND courseID = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['mydata']->id, $courseID]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // الحصول على سؤال بواسطة المعرف
    public function getByID($id) {
        $db = $this->connect();
        $query = "SELECT id, question, type, isTrue,
                 CASE q.type 
                     WHEN 4 THEN (SELECT SUM(qa.points) FROM question_answers qa WHERE qa.questionID = q.id)
                     ELSE q.points 
                 END AS points, difficulty,
                 (SELECT COUNT(*) FROM result_answers WHERE questionID = ?) AS inResults,
                 (SELECT name FROM course WHERE id = courseID) AS course, courseID
                 FROM question q 
                 WHERE instructorID = ? AND id = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$id, $_SESSION['mydata']->id, $id]);
        $result = $stmt->fetchAll(PDO::FETCH_OBJ);
        return $result[0];
    }

    // الحصول على آخر سؤال تم إضافته
    public function getLastQuestion() {
        $db = $this->connect();
        $query = "SELECT * FROM question 
                 WHERE instructorID = ? 
                 ORDER BY id DESC 
                 LIMIT 1";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['mydata']->id]);
        $result = $stmt->fetchAll(PDO::FETCH_OBJ);
        return $result[0];
    }

    // الحصول على تقرير عن السؤال
    public function getQuestionReport($qid) {
        $db = $this->connect();
        $query = "SELECT 
                 q.id, q.question, q.type, q.isTrue, q.difficulty,
                 c.name AS course,
                 (SELECT COUNT(*) FROM tests_has_questions thq WHERE thq.questionID = q.id) AS inTests,
                 COUNT(DISTINCT ra.resultID) AS inResults,
                 (SELECT COUNT(DISTINCT ra1.resultID) 
                  FROM result_answers ra1 
                  WHERE ra1.questionID = q.id AND ra1.isCorrect = 1) AS rightAnswers,
                 (SELECT COUNT(DISTINCT ra2.resultID) 
                  FROM result_answers ra2 
                  WHERE ra2.questionID = q.id AND ra2.isCorrect = 0) AS wrongAnswers
                 FROM question q
                 INNER JOIN course c ON c.id = q.courseID
                 LEFT JOIN result_answers ra ON ra.questionID = q.id
                 WHERE q.id = ?
                 GROUP BY q.id, c.name";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$qid]);
        $result = $stmt->fetchAll(PDO::FETCH_OBJ);
        return $result[0];
    }

    // نسخ السؤال
    public function duplicateQuestion($qid) {
        $db = $this->connect();
        $query = "INSERT INTO question(question, type, points, difficulty, isTrue, instructorID, courseID, deleted)
                 SELECT question, type, points, difficulty, isTrue, ?, courseID, deleted 
                 FROM question WHERE id = ?;
                 
                 INSERT INTO question_answers(questionID, answer, matchAnswer, isCorrect, points)
                 SELECT (SELECT MAX(id) FROM question WHERE instructorID = ?), answer, matchAnswer, isCorrect, points 
                 FROM question_answers WHERE questionID = ?;
                 
                 SELECT MAX(id) AS id FROM question WHERE instructorID = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['mydata']->id, $qid, $_SESSION['mydata']->id, $qid, $_SESSION['mydata']->id]);
    }

    // الحصول على إجابات السؤال
    public function getQuestionAnswers($qid) {
        $db = $this->connect();
        $query = "SELECT * FROM question_answers WHERE questionID = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$qid]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // حذف السؤال (وضع علامة محذوف)
    public function setQuestionDelete($id) {
        $db = $this->connect();
        $query = "UPDATE question SET deleted = 1 WHERE id = ?;
                 DELETE FROM tests_has_questions WHERE questionID = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$id, $id]);
    }

    // استعادة السؤال المحذوف
    public function restoreQuestion($id) {
        $db = $this->connect();
        $query = "UPDATE question SET deleted = 0 WHERE id = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
    }

    // حذف السؤال نهائياً
    public function pDeleteQuestion($qid) {
        $db = $this->connect();
        $query = "DELETE FROM question WHERE id = ? AND instructorID = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$qid, $_SESSION['mydata']->id]);
    }

    // إضافة سؤال جديد
    public function insertQuestion($question, $type, $course, $isTrue, $points, $difficulty) {
        try {
            $db = $this->connect();
            $query = "INSERT INTO question(question, type, instructorID, courseID, isTrue, points, difficulty)
                     VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$question, $type, $_SESSION['mydata']->id, $course, $isTrue, $points, $difficulty]);
            return true;
        } catch (PDOException $e) {
            error_log("Error inserting question: " . $e->getMessage());
            return false;
        }
    }

    // إضافة إجابات لآخر سؤال
    public function insertAnswersToLast($answer, $isCorrect, $matchAnswer = null, $points = 1) {
        try {
            $db = $this->connect();
            $query = "INSERT INTO question_answers(questionID, answer, isCorrect, matchAnswer, points)
                     VALUES ((SELECT MAX(id) FROM question WHERE instructorID = ?), ?, ?, ?, ?)";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$_SESSION['mydata']->id, $answer, $isCorrect, $matchAnswer, $points]);
            return true;
        } catch (PDOException $e) {
            error_log("Error inserting answers: " . $e->getMessage());
            return false;
        }
    }

    // إضافة إجابات لسؤال معين
    public function insertAnswers($qid, $answer, $isCorrect, $matchAnswer = null, $points = 1) {
        try {
            $db = $this->connect();
            $query = "INSERT INTO question_answers(questionID, answer, isCorrect, matchAnswer, points)
                     VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$qid, $answer, $isCorrect, $matchAnswer, $points]);
            return true;
        } catch (PDOException $e) {
            error_log("Error inserting answers: " . $e->getMessage());
            return false;
        }
    }

    // تحديث السؤال
    public function updateQuestion($id, $question, $course, $points, $difficulty) {
        try {
            $db = $this->connect();
            $query = "UPDATE question
                     SET question = ?,
                     courseID = ?,
                     points = ?,
                     difficulty = ?
                     WHERE id = ?";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$question, $course, $points, $difficulty, $id]);
            return true;
        } catch (PDOException $e) {
            error_log("Error updating question: " . $e->getMessage());
            return false;
        }
    }

    // تحديث الإجابة
    public function updateAnswer($ansID, $answer, $isCorrect, $matchAnswer, $points = 1) {
        try {
            $db = $this->connect();
            $query = "UPDATE question_answers
                     SET answer = ?,
                     isCorrect = ?,
                     matchAnswer = ?,
                     points = ?
                     WHERE id = ?";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$answer, $isCorrect, $matchAnswer, $points, $ansID]);
            return true;
        } catch (PDOException $e) {
            error_log("Error updating answer: " . $e->getMessage());
            return false;
        }
    }

    // تحديث سؤال صح/خطأ
    public function updateTF($qID, $isCorrect) {
        try {
            $db = $this->connect();
            $query = "UPDATE question SET isTrue = ? WHERE id = ?";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$isCorrect, $qID]);
            return true;
        } catch (PDOException $e) {
            error_log("Error updating true/false question: " . $e->getMessage());
            return false;
        }
    }

    // حذف الإجابة
    public function deleteAnswer($id) {
        $db = $this->connect();
        $query = "DELETE FROM question_answers WHERE id = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
    }
}