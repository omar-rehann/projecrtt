<?php

class course extends dbh
{
    // الحصول على جميع المواد الرئيسية
    public function getAllParents()
    {
        $db = $this->connect();
        $query = "SELECT id, name, parent, instructorID,
                 (SELECT COUNT(*) FROM question WHERE courseID = c.id AND !deleted) AS questions,
                 (SELECT COUNT(*) FROM course WHERE parent = c.id) AS childs,
                 (SELECT COUNT(*) FROM test WHERE courseID = c.id) AS tests
                 FROM course c 
                 WHERE instructorID = ? AND parent IS NULL";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['mydata']->id]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // الحصول على جميع الموضوعات الفرعية
    public function getAllChilds($parentid)
    {
        $db = $this->connect();
        $query = "SELECT id, name, parent, instructorID,
                 (SELECT COUNT(*) FROM question WHERE courseID = c.id AND !deleted) AS questions,
                 (SELECT COUNT(*) FROM test WHERE courseID = c.id) AS tests
                 FROM course c 
                 WHERE instructorID = ? AND parent = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['mydata']->id, $parentid]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // التحقق من عدم وجود موضوعات فرعية
    public function noCourses()
    {
        $db = $this->connect();
        $query = "SELECT id FROM course WHERE instructorID = ? AND parent IS NOT NULL";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['mydata']->id]);
        return $stmt->rowCount() == 0;
    }

    // حذف مادة / موضوع
    public function delete($cid)
    {
        $db = $this->connect();
        $query = "DELETE FROM course WHERE id = ? AND instructorID = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$cid, $_SESSION['mydata']->id]);
    }

    // إضافة مادة / موضوع جديد
    public function insert($name, $course)
    {
        try {
            $db = $this->connect();
            $query = "INSERT INTO course(name, parent, instructorID) VALUES (?, ?, ?)";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$name, $course, $_SESSION['mydata']->id]);
            return true;
        } catch (PDOException $e) {
            error_log("Error inserting course: " . $e->getMessage());
            return false;
        }
    }

    // تحديث بيانات مادة/ موضوع
    public function update($id, $name, $parent)
    {
        try {
            $db = $this->connect();
            $query = "UPDATE course SET name = ?, parent = ? WHERE id = ?";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$name, $parent, $id]);
            return true;
        } catch (PDOException $e) {
            error_log("Error updating course: " . $e->getMessage());
            return false;
        }
    }

    // التحقق من وجود اسم مادة/موضوع
    public function checkName($name, $parent)
    {
        $db = $this->connect();
        $query = "SELECT * FROM course
                  WHERE name = ?
                  AND (parent = ? OR ISNULL(parent))
                  AND instructorID = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$name, $parent, $_SESSION['mydata']->id]);
        return $stmt->fetchColumn() > 0;
    }

    // التحقق من وجود موضوعات فرعية
    public function TopicsExists()
    {
        $db = $this->connect();
        $query = "SELECT COUNT(*) AS count FROM course 
                  WHERE parent IS NOT NULL AND instructorID = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['mydata']->id]);
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        return $result->count > 0;
    }
}