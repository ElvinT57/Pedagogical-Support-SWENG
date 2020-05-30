<?php
    /**
     * @param PDO $dbh The database object.
     * @param int $enrollmentId The id of the enrollment row.
     * @param bool $dropped a boolean integer value of the student's drop status. 1 for dropped, 0 for active.
     * @return void
     */
    function updateDropStatus($dbh, $enrollmentId, $dropped){
        $sql = "UPDATE enrollment SET dropped = :dropped
                WHERE id = :enrollmentId;";

        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':dropped', $dropped);
        $stmt->bindParam(':enrollmentId', $enrollmentId);
        $stmt->execute();

        $result = $stmt->errorInfo();
        return (!isset($result[1]) && !isset($result[2]));
    }

    /**
     * @param PDO $dbh The database object.
     * @param string $bannerid The banner id of the student.
     * @param string $firstName first name of the student.
     * @param string $firstName first name of the student.
     * @param string $firstName first name of the student.
     * @return void
     */
    function addStudent($dbh, $bannerid, $firstName, $lastName, $email) {
        $query = "
            INSERT INTO user (bannerId, firstName, lastName, email) 
            VALUES (:BANNERID, :FIRSTNAME, :LASTNAME, :EMAIL);";

        $stmt = $dbh->prepare($query);
        $stmt->bindParam(':BANNERID', $bannerid);
        $stmt->bindParam(':FIRSTNAME', $firstName);
        $stmt->bindParam(':LASTNAME', $lastName);
        $stmt->bindParam(':EMAIL', $email);
        $stmt->execute();

        $result = $stmt->errorInfo();
        return (!isset($result[1]) && !isset($result[2]));
    }

    /**
     * @param PDO $dbh The database object.
     * @param Integer $userId The user id of the faculty user.
     * @return void
     */
    function addEnrollment($dbh, $userId) {
        $query = "
            INSERT INTO enrollment (section_id, user_id, dropped)
            VALUES ((SELECT MAX(section.id) FROM section), :userId, 0);";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();

        $result = $stmt->errorInfo();
        return (!isset($result[1]) && !isset($result[2]));
    }
?>
