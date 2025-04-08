<?php
require_once 'dbh.class.php';
class group extends dbh {

  function getMyGroups() {
      $stmt = $this->connect()->prepare("SELECT g.id, g.`name`,
        (SELECT name FROM instructor WHERE id = g.instructorID) instructor, gs.joinDate
        FROM groups_has_students gs
        INNER JOIN groups g ON gs.groupID = g.id
        WHERE gs.studentID = :studID");
      $stmt->bindparam(":studID", $_SESSION['student']->id);
      $stmt->execute();
      return $stmt->fetchAll(PDO::FETCH_OBJ);
  }

  public function leaveGroup($groupID) {
      $stmt = $this->connect()->prepare("DELETE FROM groups_has_students WHERE groupID = :groupID AND studentID = :studID");
      $stmt->bindparam(":groupID", $groupID);
      $stmt->bindparam(":studID", $_SESSION['student']->id);
      $stmt->execute();
      return 1;
  }

}
