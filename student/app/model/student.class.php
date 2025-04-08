<?php
require_once 'dbh.class.php'; // استيراد كلاس قاعدة البيانات

class Student extends dbh { // الكلاس يرث من كلاس قاعدة البيانات Dbh

    // دالة لتسجيل دخول الطالب
    public function login($studentID, $password) {
        // استعلام للتحقق من معرف الطالب وكلمة المرور
        $query = "SELECT id, password FROM student WHERE id = :id AND password = :password";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":id", $studentID); // ربط معرف الطالب
        $statement->bindParam(":password", $password); // ربط كلمة المرور
        $statement->execute(); // تنفيذ الاستعلام
        $count = $statement->rowCount(); // جلب عدد الصفوف المطابقة
        
        if ($count > 0) {
            return true; // إرجاع نجاح إذا كانت البيانات صحيحة
        } else {
            return false; // إرجاع فشل إذا لم تتطابق البيانات
        }
    }

    // دالة للتحقق من جلسة الطالب
    public function checkSession($studentID) {
        $sessionID = session_id(); // جلب معرف الجلسة الحالية
        
        // استعلام للتحقق من وجود الجلسة للطالب
        $query = "SELECT sessionID FROM student WHERE id = :id AND sessionID = :sessionID";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":id", $studentID); // ربط معرف الطالب
        $statement->bindParam(":sessionID", $sessionID); // ربط معرف الجلسة
        $statement->execute(); // تنفيذ الاستعلام
        $count = $statement->rowCount(); // جلب عدد الصفوف
        
        if ($count > 0) {
            return true; // إرجاع نجاح إذا كانت الجلسة صحيحة
        } else {
            return false; // إرجاع فشل إذا لم تكن الجلسة صحيحة
        }
    }

    // دالة لتسجيل جلسة الطالب
    public function setSession($studentID) {
        $sessionID = session_id(); // جلب معرف الجلسة الحالية
        
        // استعلام لتحديث معرف الجلسة للطالب
        $query = "UPDATE student SET sessionID = :sessionID WHERE id = :id";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":id", $studentID); // ربط معرف الطالب
        $statement->bindParam(":sessionID", $sessionID); // ربط معرف الجلسة
        $statement->execute(); // تنفيذ الاستعلام
    }

    // دالة للتحقق من وجود بريد إلكتروني
    public function checkEmail($email) {
        // استعلام للتحقق من وجود البريد الإلكتروني
        $query = "SELECT id FROM student WHERE email = :email";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":email", $email); // ربط البريد الإلكتروني
        $statement->execute(); // تنفيذ الاستعلام
        $count = $statement->rowCount(); // جلب عدد الصفوف
        
        if ($count > 0) {
            return true; // إرجاع نجاح إذا وجد البريد
        } else {
            return false; // إرجاع فشل إذا لم يوجد
        }
    }

    // دالة للتحقق من وجود رقم هاتف
    public function checkPhone($phone) {
        // استعلام للتحقق من وجود رقم الهاتف
        $query = "SELECT id FROM student WHERE phone = :phone";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":phone", $phone); // ربط رقم الهاتف
        $statement->execute(); // تنفيذ الاستعلام
        $count = $statement->rowCount(); // جلب عدد الصفوف
        
        if ($count > 0) {
            return true; // إرجاع نجاح إذا وجد الرقم
        } else {
            return false; // إرجاع فشل إذا لم يوجد
        }
    }

    // دالة للتحقق من معرف الطالب بدون كلمة مرور
    public function checkID($studentID) {
        // استعلام للتحقق من وجود معرف الطالب بدون كلمة مرور
        $query = "SELECT id FROM student WHERE id = :id AND password IS NULL";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":id", $studentID); // ربط معرف الطالب
        $statement->execute(); // تنفيذ الاستعلام
        $count = $statement->rowCount(); // جلب عدد الصفوف
        
        if ($count > 0) {
            return true; // إرجاع نجاح إذا وجد الطالب بدون كلمة مرور
        } else {
            return false; // إرجاع فشل إذا لم يوجد
        }
    }

    // دالة للتحقق من رمز إعادة تعيين كلمة المرور
    public function checkPasswordToken($email, $token) {
        // استعلام للتحقق من الرمز وتاريخ انتهاء الصلاحية
        $query = "SELECT 1 FROM student WHERE password_token = :token AND email = :email AND token_expire > NOW()";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":token", $token); // ربط الرمز
        $statement->bindParam(":email", $email); // ربط البريد الإلكتروني
        $statement->execute(); // تنفيذ الاستعلام
        $count = $statement->rowCount(); // جلب عدد الصفوف
        
        if ($count > 0) {
            return true; // إرجاع نجاح إذا كان الرمز صحيحًا ولم تنتهِ صلاحيته
        } else {
            return false; // إرجاع فشل إذا كان الرمز غير صحيح أو منتهي الصلاحية
        }
    }

    // دالة لتحديث بيانات الطالب (البريد ورقم الهاتف)
    public function updateInfo($email, $phone) {
        try {
            // استعلام لتحديث البريد الإلكتروني ورقم الهاتف
            $query = "UPDATE student 
                      SET email = :email, phone = :phone 
                      WHERE id = :id";
            
            $statement = $this->connect()->prepare($query); // تحضير الاستعلام
            $statement->bindParam(":id", $_SESSION['student']->id); // ربط معرف الطالب من الجلسة
            $statement->bindParam(":email", $email); // ربط البريد الإلكتروني
            $statement->bindParam(":phone", $phone); // ربط رقم الهاتف
            $statement->execute(); // تنفيذ الاستعلام
            return true; // إرجاع نجاح العملية
        } catch (PDOException $error) {
            echo $error->getMessage(); // عرض رسالة الخطأ إذا حدث
            return false; // إرجاع فشل العملية
        }
    }

    // دالة لتحديث كلمة المرور
    public function updatePassword($email, $password) {
        try {
            // استعلام لتحديث كلمة المرور وإزالة الرمز
            $query = "UPDATE student 
                      SET password = :password, password_token = NULL, token_expire = NULL 
                      WHERE email = :email";
            
            $statement = $this->connect()->prepare($query); // تحضير الاستعلام
            $statement->bindParam(":password", $password); // ربط كلمة المرور الجديدة
            $statement->bindParam(":email", $email); // ربط البريد الإلكتروني
            $statement->execute(); // تنفيذ الاستعلام
            return true; // إرجاع نجاح العملية
        } catch (PDOException $error) {
            echo $error->getMessage(); // عرض رسالة الخطأ إذا حدث
            return false; // إرجاع فشل العملية
        }
    }

    // دالة لتوليد رمز إعادة تعيين كلمة المرور
    public function generatePasswordToken($email, $token) {
        try {
            // استعلام لتحديث الرمز وتاريخ الانتهاء وإضافة بريد للإرسال
            $query = "UPDATE student 
                      SET password_token = :token, token_expire = DATE_ADD(NOW(), INTERVAL 30 MINUTE) 
                      WHERE email = :email;
                      INSERT INTO mails(studentID, sends_at, type)
                      SELECT id, convert_tz(NOW(), @@session.time_zone, '+02:00'), 0 
                      FROM student WHERE email = :email";
            
            $statement = $this->connect()->prepare($query); // تحضير الاستعلام
            $statement->bindParam(":email", $email); // ربط البريد الإلكتروني
            $statement->bindParam(":token", $token); // ربط الرمز
            $statement->execute(); // تنفيذ الاستعلام
            return true; // إرجاع نجاح العملية
        } catch (PDOException $error) {
            echo $error->getMessage(); // عرض رسالة الخطأ إذا حدث
            return false; // إرجاع فشل العملية
        }
    }

    // دالة لجلب بيانات طالب بناءً على المعرف
    public function getByID($studentID) {
        // استعلام لجلب جميع بيانات الطالب
        $query = "SELECT * FROM student WHERE id = :id";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":id", $studentID); // ربط معرف الطالب
        $statement->execute(); // تنفيذ الاستعلام
        $results = $statement->fetchAll(PDO::FETCH_OBJ); // جلب النتائج ككائنات
        return $results[0]; // إرجاع أول نتيجة (كائن واحد)
    }
}

?>