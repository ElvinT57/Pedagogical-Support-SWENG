<?php
    /**
     * @param PDO $dbh The database object.
     * @param int $assignmentSubmission_id The id of the assignmentSubmission to submit a grade for.
     * @param int $gradeBase The base grade before any late penalties
     * @param string $manualComment The comment entered by the user
     * @param int $predefinedCommentIds A list of ids of all the predefined comments to link.
     * @return array An array with all course data for the user or null if none were found.
     */
    function insertGrade($dbh, $assignmentSubmission_id, $gradeBase, $manualComment, $extraCredit, $commentDefinition_ids = [], $ignoreLatePenalty) {
        $dbh->beginTransaction();
        $error = False;

        $query = "INSERT INTO grade (assignmentSubmission_id, gradeBase, manualComment, extraCredit, ignoreLatePenalty)
                  VALUES (:assignmentSubmission_id, :gradeBase, :manualComment, :extraCredit, :ignoreLatePenalty);";
        $query = $dbh->prepare($query);
        $query->bindParam(':assignmentSubmission_id', $assignmentSubmission_id);
        $query->bindParam(':gradeBase', $gradeBase);
        $query->bindParam(':manualComment', $manualComment);
        $query->bindParam(':extraCredit', $extraCredit);
        $query->bindParam(':ignoreLatePenalty', $ignoreLatePenalty);
        $query->execute();

        $result = $query->errorInfo();

        if (isset($result[1]) || isset($result[2])) {
            $error = True;
            return !$error;
        }

        $grade_id = $dbh->lastInsertId();
        foreach ($commentDefinition_ids as $commentDefinition_id) { //TODO: FLAG
            $query =
                "INSERT INTO
                    gradeComment(grade_id, commentDefinition_id)
                VALUES
                    (:grade_id, :commentDefinition_id);";
            $query = $dbh->prepare($query);
            $query->bindParam(':grade_id', $grade_id);
            $query->bindParam(':commentDefinition_id', $commentDefinition_id);
            $query->execute();

            $errorInfo = $query->errorInfo();

            if (isset($errorInfo[1]) || isset($errorInfo[2])) {
                $error = True;
                break;
            }
        }

        if ($error) {
            $dbh->rollback();
        } else {
            $dbh->commit();
        }
        return !$error;
    }

    /**
     * @param PDO $dbh The database object.
     * @param int $assignmentId The id of the section to insert the students into.
     * @param array $gradeInfo The grades to insert.
     * [0] => Banner Id
     * [1] => Points Earned
     * [2] => Extra Credit Earned
     * [3] => Comment
     * @return bool True if the operation succeeded.
     */
    function insertMultipleGrades($dbh, $assignmentId, $gradeInfo)
    {
        $dbh->beginTransaction();
        $error = False;
        foreach($gradeInfo as $info) {
            $query = "SELECT id
                    FROM user
                    WHERE
                        bannerId = :bannerId;";
            $query = $dbh->prepare($query);
            $query->bindParam(":bannerId", $info[0]);
            $query->execute();
            $userErrorInfo = $query->errorInfo();
            
            if (isset($userErrorInfo[1]) || isset($userErrorInfo[2])) {
                $error = True;
                break;
            }

            $userResult = $query->fetchAll(PDO::FETCH_ASSOC);
            $userId = $userResult[0]['id'];

            $query = "INSERT INTO assignmentSubmission
                                            (`assignment_id`, `user_id`, `timeSubmitted`, `imagePath`)
                                        VALUES (:assignmentId, :userId, NOW(), NULL);
                                        SELECT LAST_INSERT_ID();";
                                        
            $query = $dbh->prepare($query);
            $query->bindParam(":assignmentId", $assignmentId);
            $query->bindParam(":userId", $userId);
            $query->execute();
            
            $assignmentSubmissionErrorInfo = $query->errorInfo();
            
            if (isset($assignmentSubmissionErrorInfo[1]) || isset($assignmentSubmissionErrorInfo[2])) {
                $error = True;
                break;
            }

            $assignmentSubmissionId = $dbh->lastInsertId();

            $query = "INSERT INTO grade 
                            (assignmentSubmission_id, gradeBase, manualComment, extraCredit, ignoreLatePenalty)
                            VALUES
                            (:assignmentSubmission_id, :gradeBase, :manualComment, :extraCredit, 0);";
            
            $query = $dbh->prepare($query);
            $query->bindParam(":assignmentSubmission_id", $assignmentSubmissionId);
            $query->bindParam(":gradeBase", $info[1]);
            $query->bindParam(":manualComment", $info[3]);
            $query->bindParam(":extraCredit", $info[2]);
            $query->execute();

            $gradeQueryErrorInfo = $query->errorInfo();    

            if (isset($gradeQueryErrorInfo[1]) || isset($gradeQueryErrorInfo[2])) {
                $error = True;
                break;
            }        
        }
 
        if ($error) {
            $dbh->rollback();
        } else {
            $dbh->commit();
        }
        return !$error;
    }

    /**
     * Inserts a new submission and grade for the given no-submission assignment.
     * 
     * @param PDO $dbh Database object
     * @param int $user_id The id of the student to be graded.
     * @param int $assignment_id The id of the given assignment.
     * @param int $gradeBase The base grade to be given.
     * @param string the manual comment for the submission.
     * @param int Extra credit points to be given.
     * @param array An array of comment definition ids. 
     * @param int integer boolean value for ignoring late penalty 
     * @param string string format of the date submitted. 
     */
    function insertNoSubmissionGrade($dbh, $user_id, $assignment_id, $gradeBase, $manualComment, $extraCredit, $commentDefinition_ids, $ignoreLatePenalty, $dateSubmitted){
        $dbh->beginTransaction();
        $error = False;

        // first insert a submission for the student.
        $sql = "INSERT INTO assignmentSubmission (assignment_id, user_id, timeSubmitted) VALUES (:assignment_id, :user_id, :dateSubmitted);";
        $sql = $dbh->prepare($sql);
        $sql->bindParam(':user_id', $user_id);
        $sql->bindParam(':assignment_id', $assignment_id);
        $sql->bindParam(':dateSubmitted', $dateSubmitted);
        $sql->execute();

        $result = $sql->errorInfo();

        if (isset($result[1]) || isset($result[2])) {
            $error = True;
        }

        //retrieve the latest submission
        $submission_id = $dbh->lastInsertId();

        // finally insert the grade given the submission id.
        $sql = "INSERT INTO grade (assignmentSubmission_id, gradeBase, manualComment, extraCredit, ignoreLatePenalty) VALUES (:submission_id, :gradeBase, :manualComment, :extraCredit, :ignoreLatePenalty);";
        $sql = $dbh->prepare($sql);
        $sql->bindParam(':submission_id', $submission_id);
        $sql->bindParam(':gradeBase', $gradeBase);
        $sql->bindParam(':manualComment', $manualComment);
        $sql->bindParam(':extraCredit', $extraCredit);
        $sql->bindParam(':ignoreLatePenalty', $ignoreLatePenalty);
        $sql->execute();
        $result = $sql->errorInfo();

        if (isset($result[1]) || isset($result[2])) {
            $error = True;
        }

        //insert comment defintions 
        $grade_id = $dbh->lastInsertId();
        foreach ($commentDefinition_ids as $commentDefinition_id) { //TODO: FLAG
            $query = "INSERT INTO
                        gradeComment (grade_id, commentDefinition_id)
                      VALUES
                        (:grade_id, :commentDefinition_id);";
            $query = $dbh->prepare($query);
            $query->bindParam(':grade_id', $grade_id);
            $query->bindParam(':commentDefinition_id', $commentDefinition_id);
            $query->execute();

            $errorInfo = $query->errorInfo();

            if (isset($errorInfo[1]) || isset($errorInfo[2])) {
                $error = True;
                break;
            }
        }

        if ($error) {
            $dbh->rollback();
        } else {
            $dbh->commit();
        }
        return !$error;
    }

    /**
     * Inserts a new grade for the given lab assignment
     * 
     * @param PDO $dbh The database object.
     * @param int $assignmentSubmission_id The id of the assignmentSubmission to submit a grade for.
     * @param int $user_id The primary id of the student that's being graded.
     * @param int $gradeBase The base grade before any late penalties
     */
    function insertLabGrade($dbh, $assignment_id, $user_id, $gradeBase){
        $dbh->beginTransaction();
        $error = False;

        // first insert a submission for the student.
        $sql = "INSERT INTO assignmentSubmission (assignment_id, user_id, timeSubmitted) VALUES (:assignment_id, :user_id, NOW());";
        $sql = $dbh->prepare($sql);
        $sql->bindParam(':user_id', $user_id);
        $sql->bindParam(':assignment_id', $assignment_id);
        $sql->execute();

        $result = $sql->errorInfo();

        if (isset($result[1]) || isset($result[2])) {
            $error = True;
        }

        // retrieve the submission's id.
        $submission_id = $dbh->lastInsertId();
        
        // finally insert the grade given the submission id.
        $sql = "INSERT INTO grade (assignmentSubmission_id, gradeBase, manualComment, extraCredit, ignoreLatePenalty) VALUES (:submission_id, :gradeBase, '', '0', '1');";
        $sql = $dbh->prepare($sql);
        $sql->bindParam(':submission_id', $submission_id);
        $sql->bindParam(':gradeBase', $gradeBase);
        $sql->execute();
        $result = $sql->errorInfo();

        if (isset($result[1]) || isset($result[2])) {
            $error = True;
        }

        if ($error) {
            $dbh->rollback();
        } else {
            $dbh->commit();
        }
        return !$error;
    }

    /**
     * Updates the existing grade for the given lab assignment
     * 
     * @param PDO $dbh The database object.
     * @param int $assignmentSubmission_id The id of the assignmentSubmission to submit a grade for.
     * @param int $user_id The primary id of the student that's being graded.
     * @param int $gradeBase The base grade before any late penalties
     */
    function updateLabGrade($dbh, $assignment_id, $user_id, $gradeBase){
        $dbh->beginTransaction();
        $error = False;

        // retrieve the submission's id.
        $sql = "SELECT 
                    id 
                FROM assignmentSubmission
                WHERE
                    assignment_id = :assignment_id
                AND
                    user_id = :user_id;";

        $sql = $dbh->prepare($sql);
        $sql->bindParam(':user_id', $user_id);
        $sql->bindParam(':assignment_id', $assignment_id);
        $sql->execute();
        
        $result = $sql->errorInfo();

        if (isset($result[1]) || isset($result[2])) {
            $error = True;
        }

        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
        $submission_id = $result[0]['id'];

        // Next Update the existing grade
        $sql = "UPDATE grade SET gradeBase = :gradeBase 
                WHERE ( assignmentSubmission_id = :submission_id );";

        $sql = $dbh->prepare($sql);
        $sql->bindParam(':submission_id', $submission_id);
        $sql->bindParam(':gradeBase', $gradeBase);
        $sql->execute();

        $result = $sql->errorInfo();

        if (isset($result[1]) || isset($result[2])) {
            $error = True;
        }

        if ($error) {
            $dbh->rollback();
        } else {
            $dbh->commit();
        }
        return !$error;
    }

    /**
     * Returns all graded students and their grade for the
     * given lab assignment.
     * 
     * @param PDO $dbh The database object.
     * @param int $assignmentSubmission_id The id of the assignmentSubmission to submit a grade for.
     * @param int $section_id The id of the section.
     */
    function getLabGrades($dbh, $assignment_id, $section_id){
        $sql = "SELECT
                    assignmentSubmission.user_id as 'user_id',
                    grade.gradeBase as 'gradeBase'
                FROM grade
                    JOIN assignmentSubmission on assignmentSubmission.id = grade.assignmentSubmission_id
                    JOIN sectionAssignment on sectionAssignment.assignment_id = assignmentSubmission.assignment_id
                WHERE
                    sectionAssignment.assignment_id = :assignment_id
                AND
                    sectionAssignment.section_id = :section_id;";

        $sql = $dbh->prepare($sql);
        $sql->bindParam(':assignment_id', $assignment_id);
        $sql->bindParam(':section_id', $section_id);
        $sql->execute();

        return $sql->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Retrieves all the graded submissions of the given assignment from
     * the user.
     * @param dbh $dbh The database object.
     * @param user_id the primary key of the user
     * @param assignment_id the primary key of the assignment
     * @param section_id the primary key of the assignment
     * @return array A list all the graded submissions.
     */
    function getAllGradesForAssignment($dbh, $user_id, $assignment_id, $section_id){
        $sql =
            "SELECT 
                grade.id,
                grade.gradeBase,
                grade.manualComment,
                grade.extraCredit,
                grade.ignoreLatePenalty,
                sectionAssignment.latePenalty,
                assignmentSubmission.timeSubmitted,
                sectionAssignment.dateDue
            FROM grade
                JOIN assignmentSubmission on assignmentSubmission.id = grade.assignmentSubmission_id
                JOIN user on user.id = assignmentSubmission.user_id
                JOIN assignment on assignment.id = assignmentSubmission.assignment_id
                JOIN sectionAssignment on sectionAssignment.assignment_id = assignment.id
            WHERE
                assignmentSubmission.user_id = :user_id
            AND
                assignmentSubmission.assignment_id = :assignment_id
            AND
                sectionAssignment.section_id = :section_id
            ORDER BY
            assignmentSubmission.timeSubmitted DESC;";

        $sql = $dbh->prepare($sql);
        $sql->bindParam(':user_id', $user_id);
        $sql->bindParam(':assignment_id', $assignment_id);
        $sql->bindParam(':section_id', $section_id);
        $sql->execute();
        
        return $sql->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Calculates the given student's grade considering the extra credit and the late penalty (If it applies).
     * @param baseGrade $baseGrade submission grade.
     * @param extraCredit $extraCredit submission grade extra credit.
     * @param latePenalty $latePenalty amount of late penalty to subtract.
     * @param dueDate $dueDate due date of the assignment
     * @param dateSubmitted $dateSubmitted the date the assignment was submitted
     * @param latePenaltyInterval $latePenaltyInterval the day interval for late penalty
     * @param ignorePenalty $ignorePenalty int boolean for ignoring late penalty.
     **/
    function calculateGrade($baseGrade, $extraCredit, $latePenalty, $dueDate, $dateSubmitted, $latePenaltyInterval, $ignorePenalty){
        if($latePenaltyInterval == 0 or $ignorePenalty == 1){
            return $baseGrade + $extraCredit;
        }else{
            date_default_timezone_set("America/New_York");
            // calculate the date differences
            $dateSubmitted =  new DateTime($dateSubmitted);
            $dueDate = new DateTime($dueDate);
            $dateDiff = $dueDate->diff($dateSubmitted)->d;

            // check if the submission date comes before the due date
            if($dueDate > $dateSubmitted){
                $dateDiff = 0;
            }
            // calculate grade
            $grade = ($baseGrade + $extraCredit) - ($latePenalty * floor($dateDiff / max($latePenaltyInterval, 1)) );

            return max($grade, 0);
        }
    }

    /**
     * This query will get all of the grades for all of the users for all of the 
     * assignmnents in a given section
     * @param dbh The database object
     * @param sectionId The section getting the information
     */
    function getGradesForAllStudents($dbh, $sectionId) {
        $query = 
            "SELECT 
                gradeBase, 
                title, 
                userName,
                dateDue as 'due_date'
            FROM grade
                JOIN assignmentSubmission ON assignmentSubmission_id = assignmentSubmission.id
                JOIN assignment ON assignmentSubmission.assignment_id = assignment.id
                JOIN sectionAssignment ON sectionAssignment.assignment_id = assignment.id
                JOIN user ON user_id = user.id
            WHERE section_id = :sectionId;";
        
        $query = $dbh->prepare($query);
        $query->bindParam(':sectionId', $sectionId);

        $result = $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Returns all users grades for all assignments in a section.
     * @param dbh The database object.
     * @param section_id The id of the section to query over.
     */
    function getGradesForUsersForSection($dbh, $section_id) {
        $query =
            "SELECT
                user.userName,
                user.firstName,
                user.lastName,
                user.id AS user_id,
                assignment.id AS assignment_id,
                assignment.title,
                sectionAssignment.dateDue,
                sectionAssignment.latePenalty,
                sectionAssignment.latePenaltyInterval,
                assignment.maxGrade,
                bGrade.gradeBase,
                bGrade.extraCredit,
                bGrade.ignoreLatePenalty,
                bAssign.timeSubmitted
            FROM
                enrollment
                JOIN user ON user.id = enrollment.user_id
                
                JOIN (
                    SELECT * FROM assignmentSubmission as1
                    INNER JOIN (
                        SELECT MAX(id) AS bAssignId FROM assignmentSubmission GROUP BY user_id, assignment_id
                    ) as2 ON (as1.id = as2.bAssignId)
                ) bAssign ON enrollment.user_id = bAssign.user_id
                
                LEFT JOIN (
                    SELECT * FROM grade g1
                    INNER JOIN (
                        SELECT MAX(id) AS bGradeId FROM grade GROUP BY assignmentSubmission_id
                    ) g2 ON (g1.id = g2.bGradeId)
                ) bGrade ON bAssign.bAssignId = bGrade.assignmentSubmission_id
                
                JOIN assignment ON assignment.id = bAssign.assignment_id
                JOIN sectionAssignment ON sectionAssignment.assignment_id = assignment.id
            WHERE
                enrollment.section_id = :section_id AND sectionAssignment.section_id = :section_id;";
        $query = $dbh->prepare($query);
        $query->bindParam(":section_id", $section_id);
        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
?>