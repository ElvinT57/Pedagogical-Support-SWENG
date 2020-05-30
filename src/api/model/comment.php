<?php
    /**
     * Retrieves all the comment definitions that are not disabled.
     * @param PDO $dbh The database object.
     * @param int $userId The id of the user the comment belongs to.
     * @return bool True if the operation succeeded, otherwise false.
     */
    function getCommentDefinitions($dbh, $user_id) {
        $getPredefinedQuery =
            "SELECT
                cd.*,
                IFNULL(c.count, 0) as 'frequency'
            FROM
                commentDefinition cd
            LEFT JOIN (
                SELECT
                    DISTINCT ac.commentDefinition_id AS 'commentDefiniton_id',
                    count(*) as 'count'
                FROM gradeComment AS ac
                WHERE ac.commentDefinition_id IN (
                    SELECT id
                    FROM commentDefinition AS cd
                    WHERE cd.user_id = :user_id
                )
                GROUP BY ac.commentDefinition_id
                ORDER BY COUNT
            ) as c
            ON cd.id = c.commentDefiniton_id
            WHERE
                cd.user_id = :user_id
            AND
                cd.disabled = 0;";

        $getPredefinedStmt = $dbh->prepare($getPredefinedQuery);
        $getPredefinedStmt->bindParam(':user_id', $user_id);
        $getPredefinedStmt->execute();

        return $getPredefinedStmt->fetchAll();
    }

    /**
     * @param dbh The database object.
     * @param assignmentSubmission_id primary key of the assignment submission.
     */
    function getGradeCommentDefintions($dbh, $grade_id) {
        $sql = 
            "SELECT
                gradeComment.id as 'id',
                commentDefinition.id as 'commentDefinition_id',
                commentDefinition.text,
                commentDefinition.disabled
            FROM gradeComment
                JOIN commentDefinition on commentDefinition.id = gradeComment.commentDefinition_id
            WHERE
                gradeComment.grade_id = :grade_id;";

        $sql = $dbh->prepare($sql);
        $sql->bindParam(':grade_id', $grade_id);
        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Toggle a comment definition to enabled or disabled.
     * @param PDO $dbh The database object.
     * @param int $userId The id of the user the comment belongs to.
     * @param string $commentText The text of the comment.
     * @return bool True if the operation succeeded, otherwise false.
     */
    function addCommentDefinition($dbh, $userId, $commentText) {
        $query = 
            "INSERT INTO
                commentDefinition
            SET
                user_id=:userId,
                text=:commentText;";
        
        $query = $dbh->prepare($query);
        $query->bindParam(":userId", $userId);
        $query->bindParam(":commentText", $commentText);
        $query->execute();
        $result = $query->errorInfo();

        return (!isset($result[1]) && !isset($result[2]));
    }

    
    /**
     * Toggle a comment definition to enabled or disabled.
     * @param PDO $dbh The database object.
     * @param int $commentId The id of the comment to toggle.
     * @param bool $disabled Whether the comment should be disabled (TRUE) or enabled (FALSE).
     * @return bool True if the operation succeeded, otherwise false.
     */
    function toggleCommentDefinition($dbh, $commentId, $disabled) {
        $query = 
            "UPDATE
                commentDefinition
            SET
                disabled=:disabled
            WHERE
                id=:commentId;";
        
        $query = $dbh->prepare($query);
        $query->bindParam(":commentId", $commentId);
        $query->bindParam(":disabled", $disabled);
        $query->execute();
        $result = $query->errorInfo();

        return (!isset($result[1]) && !isset($result[2]));
    }
?>