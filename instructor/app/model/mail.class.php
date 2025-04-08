<?php


class mail extends dbh
{

  //  كل الرسائل التي لم يتم إرسالها
  public function getUnsentMails() {
    $sql = "SELECT * FROM mails WHERE !sent";
    $stmt = $this->connect()->query($sql);
    $result = $stmt->fetchAll(PDO::FETCH_OBJ);
    return $result;
  }

  // حذف رسالة تم إرسالها
  public function mailSent($id) {
    $sql = "DELETE FROM mails WHERE id = :id";
    $stmt = $this->connect()->prepare($sql);
    $stmt->bindParam(":id", $id);
    $stmt->execute();
  }

  //  بيانات الطالب مع التحقق من  Password Token
  public function getStudentToken($id) {
    $sql = "SELECT name, email, password_token FROM student WHERE id = :id AND token_expire > NOW()";
    $stmt = $this->connect()->prepare($sql);
    $stmt->bindParam(":id", $id);
    $stmt->execute();
    $result = $stmt->fetch();

    if (!empty($result)) {
      return $result;
    } else {
      return false;
    }
  }

  //  بيانات المعلم مع التحقق من  Password Token
  public function getInstructorToken($id) {
    $sql = "SELECT name, email, password_token FROM instructor WHERE id = :id AND token_expire > NOW()";
    $stmt = $this->connect()->prepare($sql);
    $stmt->bindParam(":id", $id);
    $stmt->execute();
    $result = $stmt->fetch();

    if (!empty($result)) {
      return $result;
    } else {
      return false;
    }
  }

  //  نتيجة اختبار طالب معين
  public function getResult($rid) {
    $sql = "SELECT 
              r.id,
              t.name AS testName,
              s.name AS studentName,
              s.id AS studentID,
              s.email AS studentMail,
              getResultGrade(r.id) AS FinalGrade,
              getTestGrade(r.id) AS TestDegree,
              i.name AS instructorName,
              i.email AS instructorMail
            FROM result r
            INNER JOIN test t ON t.id = r.testID
            INNER JOIN student s ON s.id = r.studentID
            INNER JOIN instructor i ON i.id = t.instructorID
            WHERE r.id = :rid
            GROUP BY t.id, r.id";

    $stmt = $this->connect()->prepare($sql);
    $stmt->bindParam(":rid", $rid);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_OBJ);
    return $result[0];
  }

}
?>
