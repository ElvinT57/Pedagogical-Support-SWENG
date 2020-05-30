<?php
    require_once './userGate.php';
    require_once './vendor/autoload.php';
    require_once './api/config/db_connect.php';
    require_once './api/model/assignment.php';
    require_once './api/model/grade.php';
    require_once './api/model/section.php';
    require_once './api/model/course.php';
    require_once './api/model/util.php';

    authorization_gate([ USER_GUEST, USER_STUDENT ]);

$loader = new \Twig\Loader\FilesystemLoader('./templates');
$twig = new \Twig\Environment($loader, [
    //'cache' => './cache',
]);
$twig->addExtension(new \Twig\Extension\StringLoaderExtension());

$section = getSection($dbh, $_GET['section_id']);
$course = getCourseForSection($dbh, $_GET['section_id']);

$users = getStudentsFromAssignment($dbh, $_GET['section_id'], $_GET['assignment_id']);
// retrieve all the grades for each student
$grades = getLabGrades($dbh, $_GET['assignment_id'], $_GET['section_id']);

foreach ($users as &$user) {
    foreach ($grades as &$grade) {
      if ($user['user_id'] === $grade['user_id']) {
        $user['grade'] = $grade['gradeBase'];
        break;
      }
    }
}

// sort table by last name
$users = sortTable($users, 'lastName');

// retrieve the assignment information
$assignment = getAssignmentFromSection($dbh, $_GET['assignment_id'], $_GET['section_id']);

echo $twig->render('view/sectionLabAssignments.html', [
    'page_title' => PAGE_TITLE,
    'info' => (isset($info)) ? $info : NULL,
    'users' => $users,
    'assignment' => $assignment,
    'course' => $course,
    'section' => $section,
    'breadcrumbs' => [
      ['text' => $course['title'], 'link' => 'viewSection.php?course_id={{ course.id }}&section_id={{ section.id }}'],
      ['text' => $assignment['title'], 'link' => '#']
    ]
]);
?>