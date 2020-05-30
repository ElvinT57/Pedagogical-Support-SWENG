<?php
require_once './vendor/autoload.php';
require_once './userGate.php';
require_once './api/config/db_connect.php';
require_once './api/model/course.php';
require_once './api/model/grade.php';
require_once './api/model/assignment.php';

authorization_gate([ USER_GUEST ]);

$loader = new \Twig\Loader\FilesystemLoader('./templates');
$twig = new \Twig\Environment($loader, [ ]);
$twig->addExtension(new \Twig\Extension\StringLoaderExtension());

$user = $_SESSION['user'];
$courses = getCoursesForStudent($dbh, $user['id']);
foreach ($courses as &$course) {
    $grades = getGradesForUsersForSection($dbh, $course["section_id"]);
    $assignments = getAssignmentsFromSection($dbh, $course["section_id"]);

    $courseCurrentGrade = 0;
    $courseMaxGrade = 0;

    //Init grade as null to signify "not submitted"
    //Will be filled with a grade if the user has been graded here
    foreach ($assignments as &$a1) {
        $a1["grade"] = NULL;
    }

    // loop through and associate grades with assignments
    // skip any grades for users that aren't us
    // could use a query that only returns our grades, but
    // getGradesForUsersForSection was already written
    for ($i = count($grades) - 1; $i >= 0; $i--) {
        $grade = $grades[$i];
        if ($grade["user_id"] != $user["id"]) {
            continue;
        }

        // calculate final grade w/ penalties
        $grade["finalGrade"] = calculateGrade(
            $grade["gradeBase"],
            $grade["extraCredit"],
            $grade["latePenalty"],
            $grade["dateDue"],
            $grade["timeSubmitted"],
            $grade["latePenaltyInterval"],
            $grade["ignoreLatePenalty"]
        );

        // associate grades with assignments & increment counters
        foreach ($assignments as &$assignment) {
            if ($assignment["assignment_id"] == $grade["assignment_id"]) {
                $assignment["grade"] = $grade;

                $courseCurrentGrade += $grade["finalGrade"];
                $courseMaxGrade += $assignment["maxGrade"];
                break;
            }
        }
    }

    $course["currentGrade"] = $courseCurrentGrade;
    $course["maxGrade"] = $courseMaxGrade;
}

echo $twig->render('view/studentReport.twig.html', [
    'page_title' => PAGE_TITLE,
    'info' => (isset($info)) ? $info : NULL,
    'user' => $user,
    'courses' => $courses,
    'breadcrumbs' => [
        [ 'text' => 'Report Card', 'link' => '#' ]
    ]
]);