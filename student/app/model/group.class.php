<?php
// نستدعي ملف الاتصال بقاعدة البيانات
require_once 'dbh.class.php';

// ننشئ كلاس group الذي يرث من كلاس الاتصال بقاعدة البيانات
class group extends dbh {

    // دالة للحصول على المجموعات التي ينتمي إليها الطالب
    function getMyGroups() {
        // نقوم بالاتصال بقاعدة البيانات
        $database_connection = $this->connect();
        
        // نجهز استعلام SQL لجلب المجموعات
        $sql_query = "SELECT g.id, g.`name`,
                     (SELECT name FROM instructor WHERE id = g.instructorID) AS instructor, 
                     gs.joinDate
                     FROM groups_has_students gs
                     INNER JOIN groups g ON gs.groupID = g.id
                     WHERE gs.studentID = ?";
        
        // نجهز الاستعلام ونربط المعاملات
        $prepared_statement = $database_connection->prepare($sql_query);
        $prepared_statement->bindParam(1, $_SESSION['student']->id);
        
        // ننفذ الاستعلام
        $prepared_statement->execute();
        
        // نعيد النتائج ككائنات
        return $prepared_statement->fetchAll(PDO::FETCH_OBJ);
    }

    // دالة لمغادرة مجموعة معينة
    public function leaveGroup($groupID) {
        // نقوم بالاتصال بقاعدة البيانات
        $database_connection = $this->connect();
        
        // نجهز استعلام SQL لحذف الطالب من المجموعة
        $sql_query = "DELETE FROM groups_has_students 
                     WHERE groupID = ? AND studentID = ?";
        
        // نجهز الاستعلام ونربط المعاملات
        $prepared_statement = $database_connection->prepare($sql_query);
        $prepared_statement->bindParam(1, $groupID);
        $prepared_statement->bindParam(2, $_SESSION['student']->id);
        
        // ننفذ الاستعلام
        $prepared_statement->execute();
        
        // نعيد 1 للإشارة إلى نجاح العملية
        return 1;
    }
}