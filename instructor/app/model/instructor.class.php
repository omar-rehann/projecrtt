<?php

class Instructor extends dbh {

    // الحصول  كل الدكاترة 
    public function getAll() {
        $sql = "SELECT id, name, password, email, phone, isAdmin FROM instructor";
        $stmt = $this->connect()->query($sql);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // الحصول علي  دكتور عن طريق الايميل
    public function getByEmail($email) {
        $sql = "SELECT id, name, password, email, phone, isAdmin FROM instructor WHERE email = :email";
        $stmt = $this->connect()->prepare($sql);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // التحقق هل الايميل موجود
    public function checkEmail($email) {
        $sql = "SELECT id FROM instructor WHERE email = :email";
        $stmt = $this->connect()->prepare($sql);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // تسجيل دخول
    public function login($email, $password) {
        $sql = "SELECT email, password FROM instructor WHERE email = :email AND password = :password AND !suspended";
        $stmt = $this->connect()->prepare($sql);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":password", $password);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // التحقق من حساب دكتور موجود ولا لا
    public function checkAccount($id) {
        $sql = "SELECT 1 FROM instructor WHERE id = :id AND !suspended";
        $stmt = $this->connect()->prepare($sql);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // تسجيل حساب جديد
    public function register($name, $password, $email, $phone, $invite = null) {
        try {
            if ($invite != null) {
                $sql = "DELETE FROM instructor_invitations WHERE code = :code;
                        INSERT INTO instructor(name, password, email, phone) VALUES(:name, :password, :email, :phone)";
                $stmt = $this->connect()->prepare($sql);
                $stmt->bindParam(":code", $invite);
            } else {
                $sql = "INSERT INTO instructor(name, password, email, phone) VALUES(:name, :password, :email, :phone)";
                $stmt = $this->connect()->prepare($sql);
            }

            $stmt->bindParam(":name", $name);
            $stmt->bindParam(":password", $password);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":phone", $phone);
            $stmt->execute();
            return true;

        } catch (PDOException $e) {
            echo $e->getMessage();
            return false;
        }
    }

    // تعديل بيانات الدكتور
    public function updateInfo($name, $email, $phone) {
        try {
            $sql = "UPDATE instructor SET name = :name, email = :email, phone = :phone WHERE id = :id";
            $stmt = $this->connect()->prepare($sql);
            $stmt->bindParam(":id", $_SESSION['mydata']->id);
            $stmt->bindParam(":name", $name);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":phone", $phone);
            $stmt->execute();
            return true;

        } catch (PDOException $e) {
            echo $e->getMessage();
            return false;
        }
    }

    // تعديل كلمة المرور
    public function updatePassword($email, $password) {
        try {
            $sql = "UPDATE instructor SET password = :password WHERE email = :email";
            $stmt = $this->connect()->prepare($sql);
            $stmt->bindParam(":password", $password);
            $stmt->bindParam(":email", $email);
            $stmt->execute();
            return true;

        } catch (PDOException $e) {
            echo $e->getMessage();
            return false;
        }
    }

    // إعادة تعيين كلمة المرور
    public function resetPassword($email, $password) {
        try {
            $sql = "UPDATE instructor 
                    SET password = :password, password_token = null, token_expire = null 
                    WHERE email = :email";
            $stmt = $this->connect()->prepare($sql);
            $stmt->bindParam(":password", $password);
            $stmt->bindParam(":email", $email);
            $stmt->execute();
            return true;

        } catch (PDOException $e) {
            echo $e->getMessage();
            return false;
        }
    }

    // توليد رمز لتغيير كلمة المرور
    public function generatePasswordToken($email, $token) {
        try {
            $sql = "UPDATE instructor 
                    SET password_token = :token, token_expire = DATE_ADD(NOW(), INTERVAL 30 MINUTE) 
                    WHERE email = :email;
                    INSERT INTO mails(instructorID, sends_at, type)
                    SELECT id, CONVERT_TZ(NOW(), @@session.time_zone, '+02:00'), 1 FROM instructor WHERE email = :email";

            $stmt = $this->connect()->prepare($sql);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":token", $token);
            $stmt->execute();
            return true;

        } catch (PDOException $e) {
            echo $e->getMessage();
            return false;
        }
    }

    // التحقق من صحة رمز تغيير كلمة المرور
    public function isValidReset($email, $token) {
        $sql = "SELECT 1 FROM instructor WHERE password_token = :token AND email = :email AND token_expire > NOW()";
        $stmt = $this->connect()->prepare($sql);
        $stmt->bindParam(":token", $token);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
