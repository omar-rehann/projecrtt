<?php

class Student extends Dbh { // الكلاس يرث من كلاس قاعدة البيانات Dbh

    // دالة لجلب طلاب المدرس
    public function getMyStudents() {
        // استعلام لجلب الطلاب الذين لهم نتائج اختبارات مع المدرس الحالي
        $query = "SELECT DISTINCT s.id, s.name, s.email, s.phone, s.suspended 
                  FROM result r
                  INNER JOIN student s ON r.studentID = s.id
                  INNER JOIN test t ON t.id = r.testID AND t.instructorID = :instID";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":instID", $_SESSION['mydata']->id); // ربط معرف المدرس
        $statement->execute(); // تنفيذ الاستعلام
        $results = $statement->fetchAll(PDO::FETCH_OBJ); // جلب النتائج ككائنات
        return $results; // إرجاع النتائج
    }

    // دالة لجلب جميع معرفات الطلاب
    public function getAllIDs() {
        // استعلام بسيط جدًا لجلب معرفات الطلاب فقط
        $query = "SELECT id FROM student";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->execute(); // تنفيذ الاستعلام
        $results = $statement->fetchAll(PDO::FETCH_OBJ); // جلب النتائج
        return $results; // إرجاع النتائج
    }

    // دالة لجلب الطلاب غير المسجلين
    public function getUnregistered() {
        // استعلام لجلب الطلاب الذين لم يسجلوا (كلمة المرور فارغة)
        $query = "SELECT * FROM student WHERE password IS NULL";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->execute(); // تنفيذ الاستعلام
        $results = $statement->fetchAll(PDO::FETCH_OBJ); // جلب النتائج
        return $results; // إرجاع النتائج
    }

    // دالة لجلب نتائج طالب معين
    public function getStudentResults($studentID) {
        // استعلام لجلب نتائج الطالب في اختبارات المدرس الحالي
        $query = "SELECT r.id, r.testID, t.name AS testName, s.name AS studentName, 
                  r.studentID, r.startTime, r.endTime,
                  (SELECT name FROM student WHERE id = r.studentID) AS student, 
                  ipaddr, hostname,
                  getResultGrade(r.id) AS FinalGrade,
                  getResultMaxGrade(r.id) AS TestDegree
                  FROM result r
                  INNER JOIN test t ON t.id = r.testID AND t.instructorID = :instID
                  INNER JOIN student s ON s.id = r.studentID
                  WHERE r.studentID = :studentID AND !r.isTemp
                  GROUP BY t.id, r.id
                  ORDER BY r.endTime DESC";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":instID", $_SESSION['mydata']->id); // ربط معرف المدرس
        $statement->bindParam(":studentID", $studentID); // ربط معرف الطالب
        $statement->execute(); // تنفيذ الاستعلام
        $results = $statement->fetchAll(PDO::FETCH_OBJ); // جلب النتائج
        return $results; // إرجاع النتائج
    }

    // دالة لإضافة طلاب جدد
    public function addStudents($students) {
        try {
            // بناء استعلام لإضافة الطلاب مع تجاهل التكرارات
            $query = 'INSERT IGNORE INTO student(id) VALUES';
            foreach ($students as $studentID) {
                $query .= ' (' . $studentID . '),'; // إضافة كل معرف طالب
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

    // دالة لحذف طالب
    public function deleteStudent($studentID) {
        // استعلام لحذف طالب بناءً على المعرف
        $query = "DELETE FROM student WHERE id = :id";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":id", $studentID); // ربط معرف الطالب
        $statement->execute(); // تنفيذ الاستعلام
    }
}

?>