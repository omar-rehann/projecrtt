<?php

class Test extends Dbh { // الكلاس يرث من كلاس قاعدة البيانات Dbh

    // دالة لجلب جميع الاختبارات غير المحذوفة
    public function getAll() {
        // استعلام لجلب جميع الاختبارات للمدرس الحالي
        $query = "SELECT id, name, instructorID, courseID,
                  (SELECT name FROM course WHERE id = courseID) AS course,
                  (SELECT COUNT(*) FROM tests_has_questions WHERE testID = t.id) AS fixedQuestions,
                  (SELECT COUNT(*) FROM result WHERE testID = t.id) AS inResults,
                  (SELECT COUNT(*) FROM test_invitations WHERE testID = t.id) AS links
                  FROM test t 
                  WHERE instructorID = :aid AND !deleted";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":aid", $_SESSION['mydata']->id); // ربط معرف المدرس
        $statement->execute(); // تنفيذ الاستعلام
        $results = $statement->fetchAll(PDO::FETCH_OBJ); // جلب النتائج ككائنات
        return $results; // إرجاع النتائج
    }

    // دالة لجلب الاختبارات المحذوفة
    public function getDeleted() {
        // استعلام لجلب الاختبارات المحذوفة للمدرس الحالي
        $query = "SELECT id, name, instructorID, courseID,
                  (SELECT name FROM course WHERE id = courseID) AS course,
                  (SELECT COUNT(*) FROM tests_has_questions WHERE testID = t.id) AS fixedQuestions,
                  (SELECT COUNT(*) FROM result WHERE testID = t.id) AS inResults,
                  (SELECT COUNT(*) FROM groups WHERE assignedTest = t.id) AS inGroups
                  FROM test t 
                  WHERE instructorID = :aid AND deleted";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":aid", $_SESSION['mydata']->id); // ربط معرف المدرس
        $statement->execute(); // تنفيذ الاستعلام
        $results = $statement->fetchAll(PDO::FETCH_OBJ); // جلب النتائج
        return $results; // إرجاع النتائج
    }

    // دالة لجلب اختبار معين بناءً على المعرف
    public function getByID($testID) {
        // استعلام لجلب تفاصيل اختبار محدد
        $query = "SELECT id, name, instructorID, courseID,
                  (SELECT name FROM course WHERE id = courseID) AS course,
                  (SELECT COUNT(*) FROM test_invitations WHERE testID = t.id) AS assignedToLinks,
                  (SELECT COUNT(*) FROM tests_has_questions WHERE testID = t.id) AS fixedQuestions,
                  (SELECT COUNT(*) FROM test_invitations WHERE testID = t.id) AS links,
                  (SELECT COUNT(*) FROM result WHERE testID = t.id) AS inResults,
                  getTestGrade(id) AS TestGrade
                  FROM test t 
                  WHERE instructorID = :aid AND id = :id";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":id", $testID); // ربط معرف الاختبار
        $statement->bindParam(":aid", $_SESSION['mydata']->id); // ربط معرف المدرس
        $statement->execute(); // تنفيذ الاستعلام
        $results = $statement->fetchAll(PDO::FETCH_OBJ); // جلب النتائج
        return $results[0]; // إرجاع أول نتيجة (كائن واحد)
    }

    // دالة لجلب جلسات الاختبار
    public function getTestSessions() {
        // استعلام لجلب جلسات الاختبار مع تفاصيلها
        $query = "SELECT testID, settingID, 
                  (SELECT name FROM test t WHERE t.id = r.testID) AS testName, 
                  ts.startTime, ts.endTime,
                  COUNT(r.id) AS results, ts.viewAnswers, ts.releaseResult,
                  (CASE WHEN r.groupID IS NULL THEN 'Assigned To Link'
                  ELSE CONCAT('Assigned To Group ', (SELECT name FROM groups g WHERE g.id = r.groupID))
                  END) AS type
                  FROM result r
                  INNER JOIN test_settings ts ON r.settingID = ts.id
                  WHERE ts.instructorID = :aid
                  GROUP BY testID, settingID, groupID
                  ORDER BY ts.startTime DESC";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":aid", $_SESSION['mydata']->id); // ربط معرف المدرس
        $statement->execute(); // تنفيذ الاستعلام
        $results = $statement->fetchAll(PDO::FETCH_OBJ); // جلب النتائج
        return $results; // إرجاع النتائج
    }

    // دالة لجلب معرف آخر اختبار تم إضافته
    public function lastAddedTest() {
        // استعلام لجلب أعلى معرف اختبار للمدرس
        $query = "SELECT MAX(id) AS maxid FROM test WHERE instructorID = :instID";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":instID", $_SESSION['mydata']->id); // ربط معرف المدرس
        $statement->execute(); // تنفيذ الاستعلام
        $results = $statement->fetchAll(PDO::FETCH_OBJ); // جلب النتائج
        
        if (!empty($results)) {
            return $results[0]->maxid; // إرجاع أعلى معرف
        } else {
            return false; // إرجاع خطأ إذا لم يكن هناك اختبارات
        }
    }

    // دالة لجلب دعوات الاختبار
    public function getTestInvitations($testID) {
        // استعلام لجلب تفاصيل دعوات الاختبار
        $query = "SELECT ti.id, name, HEX(AES_ENCRYPT(id, 'final')) AS invite,
                  CASE
                  WHEN ((convert_tz(now(), @@session.time_zone, '+02:00') BETWEEN ts.startTime AND ts.endTime) AND useLimit > used) THEN 1
                  ELSE 0
                  END AS status
                  FROM test_invitations ti
                  INNER JOIN test_settings ts ON ts.id = ti.settingID
                  WHERE instructorID = :instID AND testID = :testID";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":instID", $_SESSION['mydata']->id); // ربط معرف المدرس
        $statement->bindParam(":testID", $testID); // ربط معرف الاختبار
        $statement->execute(); // تنفيذ الاستعلام
        $results = $statement->fetchAll(PDO::FETCH_OBJ); // جلب النتائج
        return $results; // إرجاع النتائج
    }

    // دالة لجلب اسم الاختبار
    public function getTestName($testID) {
        // استعلام لجلب اسم الاختبار
        $query = "SELECT name FROM test WHERE id = :testID AND instructorID = :instID";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":instID", $_SESSION['mydata']->id); // ربط معرف المدرس
        $statement->bindParam(":testID", $testID); // ربط معرف الاختبار
        $statement->execute(); // تنفيذ الاستعلام
        $results = $statement->fetchAll(PDO::FETCH_OBJ); // جلب النتائج
        
        if ($results[0]) {
            return $results[0]->name; // إرجاع اسم الاختبار
        } else {
            return false; // إرجاع خطأ إذا لم يتم العثور على الاختبار
        }
    }

    // دالة لنسخ اختبار
    public function duplicateTest($testID) {
        // استعلام لنسخ الاختبار وأسئلته
        $query = "INSERT INTO test(name, courseID, instructorID)
                  SELECT CONCAT('Copy of ', name), courseID, instructorID FROM test WHERE id = :tID;
                  INSERT INTO tests_has_questions(testID, questionID)
                  SELECT (SELECT MAX(id) FROM test WHERE instructorID = :instID), questionID 
                  FROM tests_has_questions WHERE testID = :tID";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":instID", $_SESSION['mydata']->id); // ربط معرف المدرس
        $statement->bindParam(":tID", $testID); // ربط معرف الاختبار
        $statement->execute(); // تنفيذ الاستعلام
        return true; // إرجاع نجاح العملية
    }

    // دالة لحساب متوسط درجات الاختبار
    public function testAverage($testID) {
        // استعلام لحساب متوسط الدرجات
        $query = "SELECT ROUND(AVG(getResultGrade(r.id))) AS average FROM result r WHERE testID = :testID";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":testID", $testID); // ربط معرف الاختبار
        $statement->execute(); // تنفيذ الاستعلام
        $results = $statement->fetchAll(PDO::FETCH_OBJ); // جلب النتائج
        
        if ($results[0]) {
            return $results[0]->average; // إرجاع المتوسط
        } else {
            return false; // إرجاع خطأ إذا لم يكن هناك نتائج
        }
    }

    // دالة لجلب الأسئلة الموجودة في الاختبار
    public function getQuestionsInTest($testID) {
        // استعلام لجلب الأسئلة المرتبطة بالاختبار
        $query = "SELECT id, question, type, 
                  CASE q.type WHEN 4 THEN (SELECT SUM(qa.points) FROM question_answers qa WHERE questionID = q.id)
                  ELSE q.points END AS questionGrade, difficulty,
                  (SELECT name FROM course WHERE id = courseID) AS course
                  FROM question q
                  INNER JOIN tests_has_questions tq ON tq.questionID = q.id AND tq.testID = :tid
                  WHERE instructorID = :aid AND deleted = 0
                  AND id IN (SELECT questionID FROM tests_has_questions WHERE testID = :tid)";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":tid", $testID); // ربط معرف الاختبار
        $statement->bindParam(":aid", $_SESSION['mydata']->id); // ربط معرف المدرس
        $statement->execute(); // تنفيذ الاستعلام
        $results = $statement->fetchAll(PDO::FETCH_OBJ); // جلب النتائج
        return $results; // إرجاع النتائج
    }

    // دالة لجلب الأسئلة غير الموجودة في الاختبار
    public function getQuestionsNotInTest($testID) {
        // استعلام لجلب الأسئلة غير المرتبطة بالاختبار
        $query = "SELECT id, question, type, difficulty,
                  (SELECT name FROM course WHERE id = courseID) AS course,
                  CASE q.type WHEN 4 THEN (SELECT SUM(qa.points) FROM question_answers qa WHERE questionID = q.id)
                  ELSE q.points END AS points
                  FROM question q
                  WHERE instructorID = :aid AND deleted = 0
                  AND id NOT IN (SELECT questionID FROM tests_has_questions WHERE testID = :tid)";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":tid", $testID); // ربط معرف الاختبار
        $statement->bindParam(":aid", $_SESSION['mydata']->id); // ربط معرف المدرس
        $statement->execute(); // تنفيذ الاستعلام
        $results = $statement->fetchAll(PDO::FETCH_OBJ); // جلب النتائج
        return $results; // إرجاع النتائج
    }

    // دالة لتحديد الاختبار كمحذوف
    public function setTestDelete($testID) {
        // استعلام لتحديث حالة الاختبار إلى محذوف
        $query = "UPDATE test SET deleted = 1 WHERE id = :id AND instructorID = :aid";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":id", $testID); // ربط معرف الاختبار
        $statement->bindParam(":aid", $_SESSION['mydata']->id); // ربط معرف المدرس
        $statement->execute(); // تنفيذ الاستعلام
    }

    // دالة للإفراج عن نتائج الاختبار
    public function releaseResult($settingID) {
        // استعلام لتحديث حالة إصدار النتائج
        $query = "UPDATE test_settings SET releaseResult = 1 WHERE id = :id";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":id", $settingID); // ربط معرف الإعداد
        $statement->execute(); // تنفيذ الاستعلام
    }

    // دالة لإرسال رسائل بريدية للنتائج
    public function sendMails($testID, $settingID) {
        // استعلام لإضافة رسائل بريدية للنتائج
        $query = "INSERT INTO mails(resultID, sends_at, type)
                  SELECT id, convert_tz(now(), @@session.time_zone, '+02:00'), 2 
                  FROM result r
                  WHERE testID = :tid AND settingID = :sid";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":tid", $testID); // ربط معرف الاختبار
        $statement->bindParam(":sid", $settingID); // ربط معرف الإعداد
        $statement->execute(); // تنفيذ الاستعلام
    }

    // دالة لعرض الإجابات
    public function viewAnswers($settingID) {
        // استعلام لتحديث حالة عرض الإجابات
        $query = "UPDATE test_settings SET viewAnswers = 0 WHERE id = :id";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":id", $settingID); // ربط معرف الإعداد
        $statement->execute(); // تنفيذ الاستعلام
    }

    // دالة لإخفاء الإجابات
    public function hideAnswers($settingID) {
        // استعلام لتحديث حالة إخفاء الإجابات
        $query = "UPDATE test_settings SET viewAnswers = 2 WHERE id = :id";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":id", $settingID); // ربط معرف الإعداد
        $statement->execute(); // تنفيذ الاستعلام
    }

    // دالة لاستعادة اختبار محذوف
    public function restoreTest($testID) {
        // استعلام لاستعادة الاختبار
        $query = "UPDATE test SET deleted = 0 WHERE id = :id AND instructorID = :aid";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":id", $testID); // ربط معرف الاختبار
        $statement->bindParam(":aid", $_SESSION['mydata']->id); // ربط معرف المدرس
        $statement->execute(); // تنفيذ الاستعلام
    }
    // دالة لحذف اختبار نهائيًا
    public function delete($testID) {
        // استعلام لحذف الاختبار نهائيًا
        $query = "DELETE FROM test WHERE id = :tid AND instructorID = :aid";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":tid", $testID); // ربط معرف الاختبار
        $statement->bindParam(":aid", $_SESSION['mydata']->id); // ربط معرف المدرس
        $statement->execute(); // تنفيذ الاستعلام
    }

    // دالة لإضافة اختبار جديد
    public function insert($testName, $courseID) {
        try {
            // استعلام لإضافة اختبار جديد
            $query = "INSERT INTO test(name, courseID, instructorID)
                      VALUES (:name, :courseID, :instructorID)";
            
            $statement = $this->connect()->prepare($query); // تحضير الاستعلام
            $statement->bindParam(":name", $testName); // ربط اسم الاختبار
            $statement->bindParam(":courseID", $courseID); // ربط معرف المادة
            $statement->bindParam(":instructorID", $_SESSION['mydata']->id); // ربط معرف المدرس
            $statement->execute(); // تنفيذ الاستعلام
            return true; // إرجاع نجاح العملية
        } catch (PDOException $error) {
            echo $error->getMessage(); // عرض رسالة الخطأ إذا حدث
            return false; // إرجاع فشل العملية
        }
    }

    // دالة للتحقق من توفر عدد كافٍ من الأسئلة
    public function checkAvailableCount($testID, $courseID, $difficulty, $requiredCount) {
        // استعلام للتحقق من عدد الأسئلة المتاحة
        $query = "SELECT COUNT(*) AS count FROM question
                  WHERE id NOT IN (SELECT questionID FROM tests_has_questions WHERE testID = :testID)
                  AND courseID = :courseID
                  AND !deleted
                  AND difficulty = :diff";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":testID", $testID); // ربط معرف الاختبار
        $statement->bindParam(":courseID", $courseID); // ربط معرف المادة
        $statement->bindParam(":diff", $difficulty); // ربط مستوى الصعوبة
        $statement->execute(); // تنفيذ الاستعلام
        $results = $statement->fetchAll(PDO::FETCH_OBJ); // جلب النتائج
        
        if ($results[0]->count >= $requiredCount) {
            return true; // إرجاع نجاح إذا كان العدد كافيًا
        } else {
            return false; // إرجاع فشل إذا لم يكن العدد كافيًا
        }
    }

    // دالة لإضافة أسئلة إلى الاختبار
    public function addQuestionsToTest($testID, $questions) {
        try {
            // بناء استعلام لإضافة الأسئلة
            $query = 'INSERT INTO tests_has_questions(testID, questionID) VALUES';
            foreach ($questions as $questionID) {
                if (is_numeric($questionID)) {
                    $query .= ' (' . $testID . ',' . $questionID . '),'; // إضافة كل سؤال
                }
            }
            $query = rtrim($query, ','); // إزالة الفاصلة الأخيرة
            
            $statement = $this->connect()->prepare($query); // تحضير الاستعلام
            $statement->execute(); // تنفيذ الاستعلام
            return true; // إرجاع نجاح العملية
        } catch (PDOException $error) {
            echo $error->getMessage(); // عرض رسالة الخطأ إذا حدث
            return false; // إرجاع فشل العملية
        }
    }

    // دالة لحذف أسئلة من الاختبار
    public function deleteQuestionsFromTest($testID, $questions) {
        try {
            // بناء استعلام لحذف الأسئلة
            $query = 'DELETE FROM tests_has_questions WHERE testID = ' . $testID . ' AND questionID IN (';
            foreach ($questions as $questionID) {
                if (is_numeric($questionID)) {
                    $query .= $questionID . ','; // إضافة كل سؤال
                }
            }
            $query = rtrim($query, ','); // إزالة الفاصلة الأخيرة
            $query .= ')';
            
            $statement = $this->connect()->prepare($query); // تحضير الاستعلام
            $statement->execute(); // تنفيذ الاستعلام
            return true; // إرجاع نجاح العملية
        } catch (PDOException $error) {
            echo $error->getMessage(); // عرض رسالة الخطأ إذا حدث
            return false; // إرجاع فشل العملية
        }
    }

    // دالة لتحديث بيانات الاختبار
    public function update($testID, $testName, $courseID) {
        try {
            // استعلام لتحديث اسم الاختبار والمادة
            $query = "UPDATE test SET name = :name, courseID = :cid WHERE id = :id AND instructorID = :aid";
            
            $statement = $this->connect()->prepare($query); // تحضير الاستعلام
            $statement->bindParam(":name", $testName); // ربط اسم الاختبار
            $statement->bindParam(":cid", $courseID); // ربط معرف المادة
            $statement->bindParam(":id", $testID); // ربط معرف الاختبار
            $statement->bindParam(":aid", $_SESSION['mydata']->id); // ربط معرف المدرس
            $statement->execute(); // تنفيذ الاستعلام
            return true; // إرجاع نجاح العملية
        } catch (PDOException $error) {
            echo $error->getMessage(); // عرض رسالة الخطأ إذا حدث
            return false; // إرجاع فشل العملية
        }
    }

    // دالة للتحقق من وجود الاختبار
    public function checkID($testID) {
        // استعلام للتحقق من وجود الاختبار
        $query = "SELECT * FROM test WHERE id = :id AND instructorID = :aid";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":id", $testID); // ربط معرف الاختبار
        $statement->bindParam(":aid", $_SESSION['mydata']->id); // ربط معرف المدرس
        $statement->execute(); // تنفيذ الاستعلام
        $result = $statement->fetchColumn(); // جلب عدد الصفوف
        
        if ($result > 0) {
            return true; // إرجاع نجاح إذا وجد الاختبار
        } else {
            return false; // إرجاع فشل إذا لم يوجد
        }
    }

    // دالة للتحقق مما إذا كان الاختبار للقراءة فقط
    public function isReadOnly($testID) {
        // استعلام للتحقق من وجود نتائج للاختبار
        $query = "SELECT COUNT(id) AS cnt FROM result WHERE testID = :id";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":id", $testID); // ربط معرف الاختبار
        $statement->execute(); // تنفيذ الاستعلام
        $result = $statement->fetchColumn(); // جلب العدد
        
        if ($result > 0) {
            return true; // إرجاع نجاح إذا كان للقراءة فقط
        } else {
            return false; // إرجاع فشل إذا لم يكن للقراءة فقط
        }
    }
}

?>