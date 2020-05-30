<?php
require_once './vendor/autoload.php';
require_once './api/config/db_connect.php';
require_once './api/model/course.php';
require_once './api/model/assignment.php';
require_once './api/model/user.php';
require_once './api/model/section.php';
require_once './api/model/submission.php';
require_once './api/model/grade.php';
require_once './userGate.php';

authorization_gate([ USER_GUEST ]);

$loader = new \Twig\Loader\FilesystemLoader('./templates');
$twig = new \Twig\Environment($loader, [ ]);
$twig->addExtension(new \Twig\Extension\StringLoaderExtension());

$user = $_SESSION['user'];
$userType = $user['userType'];
$section_id = $_GET['section_id'];

$course = getCourseForSection($dbh, $_GET['section_id']);
$section = getSection($dbh, $_GET['section_id']);

if ($userType == USER_STUDENT) {
    $assignments = getSubmissionsInSection($dbh, $user['id'], $section_id);

    // derived the net grade for each graded assignment
    for($i = 0; $i < sizeof($assignments); $i += 1){
        $assignment = $assignments[$i];
        $baseGrade = $assignment['baseGrade'];
        // calculate grade for every assignment that has been graded.
        if($baseGrade){
            $assignments[$i]['baseGrade'] = calculateGrade($baseGrade, $assignment['extraCredit'], $assignment['latePenalty'],
                                            $assignment['dueDate'], $assignment['timeSubmitted'], $assignment['latePenaltyInterval'], $assignment['ignoreLatePenalty']);
        }
    }

    echo $twig->render('view/section/student.twig.html', [
        'page_title' => 'Pedagogical Support',
        'info' => (isset($info)) ? $info : NULL,
        'assignments' => $assignments,
        'course' => $course,
        'section' => $section,
        'user' => $user,
        'breadcrumbs' => [
            [ 'text' => $course['title'], 'link' => 'viewSection.php?section_id={{ section.id }}' ]
        ]
    ]);
} else {
    $users = getUsersForSection($dbh, $_GET['section_id']);
    $userIds = array_map(function($user) { return $user['id']; }, $users);

    $assignments = getAssignmentsForCourseSection($dbh, $_GET['section_id']);
    
    foreach ($assignments as &$assignment) {
        $submissions = getSubmissionsFromUserList($dbh, $assignment['id'], $userIds);

        // convert date times to timestamps for comparison
        foreach ($submissions as &$submission) {
            $submission['timeSubmitted'] = strtotime($submission['timeSubmitted']);
        }

        // sort newest submission to oldest
        usort($submissions, function($a, $b) {
            if ($a == $b) return 0;
            return ($a['timeSubmitted'] < $b['timeSubmitted']) ? 1 : -1;
        });

        // convert back to original format
        foreach ($submissions as &$submission) {
            $submission['timeSubmitted'] = date('Y-m-d H:m:s', $submission['timeSubmitted']);
        }

        // only care if we graded the latest submission
        // loop through the now newest-to-oldest submissions
        $graded = [];
        foreach ($submissions as &$submission) {

            $userId = $submission['user_id'];
            $time = $submission['timeSubmitted'];

            // skip submissions older than the newest for a specific user
            if ( array_key_exists($userId, $graded) ) {
                continue;
            }

            $graded[$userId] = isset($submission["grade_id"]) ? 1 : 0;
        }
        
        $assignment['graded'] = array_sum(array_values($graded));
        $assignment['submitted'] = count($graded);
    }

    $pageTitle = PAGE_TITLE;
    echo $twig->render('view/section/faculty.twig.html', [
        'page_title' => "View Section - {$pageTitle}",
        'info' => (isset($info)) ? $info : NULL,
        'assignments' => $assignments,        
        'course' => $course,
        'users' => $users,
        'section' => $section,
        'user' => $user,
        'breadcrumbs' => [
            [ 'text' => $course['title'], 'link' => 'viewCourse.php?course_id={{ course.id }}' ],
            [ 'text' => 'Section {{ section.number }}', 'link' => '#' ]
        ]
    ]);
}
?>
