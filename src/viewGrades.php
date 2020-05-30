<?php
require_once './userGate.php';
authorization_gate([ USER_GUEST, USER_STUDENT ]);

require_once './api/config/db_connect.php';
require_once './api/model/user.php';
require_once './api/model/course.php';
require_once './api/model/section.php';
require_once './api/model/submission.php';
require_once './api/model/assignment.php';
require_once './vendor/autoload.php';
require_once './api/model/util.php';
require_once './api/model/grade.php';

$loader = new \Twig\Loader\FilesystemLoader('./templates');
$twig = new \Twig\Environment($loader, [ ]);
$twig->addExtension(new \Twig\Extension\StringLoaderExtension());

if (!isset($_GET["section_id"])) {
  die("Error: Section ID not specified.");
}
if (!isset($_GET["assignment_id"])) {
  die("Error: Assignment ID not specified.");
}

$section = getSection($dbh, $_GET['section_id']);
$course = getCourseForSection($dbh, $_GET['section_id']);
$assignment = getAssignmentFromSection($dbh, $_GET['assignment_id'], $_GET['section_id']);
$students = getUsersForSection($dbh, $_GET['section_id'], USER_STUDENT);
$submissions = [];

$userIds = [];
$n_ids = count($students);
for ($i = 0; $i < $n_ids; $i++) {
  array_push($userIds, $students[$i]['id']);
}

if ($n_ids > 0) {
  $submissions = getSubmissionsFromUserList($dbh, $_GET['assignment_id'], $userIds);
  foreach ($students as &$student) {
    foreach ($submissions as &$submission) {
      if ($student['id'] === $submission['user_id']) {
        if(isset($submission['grade_id'])){
            // calculate the grade
          $submission['gradeBase'] = calculateGrade($submission['gradeBase'], $submission['extraCredit'], $assignment['latePenalty'], 
                                    $assignment['dueDate'], $submission['timeSubmitted'], $assignment['latePenaltyInterval'], $submission['ignoreLatePenalty']);
          $student['submission'] = $submission;
        }else{
          $student['gradeBase'] = null;
          $student['submission'] = $submission;
        }
      }
    }
  }
}

// sort names
$students = sortTable($students, 'lastName');

$user = $_SESSION['user'];

$pageTitle = PAGE_TITLE;
echo $twig->render('view/grades/faculty.twig.html', [
    'page_title' => "Assignment Grades - {$pageTitle}",
    'info' => (isset($info)) ? $info : NULL,
    'students' => $students,
    'submission' => $submissions,
    'assignment' => $assignment,
    'course' => $course,
    'section' => $section,
    'user' => $user,
    'breadcrumbs' => [
        [ 'text' => $course['title'], 'link' => 'viewCourse.php?course_id={{ course.id }}' ],
        [ 'text' => 'Section {{ section.number }}', 'link' => 'viewSection.php?section_id={{ section.id }}' ],
        [ 'text' => 'Grades: {{ assignment.title }}', 'link' => '#' ]
    ]
]);