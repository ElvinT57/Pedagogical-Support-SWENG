<?php
/**
 * @param PDO $dbh The database object.
 * @param int $assignmentId The id of the assignment to get all submissions for.
 * @param array $userArray An array containing userIds.
 * @return array An array with all submissions for the assignment or null if none were found.
 */
function getSubmissionsFromUserList($dbh, $assignmentId, $userArray) {
    //checks if input is an array, is less than 1, and if it is unset
    //if all conditions are true, return error message
    if( (!is_array($userArray)) && (empty($userArray)) && (!isset($userArray)) ) {
        return null;
    }
    else {
        $inQuery = implode(',', array_fill(0, count($userArray), '?'));
        $query = 
            "SELECT
                assignmentSubmission.id AS 'id',
                user_id AS 'user_id',
                assignmentSubmission.timeSubmitted,
                grade.id AS 'grade_id',
                grade.gradeBase AS 'gradeBase',
                grade.extraCredit AS 'extraCredit',
                grade.ignoreLatePenalty,
                assignment.maxGrade AS 'gradeMax'
            FROM assignmentSubmission
                LEFT JOIN grade ON grade.assignmentSubmission_id = assignmentSubmission.id
                JOIN assignment ON assignment.id = assignmentSubmission.assignment_id
            WHERE
                assignmentSubmission.assignment_id=? AND
                assignmentSubmission.user_id IN ({$inQuery})
            ORDER BY assignmentSubmission.id ASC;";
        
        $query = $dbh->prepare($query);
        $query->bindValue(1, $assignmentId, PDO::PARAM_INT);
        foreach ($userArray as $k => $id) {
            $query->bindValue($k+2, $id, PDO::PARAM_INT);
        }
        $query->execute();

        $result = $query->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }
}

/**
 * This method will get the details on an assignment submission
 * @param PDO $dbh the database object
 * @param int $submission_id the submission id
 * @return array the details of the submitted assignment
 */
function getAssignmentSubmission($dbh, $submission_id) {
    $sql =  "SELECT *
            FROM assignmentSubmission
            WHERE id = :submission_id;";

    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(':submission_id', $submission_id);
    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
* @param PDO $dbh The database object.
* @param int $assignment_id the id of the assignment
* @param int $user_id the id of the student we want to retrieve the submission from
* @return list of submissions for a given assignment [submission_id, timeSubmitted, imagePath]
*/
function getAssignmentSubmissions($dbh, $user_id, $assignment_id) {
    $getSubmissionQuery=
        "SELECT
            assignmentSubmission.id,
            assignmentSubmission.timeSubmitted,
            assignmentSubmission.imagePath
        FROM assignmentSubmission
        WHERE
            assignmentSubmission.user_id = :user_id AND
            assignmentSubmission.assignment_id = :assignment_id
        ORDER BY assignmentSubmission.timeSubmitted DESC LIMIT 1;";

    $getSubmissionStmt = $dbh->prepare($getSubmissionQuery);
    $getSubmissionStmt->bindParam(':user_id', $user_id);
    $getSubmissionStmt->bindParam(':assignment_id', $assignment_id);
    $getSubmissionStmt->execute();

    return $getSubmissionStmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * This method will get the submissions from a user in a section
 * @param PDO $dbh the database object
 * @param int $user_id the user id
 * @param int $section_id the section id 
 * @return array the list of submission from a user in specified section
 */
function getSubmissionsInSection($dbh, $user_id, $section_id){
    $sql =
        "SELECT
             assignment.id AS 'assignment_id',
             assignment.assignmentType AS 'type',
             sectionAssignment.id AS 'sectionAssignment_id',
             latestSubmission.id AS 'assignmentSubmission_id',
             latestGrade.id AS 'grade_id',
             sectionAssignment.dateAvailable,
             sectionAssignment.dateDue as 'dueDate',
             sectionAssignment.latePenalty,
             sectionAssignment.latePenaltyInterval,
             assignment.title AS 'title',
             assignment.description AS 'description',
             assignment.permitsUpload AS 'permitsUpload',
             latestSubmission.timeSubmitted AS 'timeSubmitted',
             latestSubmission.imagePath AS 'imagePath',
             latestGrade.gradeBase AS 'baseGrade',
             latestGrade.extraCredit AS 'extraCredit',
             assignment.maxGrade AS 'maxGrade',
             latestGrade.ignoreLatePenalty AS 'ignoreLatePenalty',
             latestGrade.manualComment AS 'manualComment'
         FROM
             sectionAssignment
             JOIN assignment ON assignment.id = sectionAssignment.assignment_id
             LEFT JOIN (
                 SELECT
                     a1.*
                 FROM
                     assignmentSubmission a1
                     LEFT JOIN assignmentSubmission a2 ON (
                             a1.user_id = a2.user_id
                         AND a1.assignment_id = a2.assignment_id
                         AND a1.id < a2.id
                     )
                 WHERE
                         a2.id IS NULL
                     AND a1.user_id = :user_id
             ) AS latestSubmission ON latestSubmission.assignment_id = sectionAssignment.assignment_id
             LEFT JOIN (
                 SELECT
                     g1.*
                 FROM
                     grade g1
                     LEFT JOIN grade g2 ON (
                             g1.assignmentSubmission_id = g2.assignmentSubmission_id
                         AND g1.id < g2.id
                     )
                 WHERE
                     g2.id IS NULL
             ) AS latestGrade ON latestGrade.assignmentSubmission_id = latestSubmission.id
         WHERE
             sectionAssignment.section_id = :section_id
         ORDER BY latestSubmission.timeSubmitted DESC;";

    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':section_id', $section_id);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
/**
 * Insert a assignment submission for a user including a file that the user uploaded.
 * Pass the old path and the final path to ensure data and filesystem integrity.
* @param PDO $dbh The database object.
* @param int $assignmentId the id of the assignment
* @param int $userId the id of the student we want to insert the submission for
* @param string $oldPath The path to the temporary file upload.
* @param string $newPath The path where the file should be moved.
* @return bool True if the transaction succeeded, otherwise false.
*/
function insertAssignmentSubmissionWithUpload($dbh, $assignmentId, $userId, $oldPath, $newPath) {
    $dbh->beginTransaction();

    $query =
        "INSERT INTO
            assignmentSubmission
            (`assignment_id`, `user_id`, `timeSubmitted`, `imagePath`)
        VALUES
            (:assignmentId, :userId, NOW(), :newPath);";
    
    $query = $dbh->prepare($query);
    $query->bindParam(":assignmentId", $assignmentId);
    $query->bindParam(":userId", $userId);
    $query->bindParam(":newPath", $newPath);
    $query->execute();
    $result = $query->errorInfo();
    
    if (isset($result[1]) || isset($result[2])) {
        $dbh->rollback();
        return false;
    }

    //TODO: Check if the $newPath already points to a file, if not rollback

    try {
        move_uploaded_file($oldPath, $newPath);
    } catch (Exception $e) {
        $dbh->rollback();
        return false;
    }

    $dbh->commit();
    return true;
}

/**
 * Insert a assignment submission without a file upload.
* @param PDO $dbh The database object.
* @param int $assignmentId the id of the assignment
* @param int $userId the id of the student we want to insert the submission for
* @return bool True if the transaction succeeded, otherwise false.
*/
function insertAssignmentSubmission($dbh, $assignmentId, $userId) {
    $query =
        "INSERT INTO
            assignmentSubmission
            (`assignment_id`, `user_id`, `timeSubmitted`, `imagePath`)
        VALUES
            (:assignmentId, :userId, NOW(), NULL);";
    
    $query = $dbh->prepare($query);
    $query->bindParam(":assignmentId", $assignmentId);
    $query->bindParam(":userId", $userId);
    $query->execute();
    $result = $query->errorInfo();

    return (!isset($result[1]) && !isset($result[2]));
}

/**
 * Retrieves the user id's of all the students who
 * have not submitted the assignment. 
 * 
 * @param PDO $dbh The database object.
 * @param int $section_id The ID of the given section.
 * @param int $assignment_id The ID of the given assignment
 * @return Array An array of user id's of students who have not submitted yet.
 */
function getUnsubmittedStudent($dbh, $section_id, $assignment_id) {
    $sql = "SELECT
                enrollment.user_id
            FROM enrollment
                JOIN user on user.id = enrollment.user_id
            WHERE
                enrollment.user_id not in (
                    SELECT distinct
                        enrollment.user_id
                    FROM assignmentSubmission
                        JOIN enrollment on enrollment.user_id = assignmentSubmission.user_id
                    WHERE
                        assignmentSubmission.assignment_id = :assignment_id
                        AND
                        enrollment.section_id = :section_id
                )
            AND
                enrollment.section_id = :section_id
            AND
                user.userType <> 'Teacher';";

    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(':section_id', $section_id);
    $stmt->bindParam(':assignment_id', $assignment_id);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Retrieves the user id's of all the students who
 * have not been graded for the given assignment yet. 
 * 
 * @param PDO $dbh The database object.
 * @param int $section_id The ID of the given section.
 * @param int $assignment_id The ID of the given assignment
 * @return Array An array of user id's of students who have not submitted yet.
 */
function getUngradedStudents($dbh, $section_id, $assignment_id) {
    $sql = "SELECT
                enrollment.user_id
            FROM enrollment
                JOIN assignmentSubmission on assignmentSubmission.user_id = enrollment.user_id
            WHERE
                enrollment.user_id not in (
                    SELECT distinct
                        enrollment.user_id
                    FROM assignmentSubmission
                        JOIN enrollment on enrollment.user_id = assignmentSubmission.user_id
                        JOIN grade on grade.assignmentSubmission_id = assignmentSubmission.id
                    WHERE
                        assignmentSubmission.assignment_id = :assignment_id
                        AND
                        enrollment.section_id = :section_id
                        AND
                        grade.assignmentSubmission_id = assignmentSubmission.id
                )
            AND
                enrollment.section_id = :section_id
            AND
                assignmentSubmission.assignment_id = :assignment_id;";

    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(':section_id', $section_id);
    $stmt->bindParam(':assignment_id', $assignment_id);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * This method will list all of the submissions from a specified user
 * @param PDO $dbh the database object
 * @param int $user_id the user id
 * @return array the list of all user submissions
 */
function getListOfUserSubmissions($dbh, $user_id) {
    $query = 
        "SELECT
            assignment.id AS 'assignmentID',
            assignmentSubmission.id AS 'assignmentSubmissionID'
        FROM assignmentSubmission
            JOIN assignment ON assignmentSubmission.assignment_id = assignment.id
        WHERE assignmentSubmission.user_id = :user_id;";
    
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $result;
}

/**
 * This method will return the most up-to-date submissions from a user
 * @param PDO $dbh the database object
 * @param int $userId the user Id
 * @return array the list of the most recent submission from a user
 */
function getMostRecentSubmissions($dbh, $userId) {
    $query = " SELECT 
                    assignment_id, 
                    timeSubmitted, 
                    gradeBase AS 'gradeBase',
                    IFNULL(extraCredit, 0) AS 'extraCredit',
                    IFNULL(ignoreLatePenalty, 1) AS 'ignoreLatePenalty',
                    course.id AS 'course_id'
               FROM assignmentSubmission as1 
                    INNER JOIN (
                        SELECT MAX(id) AS id 
                        FROM assignmentSubmission 
                        GROUP BY user_id, assignment_id
                        ) as2 ON (as1.id = as2.id)
                    LEFT JOIN grade ON as1.id = grade.assignmentSubmission_id
                    JOIN assignment ON as1.assignment_id = assignment.id
                    JOIN course ON assignment.course_id = course.id
                    LEFT JOIN section ON course.id = section.course_id
                WHERE user_id = :userId;";

    $stmt = $dbh->prepare($query);
    $stmt->bindParam(':userId', $userId);
    $stmt->execute();

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $result;
}

?>