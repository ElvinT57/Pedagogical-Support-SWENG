<?php


    /**
     * @param PDO database PDO object.
     * @param int $assignment_id the id of the desired assignment.
     * @return array All the information about the desired assignment.
     */
    function getAssignment($dbh, $assignmentId) {
        $query =
            "SELECT 
                assignment.id as 'assignment_id',
                assignment.course_id,
                assignment.title,
                assignment.description,
                assignment.maxGrade,
                assignment.number,
                assignment.assignmentType,
                assignment.permitsUpload,
                sectionAssignment.dateDue as 'dueDate',
                sectionAssignment.latePenalty,
                sectionAssignment.latePenaltyInterval
             FROM
                assignment
                JOIN sectionAssignment on sectionAssignment.assignment_id = assignment.id
             WHERE
                assignment.id = :assignmentId;";

        $query = $dbh->prepare($query);
        $query->bindParam(':assignmentId', $assignmentId);
        $query->execute();

        return $query->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * @param PDO $dbh database PDO object.
     * @param int the id of the given section
     * @return array A list of the section's assignments and their information. 
     */
    function getAssignmentsFromSection($dbh, $section_id) {
        $getAssignmentsQuery = "SELECT
                                	assignment.id as 'assignment_id',
                                    assignment.title,
                                    assignment.description,
                                    assignment.maxGrade,
                                    assignment.number,
                                    assignment.assignmentType,
                                    assignment.permitsUpload,
                                    sectionAssignment.dateDue as 'dueDate',
                                    sectionAssignment.latePenalty,
                                    sectionAssignment.latePenaltyInterval
                                FROM
                                	sectionAssignment
                                JOIN assignment on assignment.id = sectionAssignment.assignment_id
                                where sectionAssignment.section_id = :section_id;
                                ORDER BY sectionAssignment.dateDue ASC";
        
        $getAssignmentsStmt = $dbh->prepare($getAssignmentsQuery);
        $getAssignmentsStmt->bindParam(':section_id', $section_id);
        $getAssignmentsStmt->execute();

        return $getAssignmentsStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param PDO $dbh database PDO object.
     * @param int $assignment_id the id of the given assignment
     * @param int $section_id the id of the given section.
     * @return array The assignment information from the desired section.
     */
    function getAssignmentFromSection($dbh, $assignment_id, $section_id) {
        // TODO: Refactor columns with the correct conventions
        $sql = "SELECT
                    assignment.id as 'id',
                    assignment.title,
                    assignment.assignmentType,
                    assignment.maxGrade,
                    assignment.description,
                    sectionAssignment.dateDue as 'dueDate',
                    sectionAssignment.latePenalty,
                    sectionAssignment.latePenaltyInterval,
                    assignment.permitsUpload
                FROM assignment
                    JOIN sectionAssignment on sectionAssignment.assignment_id = assignment.id
                WHERE
                    assignment.id = :assignment_id
                AND
                    sectionAssignment.section_id = :section_id;";

        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':assignment_id', $assignment_id);
        $stmt->bindParam(':section_id', $section_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Returns the list of students who were assigned the give
     * assignment; that are also from the given section.
     * @param PDO $dbh database object.
     * @param int $assignment_id the id of the assignment.
     * @param int $section_id the id of the section.
     */
    function getStudentsFromAssignment($dbh, $section_id, $assignment_id) {
        $sql = "SELECT 
                    user.id as 'user_id',
                    user.firstName as 'firstName',
                    user.lastName as 'lastName',
                    user.bannerId as 'banner_id'
                FROM user
                    JOIN enrollment on enrollment.user_id = user.id
                    JOIN sectionAssignment on sectionAssignment.section_id = enrollment.section_id
                WHERE
                    sectionAssignment.assignment_id = :assignment_id
                AND
                    sectionAssignment.section_id = :section_id
                AND
	            user.userType <> 'Teacher';";

        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':assignment_id', $assignment_id);
        $stmt->bindParam(':section_id', $section_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update an assignment
     * TODO: Params
     */
    function updateAssignment($dbh, $id, $title, $description, $maxGrade, $number, $assignmentType, $permitsUpload, $dateAvailable, $dateDue, $latePenalty, $latePenaltyInterval, $section_ids) {
        // This is the only way I know how to do "private" functions in PHP, since nested functions are still global (outside of making a class).
        $updateAssignment = function($dbh, $id, $title, $description, $maxGrade, $number, $assignmentType, $permitsUpload) {
            $query = 
                "UPDATE assignment
                SET 
                    title = :title, 
                    description = :description, 
                    maxGrade = :maxGrade, 
                    number = :number,
                    assignmentType = :assignmentType,
                    permitsUpload = :permitsUpload
                WHERE assignment.id = :assignmentId;";

            $stmt = $dbh->prepare($query);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':maxGrade', $maxGrade);
            $stmt->bindParam(':number', $number);
            $stmt->bindParam(':assignmentType', $assignmentType);
            $stmt->bindParam(':permitsUpload', $permitsUpload);
            $stmt->bindParam(':assignmentId', $id);
            return $stmt->execute();
        };
        $insertSectionAssignment = function($dbh, $section_id, $assignment_id, $dateAvailable, $dateDue, $latePenalty, $latePenaltyInterval) {
            $query =
                "INSERT INTO
                    sectionAssignment
                    (`section_id`, `assignment_id`, `dateAvailable`, `dateDue`, `latePenalty`, `latePenaltyInterval`)
                VALUES
                    (:section_id, :assignment_id, :dateAvailable, :dateDue, :latePenalty, :latePenaltyInterval);";

            $stmt = $dbh->prepare($query);
            $stmt->bindParam(':section_id', $section_id);
            $stmt->bindParam(':assignment_id', $assignment_id);
            $stmt->bindParam(':dateAvailable', $dateAvailable);
            $stmt->bindParam(':dateDue', $dateDue);
            $stmt->bindParam(':latePenalty', $latePenalty);
            $stmt->bindparam(':latePenaltyInterval', $latePenaltyInterval);
            return $stmt->execute();
        };
        $clearSectionAssignments = function($dbh, $assignment_id) {
            $query =
                "DELETE FROM
                    sectionAssignment
                WHERE
                    assignment_id = :assignment_id;";
            $stmt = $dbh->prepare($query);
            $stmt->bindParam(':assignment_id', $assignment_id);
            return $stmt->execute();
        };

        $result = true;
        try {
            $dbh->beginTransaction();

            if (!$clearSectionAssignments($dbh, $id)) {
                throw new Exception("Error executing query.");
            }
            if (!$updateAssignment($dbh, $id, $title, $description, $maxGrade, $number, $assignmentType, $permitsUpload)) {
                throw new Exception("Error executing query.");
            }
            
            foreach ($section_ids as $section_id) {
                if (!$insertSectionAssignment($dbh, $section_id, $id, $dateAvailable, $dateDue, $latePenalty, $latePenaltyInterval)) {
                    throw new Exception("Error executing query.");
                }
            }

            $dbh->commit();
        } catch (PDOException $e) {
            $result = false;
            $dbh->rollback();
        } finally {
            return $result;
        }
    }
    
    /**
     * Add an assignment and link all sectionAssignments
     * TODO: Params
     */
    function addAssignment($dbh, $course_id, $title, $description, $maxGrade, $number, $assignmentType, $permitsUpload, $dateAvailable, $dateDue, $latePenalty, $latePenaltyInterval, $section_ids) {
        if (gettype($section_ids) != "array") {
            throw new InvalidArgumentException("section_ids must be an array.");
        }
        
        // This is the only way I know how to do "private" functions in PHP, since nested functions are still global (outside of making a class).
        $insertAssignment = function ($dbh, $course_id, $title, $description, $maxGrade, $number, $assignmentType, $permitsUpload) {
            $query = 
                "INSERT INTO
                    assignment
                    (`course_id`, `title`, `description`, `maxGrade`, `number`, `assignmentType`, `permitsUpload`)
                VALUES
                    (:course_id, :title, :description, :maxGrade, :number, :assignmentType, :permitsUpload)";

            $stmt = $dbh->prepare($query);
            $stmt->bindParam(':course_id', $course_id);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':maxGrade', $maxGrade);
            $stmt->bindParam(':number', $number);
            $stmt->bindParam(':assignmentType', $assignmentType);
            $stmt->bindParam(':permitsUpload', $permitsUpload);
            return $stmt->execute();
        };
        $insertSectionAssignment = function($dbh, $section_id, $assignment_id, $dateAvailable, $dateDue, $latePenalty, $latePenaltyInterval) {
            $query =
                "INSERT INTO
                    sectionAssignment
                    (`section_id`, `assignment_id`, `dateAvailable`, `dateDue`, `latePenalty`, `latePenaltyInterval`)
                VALUES
                    (:section_id, :assignment_id, :dateAvailable, :dateDue, :latePenalty, :latePenaltyInterval);";

            $stmt = $dbh->prepare($query);
            $stmt->bindParam(':section_id', $section_id);
            $stmt->bindParam(':assignment_id', $assignment_id);
            $stmt->bindParam(':dateAvailable', $dateAvailable);
            $stmt->bindParam(':dateDue', $dateDue);
            $stmt->bindParam(':latePenalty', $latePenalty);
            $stmt->bindparam(':latePenaltyInterval', $latePenaltyInterval);
            return $stmt->execute();
        };

        $result = true;
        try {
            $dbh->beginTransaction();
            
            if (!$insertAssignment($dbh, $course_id, $title, $description, $maxGrade, $number, $assignmentType, $permitsUpload)) {
                throw new Exception("Error executing query.");
            }
            $assignment_id = $dbh->lastInsertId();

            foreach ($section_ids as $section_id) {
                if (!$insertSectionAssignment($dbh, $section_id, $assignment_id, $dateAvailable, $dateDue, $latePenalty, $latePenaltyInterval)) {
                    throw new Exception("Error executing query.");
                }
            }

            $dbh->commit();
        } catch (PDOException $e) {
            $result = false;
            $dbh->rollBack();
        } finally {
            return $result;
        }
    }

    /**
     * get list of assignments for a course section.
     * NOTE: this is a function to test retrieving data for the viewSection page.
     * This method can be removed if deemed unneccessary.
     * @param PDO $dbh The database object.
     * @param int $section_id the section id for section number selected.
     * @return array $result a list of all assignments for the course section.
     */
    function getAssignmentsForCourseSection($dbh, $section_id) {
        $query = 
            "SELECT
                assignment.id as 'id',
                assignment.title,
                assignment.description,
                assignment.maxGrade,
                assignment.number,
                assignment.assignmentType as 'type',
                assignment.permitsUpload,
                sectionAssignment.dateDue as 'due_date'
            FROM
                sectionAssignment
            JOIN assignment on assignment.id = sectionAssignment.assignment_id
            WHERE
                sectionAssignment.section_id = :section_id;";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(':section_id', $section_id);
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    /**
     * Return all of the necessary details for the assignment given to a student user
     * @param PDO $dbh the database object
     * @param int $user_id the id of the user
     * @return array $result the details of the assignment
     */
    function getAssignmentDetails($dbh, $user_id) {
        $query = 
            "SELECT DISTINCT
                sectionAssignment.id,
                sectionAssignment.assignment_id AS 'assignment_id',
                dateDue,
                latePenalty,
                latePenaltyInterval
            FROM sectionAssignment
                JOIN assignment ON sectionAssignment.assignment_id = assignment.id
                JOIN assignmentSubmission ON assignment.id = assignmentSubmission.assignment_id
            WHERE assignmentSubmission.user_id = :user_id;";
        
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }
?>