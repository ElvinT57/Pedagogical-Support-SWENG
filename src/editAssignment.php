<?php
require_once './vendor/autoload.php';
require_once './userGate.php';
require_once './api/config/db_connect.php';
require_once './api/model/enrollment.php';
require_once './api/model/course.php';
require_once './api/model/section.php';
require_once './api/model/assignment.php';
require_once './api/model/submission.php';
require_once './userGate.php';

authorization_gate([ USER_GUEST, USER_STUDENT ]);

$loader = new \Twig\Loader\FilesystemLoader('./templates');
$twig = new \Twig\Environment($loader, [ ]);
$twig->addExtension(new \Twig\Extension\StringLoaderExtension());
$user = $_SESSION['user'];
$course = getCourseForSection($dbh, $_GET['section_id']);
$allCourseSections = getSectionsFromCourse($dbh, $course['id']);
$section = getSection($dbh, $_GET['section_id']);
$assignment = getAssignment($dbh, $_GET['assignment_id']); 
$sectionAssignment = getSectionAssignment($dbh, $_GET['assignment_id'], $_GET['section_id']);


$pageTitle = PAGE_TITLE;
echo $twig->render('view/editAssignment.twig.html', [
    'page_title' => "Create Course - {$pageTitle}",
    'info' => (isset($info)) ? $info : NULL,
    'course' => $course,
    'assignment' => $assignment,
    'currentSection' => $section,
    'sections' => $allCourseSections,
    'sectionAssignment' => $sectionAssignment,
    'user' => $user,
    'breadcrumbs' => [
        [ 'text' => $course['title'], 'link' => 'viewCourse.php?course_id={{ course.id }}' ],
        [ 'text' => 'Section {{ currentSection.number }}', 'link' => 'viewSection.php?section_id={{ currentSection.id }}' ],
        [ 'text' => 'Grades: {{ assignment.title }}', 'link' => 'viewGrades.php?assignment_id={{ assignment.assignment_id }}&section_id={{ currentSection.id }}' ],
        [ 'text' => 'Edit Assignment', 'link' => '#' ]
    ]
]);
?>
