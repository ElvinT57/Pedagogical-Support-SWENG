<?php
    /**
     * @param PDO $dbh The database object.
     * @param int $userid The id of the user to get the courses for.
     * @return array An array with all course data for the user or null if none were found.
     */
    function getAllCoursesForUser($dbh, $userId) {
        $query =
            "SELECT 
                course.*
            FROM enrollment
                JOIN section on section.id = enrollment.section_id
                JOIN course on course.id = section.course_id
            WHERE
                enrollment.user_id=:userId
            GROUP BY
                course.id";

        $query = $dbh->prepare($query);
        $query->bindParam(":userId", $userId);
        $query->execute();
        $result = $query->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    /**
     * @param PDO $dbh The database object.
     * @param int $courseId The id of the course to get.
     * @return array An array with all the properties of the course.
     */
    function getCourse($dbh, $courseId) {
        $query =
            "SELECT *
                FROM course
            WHERE
                id=:courseId;";
        
        $query = $dbh->prepare($query);
        $query->bindParam(":courseId", $courseId);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);

        return $result;
    }

    /**
     * @param PDO $dbh The database object.
     * @param int $sectionId The id of the section to get the course for.
     * @return array An array with the section properties.
     */
    function getCourseForSection($dbh, $sectionId) {
        $query =
            "SELECT
                course.*
            FROM section
                JOIN course ON course.id = section.course_id
            WHERE
                section.id=:sectionId;";
        
        
        $query = $dbh->prepare($query);
        $query->bindParam(":sectionId", $sectionId);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);

        return $result;
    }

    /**
    * @param PDO $dbh The database object.
    * @param string $courseTitle The title of the course.
    * @param string $startDate The starting date of the course.
    * @param string $endingDate The ending date of the course.
    * @param int $userId The user id of the user in session.
    */
    function addCourse($dbh, $courseTitle, $startDate, $endDate, $userId) {
        $query = "SELECT addNewCourse(:courseTitle, :startDate, :endDate, :userId);";

        $stmt = $dbh->prepare($query);
        $stmt->bindParam(':courseTitle', $courseTitle);
        $stmt->bindParam(':startDate', $startDate);
        $stmt->bindParam(':endDate', $endDate);
        $stmt->bindParam(':userId', $userId);

        return $stmt->execute();
    }

    function editCourse($dbh, $course_id, $courseTitle, $startDate, $endDate){
        $query = "UPDATE course SET title = :courseTitle, startDate = :startDate, endDate = :endDate WHERE (id = :course_id);";

        $query = $dbh->prepare($query);
        $query->bindParam(':courseTitle', $courseTitle);
        $query->bindParam(':startDate', $startDate);
        $query->bindParam(':endDate', $endDate);
        $query->bindParam(':course_id', $course_id);
        // execute query
        $query->execute();
        // retrieve status of the query
        $error = $query->errorInfo();  

        return !(isset($gradeQueryErrorInfo[1]) || isset($gradeQueryErrorInfo[2]));
    }

    /**
     * get the course details a student user is enrolledin like instructor's name,
     * when the class meets, and the class title.
     * @param PDO $dbh The database object.
     * @param int $userid The id of the user to get the courses for.
     * @return array An array with all course data for the student user or null if none were found.
     */
    function getCoursesForStudent($dbh, $userId) {
        $query = 
        "SELECT
            section.id AS 'section_id',
            section.number AS 'section_number',
            section.crn AS 'section_crn',
            section.meetingDays AS 'meetingDays',
            section.beginTime AS 'meetingTime',
            IFNULL(section.labMeetingDays, 'None') AS 'labMeetingDay',
            IFNULL(section.labBeginTime, '') AS 'labMeetingTime',
            section.semester AS 'section_semester',
            section.session AS 'section_session',
            section.year AS 'section_year',
            course.id AS 'course_id',
            course.title,
            course.startDate AS 'course_startDate',
            course.endDate AS 'course_endDate',
            GROUP_CONCAT(_teacher.teacher_firstName, ' ', _teacher.teacher_lastName
            SEPARATOR ', ') AS 'instructor'
        FROM
            user
            JOIN enrollment ON enrollment.user_id = user.id
            JOIN section ON section.id = enrollment.section_id
            JOIN course ON course.id = section.course_id
            LEFT JOIN (
                SELECT -- select the teachers for each section
                    enrollment.section_id,
                    enrollment.id AS 'teacher_enrollment_id',
                    user.id AS 'teacher_id',
                    user.userName AS 'teacher_userName',
                    user.firstName AS 'teacher_firstName',
                    user.lastName AS 'teacher_lastName'
                FROM
                    enrollment
                    JOIN user ON enrollment.user_id = user.id
                WHERE
                    user.userType = 'Teacher'
            ) AS _teacher ON _teacher.section_id = section.id
        WHERE
            user.id = :userid
        GROUP BY
            section.id;";

        $stmt = $dbh->prepare($query);
        $stmt->bindParam(':userid', $userId);
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }