<?php
    /**
     * This method will return the assignments in the specified section
     * @param PDO $dbh the database object
     * @param int $assignmentId the assignment id
     * @param int $sectionId the section id
     * @return array the assignments for the section.
     */
    function getSectionAssignment($dbh, $assignmentId, $sectionId) {
        $query =
            "SELECT *
            FROM
                assignment
            JOIN sectionAssignment ON sectionAssignment.assignment_id = assignment.id
            WHERE
            sectionAssignment.section_id=:sectionId;";

        $query = $dbh->prepare($query);
        $query->bindParam(':sectionId', $sectionId);        
        $query->execute();

        return $query->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * @param PDO $dbh The database object.
     * @param int $userId The id of the user to get the courses for.
     * @param int $courseId The id of the course to get sections for.
     * @return array An array with all course data for the user or null if none were found.
     */
    function getSectionsForUsersCourse($dbh, $courseId, $userId) {
        $query =
            "SELECT
                section.id AS 'id',
                section.number AS 'number',
                section.meetingDays AS 'days',
                section.beginTime AS 'time',
                section.crn AS 'crn',
                course.title AS 'course_title'
            FROM enrollment
                JOIN section ON section.id = enrollment.section_id
                JOIN course ON course.id = section.course_id
            WHERE
                enrollment.user_id=:userId AND section.course_id=:courseId;";

        $query = $dbh->prepare($query);
        $query->bindParam(":userId", $userId);
        $query->bindParam(":courseId", $courseId);
        $query->execute();
        $result = $query->fetchAll(PDO::FETCH_ASSOC);
        
        return $result;
    }

    /**
     * @param PDO $dbh The database object.
     * @param int $sectionID The id of the given section.
     * @return array An array with all information needed for each student in the given section.
     */
    function getStudentsFromSection($dbh, $sectionID){
        $sql = 
            "SELECT
                e.id,
                u.profilePath,
                u.firstName,
                u.lastName,
                u.email,
                e.dropped
            FROM user AS u
                JOIN enrollment e ON e.user_id = u.id
            WHERE e.section_id = :sectionID
            AND u.userType = 'Student';";

        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':sectionID', $sectionID);
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    /**
     * @param PDO $dbh The database object.
     * @param int $userId The id of the user to get the courses for.
     * @return array An array with all section data.
     */
    function getSection($dbh, $sectionID) {
        $sql = 
            "SELECT *
            FROM section s
            WHERE s.id = :sectionID;";

        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':sectionID', $sectionID);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * @param PDO $dbh The database object.
     * @param string $semester The selected semester the course section is being taught.
     * @param int $crn The course reference number for the course section.
     * @param int $sectionNum The section Number for the course section.
     * @param string $daysTaught The days when the course section is taught
     * @param string $beginTime The starting time of the course section.
     * @param string $labMeetingDays meeting day(s) of the lab period in course section.
     * @param string $labBeginTime The starting time of the lab period in course section.
     * @param int $sessionNum The session Number for the course section.
     * @param string $year The year a course section is taught.
     * @param int $courseId The course id for the section.
     * @return void $result Prints whether section was updated successfully or not.
     */
    function updateSection($dbh, $semester, $crn, $sectionNum, $daysTaught, 
                           $beginTime, $labMeetingDays, $labBeginTime, $sessionNum, $year, $courseId, $sectionId) {
        $query = 
            "UPDATE section
            SET
                semester = :semester, 
                crn = :crn,
                number = :sectionNum,
                meetingDays = :days,
                beginTime = :beginTime,
                labMeetingDays = :labMeetingDays,
                labBeginTime = :labBeginTime,
                section.session = :session,
                year = :year
            WHERE
                course_id = :courseId
            AND
                section.id = :sectionId;";

        $stmt = $dbh->prepare($query);
        $stmt->bindParam(':semester', $semester);
        $stmt->bindParam(':crn', $crn);
        $stmt->bindParam(':sectionNum', $sectionNum);
        $stmt->bindParam(':days', $daysTaught);
        $stmt->bindParam(':beginTime', $beginTime);
        $stmt->bindParam(':labMeetingDays', $labMeetingDays);
        $stmt->bindParam(':labBeginTime', $labBeginTime);
        $stmt->bindParam(':session', $sessionNum);
        $stmt->bindParam(':year', $year);
        $stmt->bindParam(':courseId', $courseId);
        $stmt->bindParam(':sectionId', $sectionId);
        $stmt->execute();

        $result = $stmt->errorInfo();
        return (!isset($result[1]) && !isset($result[2]));
    }

    /**
     * @param PDO $dbh The database object.
     * @param string $semester The selected semester the course section is being taught.
     * @param int $crn The course reference number for the course section.
     * @param int $sectionNum The section Number for the course section.
     * @param string $daysTaught The days when the course section is taught
     * @param string $beginTime The starting time of the course section.
     * @param int $sessionNum The session Number for the course section.
     * @param string $year The year a course section is taught.
     * @param int $courseId The course id for the section.
     * @return void $result Prints whether section was added successfully or not.
     */

    function addSection($dbh, $user_id, $course_id, $semester, $crn, $sectionNum, $daysTaught, $beginTime,
                        $labMeetingDays, $labBeginTime, $session, $year) {
        
        $dbh->beginTransaction();
        $error = false;
        // insert a new section
        $query = "INSERT INTO section (course_id, semester, crn, number, meetingDays,
                        beginTime, labMeetingDays, labBeginTime, section.session, year)
                        VALUES (:course_id, :semester, :crn, :sectionNum, :days,
                        :beginTime, :labDays, :labTime, :session, :year);";

        $stmt = $dbh->prepare($query);
        $stmt->bindParam(':semester', $semester);
        $stmt->bindParam(':crn', $crn);
        $stmt->bindParam(':sectionNum', $sectionNum);
        $stmt->bindParam(':days', $daysTaught);
        $stmt->bindParam(':beginTime', $beginTime);
        $stmt->bindParam(':labDays', $labMeetingDays);
        $stmt->bindParam(':labTime', $labBeginTime);
        $stmt->bindParam(':session', $session);
        $stmt->bindParam(':year', $year);
        $stmt->bindParam(':course_id', $course_id);
        $stmt->execute();

        $result = $stmt->errorInfo();

        if (isset($result[1]) || isset($result[2])) {
            $error = True;
        }
        // retrieve the section id
        $section_id = $dbh->lastInsertId();

        // add the user to the section
        $query = "INSERT INTO enrollment (section_id, user_id, dropped) VALUES (:section_id, :user_id, 0);";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(':section_id', $section_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        $result = $stmt->errorInfo();

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
     * @param PDO $dbh The database object.
     * @param int $courseId The id of the course.
     * @return array An array with all section data.
     */
    function getSectionsFromCourse($dbh, $courseId) {
        $query = 
            "SELECT *
             FROM section
             Where course_id = :courseId;";

        $stmt = $dbh->prepare($query);
        $stmt->bindParam(':courseId', $courseId);
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }
?>


