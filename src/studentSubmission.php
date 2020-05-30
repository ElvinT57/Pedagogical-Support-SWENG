<?php
require_once './vendor/autoload.php';
require_once './api/config/db_connect.php';
require_once './api/model/submission.php';
require_once './api/model/assignment.php';
require_once './api/model/section.php';
require_once './api/model/course.php';
require_once './api/model/grade.php';
require_once './api/model/comment.php';
require_once './userGate.php';


authorization_gate([ USER_GUEST ]);

$loader = new \Twig\Loader\FilesystemLoader('./templates');
$twig = new \Twig\Environment($loader, [ ]);
$twig->addExtension(new \Twig\Extension\StringLoaderExtension());

// retrieve necessary information for this page
$user = $_SESSION['user'];

if (isset($_FILES['upload'])) {    
    // check if we have an image
    if ($_FILES['upload']['tmp_name'] == "") {
        echo $twig->render('common/uploadError.twig.html', [
            'page_title' => "Submission - Error",
        ]);
        die();
    }
    
    if (!is_dir("./uploads/")) {
        mkdir('./uploads');
    }

    // TODO: We should probably do file type checking.
    $userFolder = "./uploads/" . $user['bannerId'] . '/';
    if (!is_dir($userFolder)) {
        mkdir($userFolder);
    }

    $filename = time() . '_' . $_FILES['upload']['name'];
    $filepath = $userFolder . $filename;

    move_uploaded_file($_FILES['upload']['tmp_name'], $filepath);

    $result = insertAssignmentSubmissionWithUpload($dbh, $_GET['assignment_id'], $user['id'], $_FILES['upload']['tmp_name'], $filepath);

    $section_id = $_GET['section_id'];

    if ($result) {
        header("Location: viewSection.php?section_id=$section_id");
    }
}

$section = getSection($dbh, $_GET['section_id']);
$course = getCourseForSection($dbh, $_GET['section_id']);

// retrieve the assignment information 
$assignment =  getAssignmentFromSection($dbh, $_GET['assignment_id'], $_GET['section_id']);

// retrieve previous submissions
$grades = getAllGradesForAssignment($dbh, $user['id'], $_GET['assignment_id'], $_GET['section_id']);

// retrieve the comments that were assigned to each submission
foreach($grades as &$grade){
    $comments = getGradeCommentDefintions($dbh, $grade['id']);
    $grade['comments'] = $comments;

    $grade['gradeBase'] = calculateGrade(
        $grade['gradeBase'],
        $grade['extraCredit'],
        $grade['latePenalty'],
        $assignment['dueDate'],
        $grade['timeSubmitted'],
        $assignment['latePenaltyInterval'],
        $grade['ignoreLatePenalty']
    );
}

$pageTitle = PAGE_TITLE;
echo $twig->render('view/studentSubmission.twig.html', [
    'page_title' => "Submission - {$pageTitle}",
    'info' => (isset($info)) ? $info : NULL,
    'user' => $user,
    'course' => $course,
    'section' => $section,
    'assignment' => $assignment,
    'grades' => $grades,
    'breadcrumbs' => [
        [ 'text' => $course['title'], 'link' => 'viewSection.php?section_id={{ section.id }}' ],
        [ 'text' => $assignment['title'], 'link' => '#' ]
    ]
]);
?>