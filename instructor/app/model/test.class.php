<?php

class test extends dbh
{

    public function getAll()
{
    $stmt = $this->connect()->prepare("SELECT id, name,
    instructorID, courseID,
    (SELECT name FROM course WHERE id = courseID) as course,
    (SELECT count(*) FROM tests_has_questions WHERE testID = t.id) as fixedQuestions,
    (SELECT count(*) FROM result WHERE testID = t.id) as inResults,
    (SELECT count(*) FROM test_invitations WHERE testID = t.id) links
    FROM test t WHERE instructorID = :aid AND !deleted");

    $stmt->bindparam(":aid", $_SESSION['mydata']->id);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_OBJ);
    return $result;
}

public function getDeleted()
{
    $stmt = $this->connect()->prepare("SELECT id, name,
    instructorID, courseID,
    (SELECT name FROM course WHERE id = courseID) as course,
    (SELECT count(*) FROM tests_has_questions WHERE testID = t.id) as fixedQuestions,
    (SELECT count(*) FROM result WHERE testID = t.id) as inResults,
    (SELECT count(*) FROM groups WHERE assignedTest = t.id) inGroups
    FROM test t WHERE instructorID = :aid AND deleted");

    $stmt->bindparam(":aid", $_SESSION['mydata']->id);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_OBJ);
    return $result;
}

public function getByID($id)
{
    $stmt = $this->connect()->prepare("SELECT id, name,
    instructorID, courseID, 
    (SELECT name FROM course WHERE id = courseID) as course,
    (SELECT count(*) FROM test_invitations WHERE testID = t.id) as assignedToLinks,
    (SELECT count(*) FROM tests_has_questions WHERE testID = t.id) as fixedQuestions,
    (SELECT count(*) FROM test_invitations WHERE testID = t.id) links,
    (SELECT count(*) FROM result WHERE testID = t.id) as inResults,
    getTestGrade(id) as TestGrade
    FROM test t WHERE instructorID = :aid AND id = :id");

    $stmt->bindparam(":id", $id);
    $stmt->bindparam(":aid", $_SESSION['mydata']->id);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_OBJ);
    return $result[0];
}

    public function getTestSessions()
    {
      $stmt = $this->connect()->prepare("select testID,settingID,(SELECT name from test t where t.id = r.testID) testName,ts.startTime,ts.endTime,
        count(r.id) results,ts.viewAnswers,ts.releaseResult,
        (CASE WHEN r.groupID IS NULL THEN 'Assigned To Link'
        ELSE CONCAT('Assigned To Group ', (select name from groups g where g.id = r.groupID))
        END) as type
        from result r
        INNER JOIN test_settings ts
        on r.settingID = ts.id
        WHERE ts.instructorID = :aid
        group by testID, settingID, groupID
        ORDER BY ts.startTime DESC");
        $stmt->bindparam(":aid", $_SESSION['mydata']->id);
        $stmt->execute();
        $result=$stmt->fetchAll(PDO::FETCH_OBJ);
        return $result;
    }
    public function lastAddedTest()
    {
      $stmt = $this->connect()->prepare("SELECT MAX(id) maxid from test where instructorID = :instID");
        $stmt->bindparam(":instID", $_SESSION['mydata']->id);
        $stmt->execute();
        $result=$stmt->fetchAll(PDO::FETCH_OBJ);
        if(!empty($result)){
          return $result[0]->maxid;
        }else{
          return false;
        }

    }

    public function getTestInvitations($testID){
        $stmt = $this->connect()->prepare("SELECT ti.id,name,HEX(AES_ENCRYPT(id, 'final')) as invite,
                CASE
                WHEN ((convert_tz(now(),@@session.time_zone,'+02:00') between ts.startTime AND ts.endTime) and useLimit > used) THEN
                  1
                ELSE
                  0
              END as status
              FROM
              test_invitations ti
              inner join test_settings ts
              on ts.id = ti.settingID
             where instructorID = :instID and testID = :testID");
        $stmt->bindparam(":instID",$_SESSION['mydata']->id);
        $stmt->bindparam(":testID",$testID);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_OBJ);
        return $result;
    }
    public function getTestName($testID){
        $stmt = $this->connect()->prepare("select name from test where id = :testID AND instructorID = :instID");
        $stmt->bindparam(":instID",$_SESSION['mydata']->id);
        $stmt->bindparam(":testID",$testID);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_OBJ);
        if($result[0])
          return $result[0]->name;
        else
          return false;
    }
    public function duplicateTest($testID){
        $stmt = $this->connect()->prepare("INSERT INTO test(name, courseID, instructorID)
        SELECT CONCAT('Copy of ', name), courseID, instructorID FROM test WHERE id = :tID;
        
        INSERT INTO tests_has_questions(testID, questionID)
        SELECT (SELECT MAX(id) FROM test WHERE instructorID = :instID), questionID 
        FROM tests_has_questions WHERE testID = :tID;");
    
        $stmt->bindparam(":instID", $_SESSION['mydata']->id);
        $stmt->bindparam(":tID", $testID);
        $stmt->execute();
        return true;
    }
    
    public function testAverage($testID){
        $stmt = $this->connect()->prepare("select round(avg(getResultGrade(r.id))) AS average from result r where testID = :testID");
        $stmt->bindparam(":testID",$testID);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_OBJ);
        if($result[0])
          return $result[0]->average;
        else
          return false;
    }
    public function getQuestionsInTest($testID)
    {
        $stmt = $this->connect()->prepare("
          SELECT id, question,type,CASE q.type when 4 then (select sum(qa.points) from question_answers qa where questionID = q.id)
          ELSE q.points END as questionGrade,difficulty,
          (Select name from course where id = courseID) as course
          FROM question q
          INNER JOIN tests_has_questions  tq
          ON tq.questionID = q.id AND tq.testID = :tid
          WHERE instructorID = :aid and deleted = 0
          AND id IN (SELECT questionID FROM tests_has_questions WHERE testID = :tid)");
        $stmt->bindparam(":tid", $testID);
        $stmt->bindparam(":aid", $_SESSION['mydata']->id);
        $stmt->execute();
        $result=$stmt->fetchAll(PDO::FETCH_OBJ);
        return $result;
    }
    public function getQuestionsNotInTest($testID)
    {
        $stmt = $this->connect()->prepare("SELECT id, question,type,difficulty,
          (Select name from course where id = courseID) as course,
          CASE q.type when 4 then (select sum(qa.points) from question_answers qa where questionID = q.id)
          ELSE q.points END as points
          FROM question q
          WHERE instructorID = :aid and deleted = 0
          AND id NOT IN (SELECT questionID FROM tests_has_questions WHERE testID = :tid)");
        $stmt->bindparam(":tid", $testID);
        $stmt->bindparam(":aid", $_SESSION['mydata']->id);
        $stmt->execute();
        $result=$stmt->fetchAll(PDO::FETCH_OBJ);
        return $result;
    }
    public function setTestDelete($id)
    {
        $stmt=$this->connect()->prepare("UPDATE test SET
                                        deleted = 1
                                        WHERE id=:id and instructorID = :aid");
        $stmt->bindparam(":id", $id);
        $stmt->bindparam(":aid", $_SESSION['mydata']->id);
        $stmt->execute();
    }
    public function releaseResult($id)
    {
        $stmt=$this->connect()->prepare("UPDATE test_settings SET
                                        releaseResult = 1
                                        WHERE id=:id");
        $stmt->bindparam(":id", $id);
        $stmt->execute();
    }
    public function sendMails($testID,$settingID)
    {
        $stmt=$this->connect()->prepare("INSERT into mails(resultID,sends_at,type)
        select id,convert_tz(now(),@@session.time_zone,'+02:00'),2 from result r
        where testID = :tid
        AND settingID = :sid");
        $stmt->bindparam(":tid", $testID);
        $stmt->bindparam(":sid", $settingID);
        $stmt->execute();
    }
    public function viewAnswers($id)
    {
        $stmt=$this->connect()->prepare("UPDATE test_settings SET
                                        viewAnswers = 0
                                        WHERE id=:id");
        $stmt->bindparam(":id", $id);
        $stmt->execute();
    }
    public function hideAnswers($id)
    {
        $stmt=$this->connect()->prepare("UPDATE test_settings SET
                                        viewAnswers = 2
                                        WHERE id = :id");
        $stmt->bindparam(":id", $id);
        $stmt->execute();
    }
    public function restoreTest($id)
    {
        $stmt=$this->connect()->prepare("UPDATE test SET
                                        deleted = 0
                                        WHERE id=:id and instructorID = :aid");
        $stmt->bindparam(":id", $id);
        $stmt->bindparam(":aid", $_SESSION['mydata']->id);
        $stmt->execute();
    }
    public function delete($tid)
    {
        $stmt=$this->connect()->prepare("DELETE FROM test
                                        WHERE id=:tid and instructorID = :aid ");
        $stmt->bindparam(":tid", $tid);
        $stmt->bindparam(":aid", $_SESSION['mydata']->id);

        $stmt->execute();
    }
   
    public function insert($name, $course)
    {
        try {
            $stmt = $this->connect()->prepare("INSERT INTO test(name,courseID,instructorID)
                                                              VALUES(:name,:courseID, :instructorID)");
            $stmt->bindparam(":name", $name);
            $stmt->bindparam(":courseID", $course);
            $stmt->bindparam(":instructorID", $_SESSION['mydata']->id);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            echo $e->getMessage();
            return false;
        }
    }
    public function checkAvailableCount($testID,$courseID,$diff,$count)
    {
        $stmt = $this->connect()->prepare("SELECT count(*) as count from question
        where id not in (select questionID FROM tests_has_questions where testID = :testID)
        and courseID = :courseID
        and !deleted
        and difficulty = :diff");
        $stmt->bindparam(":testID", $testID);
        $stmt->bindparam(":courseID", $courseID);
        $stmt->bindparam(":diff", $diff);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_OBJ);
        if ($result[0]->count >= $count) {
            return true;
        } else {
            return false;
        }
    }
    public function addQuestionsToTest($testID,$questions)
    {
        try {
            $sql = 'INSERT INTO tests_has_questions(testID,questionID) VALUES';
            foreach($questions as $q){
              if(is_numeric($q))
              $sql .= ' (' . $testID . ','. $q . '),';
            }
            $sql = rtrim($sql,',');
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            echo $e->getMessage();
            return false;
        }
    }
    public function deleteQuestionsFromTest($testID,$questions)
    {
        try {
            $sql = 'DELETE FROM tests_has_questions WHERE testID = '. $testID .' AND questionID IN ';
            $sql .= ' (';
            foreach($questions as $q){
              if(is_numeric($q))
                $sql .= $q.',';
            }
            $sql = rtrim($sql,',');
            $sql .= ');';
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            echo $e->getMessage();
            return false;
        }
    }
    public function update($id, $name, $course)
    {
        try {
            $stmt = $this->connect()->prepare("UPDATE test SET name = :name,courseID = :cid where id = :id and instructorID = :aid;");
            $stmt->bindparam(":name", $name);
            $stmt->bindparam(":cid", $course);
            $stmt->bindparam(":id", $id);
            $stmt->bindparam(":aid", $_SESSION['mydata']->id);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            echo $e->getMessage();
            return false;
        }
    }
    public function checkID($id)
    {
        $stmt = $this->connect()->prepare("SELECT * FROM test WHERE id= :id and instructorID = :aid");
        $stmt->bindparam(":id", $id);
        $stmt->bindparam(":aid", $_SESSION['mydata']->id);
        $stmt->execute();
        $result = $stmt->fetchColumn();
        if ($result > 0) {
            return true;
        } else {
            return false;
        }
    }
    public function isReadOnly($id)
    {
        $stmt = $this->connect()->prepare("SELECT count(id) cnt from result where testID = :id");
        $stmt->bindparam(":id", $id);
        $stmt->execute();
        $result = $stmt->fetchColumn();
        if ($result > 0) {
            return true;
        } else {
            return false;
        }
    }

}
