<?php
require_once './userGate.php';
require_once './vendor/autoload.php';
require_once './api/model/user.php';
require_once './api/model/section.php';
require_once './api/model/course.php';
require_once './api/model/grade.php';
require_once './api/model/assignment.php';
require_once './api/config/db_connect.php';

authorization_gate([ USER_GUEST, USER_STUDENT ]);

$loader = new \Twig\Loader\FilesystemLoader('./templates');
$twig = new \Twig\Environment($loader, [ ]);
$twig->addExtension(new \Twig\Extension\StringLoaderExtension());

$sectionId = $_GET['section_id'];

$course = getCourseForSection($dbh, $sectionId);
$section = getSection($dbh, $sectionId);

$grades = getGradesForUsersForSection($dbh, $sectionId);
$assignments = getAssignmentsFromSection($dbh, $sectionId);

$users = getUsersForSection($dbh, $sectionId);

$assignmentIds = [];
foreach ($assignments as $assignment) {
    $assignmentIds[$assignment["assignment_id"]] = NULL;
}
foreach ($users as &$user2) {
    $user2["assignments"] = $assignmentIds;
    $userId = $user2["id"];
    foreach ($grades as &$grade) {
        if ($grade["user_id"] != $userId) {
            continue;
        }

        if (is_null($grade["gradeBase"])) {
            $grade["finalGrade"] = NULL;
        } else {
            $grade["finalGrade"] = calculateGrade(
                $grade["gradeBase"],
                $grade["extraCredit"],
                $grade["latePenalty"],
                $grade["dateDue"],
                $grade["timeSubmitted"],
                $grade["latePenaltyInterval"],
                $grade["ignoreLatePenalty"]
            );
        }

        $user2["assignments"][$grade["assignment_id"]] = $grade;
    }
}

$user = &$_SESSION['user'];
echo $twig->render('view/facultyGradesReport.twig.html', [
    'page_title' => PAGE_TITLE,
    'info' => (isset($info)) ? $info : NULL,
    'grades' => $grades,
    'user' => $user,
    'users' => $users,
    'assignments' => $assignments,
    'course' => $course,
    'section' => $section,
    'breadcrumbs' => [
        [ 'text' => $course['title'], 'link' => 'viewCourse.php?course_id={{ course.id }}' ],
        [ 'text' => 'Section {{ section.number }}', 'link' => 'viewSection.php?section_id={{ section.id }}' ],
        [ 'text' => 'Grades Report', 'link' => '#' ]
    ]
]);
?>