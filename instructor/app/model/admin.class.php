<?php

class admin extends dbh {
  
  // الحصول على جميع المدربين ( Only Admin)
  public function getAllInstructors() {
    $db = $this->connect();
    $query = "SELECT * FROM instructor WHERE !isAdmin";
    $result = $db->query($query);
    return $result->fetchAll(PDO::FETCH_OBJ);
  }
  
  // الحصول على جميع الطلاب
  public function getAllStudents() {
    $db = $this->connect();
    $query = "SELECT * FROM student";
    $result = $db->query($query);
    return $result->fetchAll(PDO::FETCH_OBJ);
  }
  
  // الحصول على الطلاب غير المسجلين (بدون كلمة مرور)
  public function getUnregistered() {
    $db = $this->connect();
    $query = "SELECT * FROM student WHERE password IS NULL";
    $result = $db->query($query);
    return $result->fetchAll(PDO::FETCH_OBJ);
  }
  
  // الحصول على نتائج طالب معين
  public function getStudentResults($studentID) {
    $db = $this->connect();
    $query = "SELECT r.id, r.testID, t.name AS testName, s.name AS studentName, 
                     r.studentID, r.startTime, r.endTime,
                     (SELECT name FROM student WHERE id = r.studentID) AS student,
                     ipaddr, hostname,
                     getResultGrade(r.id) AS FinalGrade,
                     getResultMaxGrade(r.id) AS TestDegree
              FROM result r
              JOIN test t ON t.id = r.testID
              JOIN student s ON s.id = r.studentID
              WHERE r.studentID = ? AND !r.isTemp
              GROUP BY t.id, r.id
              ORDER BY r.endTime DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$studentID]);
    return $stmt->fetchAll(PDO::FETCH_OBJ);
  }
  
  //   Susspendd Student
  public function suspendStudent($studentID) {
    $db = $this->connect();
    $query = "UPDATE student SET suspended = 1, sessionID = NULL WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$studentID]);
    return true;
  }
  
  //  Activity Student
  public function activateStudent($studentID) {
    $db = $this->connect();
    $query = "UPDATE student SET suspended = 0 WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$studentID]);
    return true;
  }
  
  //  Susspend Instructor
  public function suspendInstructor($instructorID) {
    $db = $this->connect();
    $query = "UPDATE instructor SET suspended = 1 WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$instructorID]);
    return true;
  }
  
  //  Activity Instructor
  public function activateInstructor($instructorID) {
    $db = $this->connect();
    $query = "UPDATE instructor SET suspended = 0 WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$instructorID]);
    return true;
  }
  
  //    Import student
  public function importStudents($values) {
    try {
      $db = $this->connect();
      $query = "INSERT IGNORE INTO student(id, name, email, phone, password) VALUES " . $values;
      $db->exec($query);
      return true;
    } catch (Exception $e) {
      error_log("Error importing students: " . $e->getMessage());
      return false;
    }
  }
  
  //   Add Student
  public function addStudent($id, $name, $email, $phone, $password) {
    try {
      $db = $this->connect();
      $query = "INSERT INTO student(id, name, email, phone, password) VALUES (?, ?, ?, ?, ?)";
      $stmt = $db->prepare($query);
      $stmt->execute([$id, $name, $email, $phone, $password]);
      return true;
    } catch (Exception $e) {
      error_log("Error adding student: " . $e->getMessage());
      return false;
    }
  }
  
  // الحصول على الرسائل غير المرسلة
  public function getUnsentMails() {
    $db = $this->connect();
    $query = "SELECT * FROM mails WHERE !sent";
    $result = $db->query($query);
    return $result->fetchAll(PDO::FETCH_OBJ);
  }
}