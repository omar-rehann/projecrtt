<?php
class group extends dbh {

    // الحصول على جميع المجموعات
    public function getAll() {
        $db = $this->connect();
        $query = "SELECT gp.id, name,
                 (SELECT COUNT(*) FROM groups_has_students sg WHERE sg.groupID = gp.id) AS members,
                 (SELECT name FROM test WHERE id = gp.assignedTest) AS assignedTest,
                 gp.assignedTest AS testID,
                 (CASE WHEN (convert_tz(now(), @@session.time_zone, '+02:00') 
                  BETWEEN ts.startTime AND ts.endTime) THEN 1 ELSE 0 END) AS isActive,
                 ts.startTime, ts.endTime, ts.duration, ts.viewAnswers, 
                 gp.instructorID AS instructor
                 FROM groups gp
                 LEFT JOIN test_settings ts ON ts.id = gp.settingID
                 WHERE gp.instructorID = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['mydata']->id]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // الحصول على مجموعة بواسطة المعرف
    public function getByID($id) {
        $db = $this->connect();
        $query = "SELECT gp.id, name,
                 (SELECT COUNT(*) FROM groups_has_students sg WHERE sg.groupID = gp.id) AS members,
                 (SELECT name FROM test WHERE id = gp.assignedTest) AS assignedTest, 
                 gp.assignedTest AS testID,
                 (CASE WHEN (convert_tz(now(), @@session.time_zone, '+02:00') 
                  BETWEEN ts.startTime AND ts.endTime) THEN 1 ELSE 0 END) AS isActive,
                 ts.startTime, ts.endTime, ts.duration, ts.sendToStudent, 
                 ts.releaseResult, ts.passPercent, ts.sendToInstructor, 
                 ts.viewAnswers, gp.instructorID AS instructor
                 FROM groups gp
                 LEFT JOIN test_settings ts ON ts.id = gp.settingID
                 WHERE gp.instructorID = ? AND gp.id = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['mydata']->id, $id]);
        $result = $stmt->fetchAll(PDO::FETCH_OBJ);
        return $result[0];
    }

    // الحصول على أعضاء المجموعة
    public function getMembers($id) {
        $db = $this->connect();
        $query = "SELECT id, name, email, phone, 
                 (CASE WHEN s.password IS NULL || s.password = '' THEN 0 ELSE 1 END) AS registered,
                 joinDate 
                 FROM groups_has_students
                 INNER JOIN student s ON s.id = studentID 
                 WHERE groupID = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // حذف مجموعة
    public function delete($id) {
        $db = $this->connect();
        $query = "DELETE FROM groups WHERE id = ? AND instructorID = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$id, $_SESSION['mydata']->id]);
    }

    // إضافة مجموعة جديدة
    public function insert($name) {
        try {
            $db = $this->connect();
            $query = "INSERT INTO groups(name, instructorID) VALUES (?, ?)";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$name, $_SESSION['mydata']->id]);
            return true;
        } catch (PDOException $e) {
            error_log("Error adding group: " . $e->getMessage());
            return false;
        }
    }

    // تحديث اسم المجموعة
    public function update($id, $name) {
        try {
            $db = $this->connect();
            $query = "UPDATE groups SET name = ? WHERE id = ? AND instructorID = ?";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$name, $id, $_SESSION['mydata']->id]);
            return true;
        } catch (PDOException $e) {
            error_log("Error updating group: " . $e->getMessage());
            return false;
        }
    }

    // إضافة أعضاء للمجموعة
    public function addMembers($groupID, $students) {
        try {
            $db = $this->connect();
            $query = "INSERT IGNORE INTO groups_has_students(groupID, studentID, joinDate) VALUES ";
            
            $values = [];
            foreach($students as $studentID) {
                $values[] = "($groupID, $studentID, convert_tz(now(), @@session.time_zone, '+02:00'))";
            }
            
            $query .= implode(",", $values);
            $db->exec($query);
            return true;
        } catch (PDOException $e) {
            error_log("Error adding members: " . $e->getMessage());
            return false;
        }
    }

    // إزالة عضو من المجموعة
    public function removeMember($groupID, $studentID) {
        $db = $this->connect();
        $query = "DELETE FROM groups_has_students 
                 WHERE studentID = ? AND groupID = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$studentID, $groupID]);
    }

    // التحقق من وجود اسم مجموعة
    public function checkName($name) {
        $db = $this->connect();
        $query = "SELECT * FROM groups WHERE name = ? AND instructorID = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$name, $_SESSION['mydata']->id]);
        return $stmt->fetchColumn() > 0;
    }
}