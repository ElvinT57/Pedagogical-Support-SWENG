<?php
    //TODO: This should use a relative path, right now the import is assuming that it
    //      is being called from the root directory, if you call this file from another
    //      directory it will break
    /**
     * @param PDO $dbh The database object.
     * @param int $sectionId The id of the section to get all users for.
     * @param userType $userType The type of user to fetch, see constants.php.
     * @return array An array with all users for the section or null if none were found.
     */
    function getUsersForSection($dbh, $sectionId, $userType = USER_STUDENT) {
        $query =
            "SELECT
                user.*
            FROM enrollment
                JOIN user ON user.id = enrollment.user_id
            WHERE enrollment.section_id=:sectionId AND user.userType=:userType;";
        $query = $dbh->prepare($query);
        $query->bindParam(":sectionId", $sectionId);
        $query->bindParam(":userType", $userType);
        $query->execute();

        $result = $query->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    /**
     * TODO: PHPDOC
     */
    function getUser($dbh, $user_id) {
        $sql =  "SELECT *
                FROM user
                WHERE user.id = :user_id;";
    
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
    
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * TODO: PHPDOC
     */
    function getUserWithBannerId($dbh, $bannerId) {
        $sql =  "SELECT *
                FROM user
                WHERE user.bannerId = :bannerId;";
    
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':bannerId', $bannerId);
        $stmt->execute();
    
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * @param PDO $dbh The database object.
     * @param int $sectionId The id of the section to insert the students into.
     * @param array $students the students to insert into the section, will create the students if they don't exist.
     * [0] => Last Name
     * [1] => First Name
     * [2] => Banner Id
     * [3] => User Name
     * [4] => Email
     * @return bool True if the operation succeeded.
     */
    function addStudentsAndEnrollInSection($dbh, $sectionId, $students) {
        $dbh->beginTransaction();
        $error = False;
        $idsToAdd = [];
        foreach($students as $student) {
            if (!isset($student[0]) || !isset($student[1]) || !isset($student[2]) || !isset($student[3]) || !isset($student[4])) {
                $error = True;
                break;
            }

            // we don't want to create users that exist
            $existsQuery = $dbh->prepare("SELECT * FROM user WHERE bannerId=:bannerId;");
            $existsQuery->bindParam(":bannerId", $student[2], PDO::PARAM_INT); //modify to grab the user instead of counting
            $existsQuery->execute();
            $existsResults = $existsQuery->fetch(PDO::FETCH_ASSOC);
            
            if (isset($existsResults['id'])) {
                // we still want to add them to the section
                array_push($idsToAdd, $existsResults['id']);
                continue;
            }
            else {
                $insertUserQuery =
                    "INSERT INTO user
                            (bannerId, firstName, lastName, email, userName)
                        VALUES
                            (:BANNERID, :FIRSTNAME, :LASTNAME, :EMAIL, :USERNAME);";

                // creating a new user if one doesn't exist
                $insertUserStmt = $dbh->prepare($insertUserQuery);
                $insertUserStmt->bindParam(':BANNERID', $student[2]);
                $insertUserStmt->bindParam(':FIRSTNAME', $student[1]);
                $insertUserStmt->bindParam(':LASTNAME', $student[0]);
                $insertUserStmt->bindParam(':EMAIL', $student[4]);
                $insertUserStmt->bindParam(':USERNAME', $student[3]);

                $result = $insertUserStmt->execute();

                $insertStmt = $dbh->prepare("SELECT LAST_INSERT_ID() AS 'id';");
                $insertStmt->execute();

                $insertId = $insertStmt->fetch(PDO::FETCH_ASSOC)['id'];
                array_push($idsToAdd, $insertId);
                    
                $errorInfo = $insertStmt->errorInfo();
                if (isset($errorInfo[1]) || isset($errorInfo[2])) {
                    $error = True;
                    break;
                }
            }
        }

        if (!$error) {
            foreach ($idsToAdd as $userId) {
                // skip users that are already registered
                $existsQuery = $dbh->prepare("SELECT COUNT(*) AS 'matches' FROM enrollment WHERE user_id=:userId AND section_id=:sectionId;");
                $existsQuery->bindParam(":userId", $userId, PDO::PARAM_INT);
                $existsQuery->bindParam(":sectionId", $sectionId, PDO::PARAM_INT);
                $existsQuery->execute();
                
                $existsResults = $existsQuery->fetch();
                if ($existsResults['matches'] == 1) {
                    continue;
                }

                $insertQuery =
                    "INSERT INTO
                        enrollment
                        (`section_id`, `user_id`)
                    VALUES
                        (:sectionId, :userId);";
                
                $insertStmt = $dbh->prepare($insertQuery);
                $insertStmt->bindParam(":userId", $userId, PDO::PARAM_INT);
                $insertStmt->bindParam(":sectionId", $sectionId, PDO::PARAM_INT);
                $insertStmt->execute();
                
                $errorInfo = $insertStmt->errorInfo();
                if (isset($errorInfo[1]) || isset($errorInfo[2])) {
                    $error = True;
                    break;
                }
            }
        }

        if ($error) {
            $dbh->rollback();
        } else {
            $dbh->commit();
        }
        return !$error;
    }

    // function updateUser($dbh) {

    // }

    function deleteUser($bannerid, $dbh) {
        $deleteUserQuery = 
            "DELETE FROM user
            WHERE bannerId = :bannerid";
        
        $deleteUserStmt = $dbh->prepare($deleteUserQuery);
        $deleteUserStmt->bindParam(':bannerid', $bannerid);
        $result = $deleteUserStmt->execute();

        if($result) {
            header('location: ./users.php?delete=success');
        }

        else {
            header('location ./users.php?error=deletefailed');
        }
    }
?>