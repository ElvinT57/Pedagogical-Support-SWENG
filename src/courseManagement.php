<?php
    require_once './vendor/autoload.php';
    require_once './userGate.php';
    require_once './api/model/course.php';
    require_once './api/config/db_connect.php';

    authorization_gate([ USER_GUEST, USER_STUDENT ]);
    
    if(isset($_POST['submit'])) {
        $courseTitle = $_POST['courseTitle'];
        $startDate = $_POST['start-date'];
        $endDate = $_POST['end-date'];

        $validCourseQuery = "SELECT course.title
                             FROM course
                             WHERE title = :courseTitle";

        $stmt = $dbh->prepare($validCourseQuery);
        $stmt->bindParam(':courseTitle', $courseTitle);
        $stmt->execute();

        $count = $stmt->rowCount();
        //checks if course exists in records
        if($count >= 1) {
            $info = [
                "type" => INFO_ERROR,
                "message" => "A course with the title '${$courseTitle}' already exists."
            ];
        } else {
            if (addCourse($dbh, $courseTitle, $startDate, $endDate, $_SESSION['user']['id'])) {
                $info = [
                    "type" => INFO_SUCCESS,
                    "message" => "Created '${$courseTitle}'."
                ];
            } else {
                $info = [
                    "type" => INFO_ERROR,
                    "message" => "Something went wrong while trying to create the course."
                ];
            }
        }
    }     

    $loader = new \Twig\Loader\FilesystemLoader('./templates');
    $twig = new \Twig\Environment($loader, [ ]);
    $twig->addExtension(new \Twig\Extension\StringLoaderExtension());
    
    $user = $_SESSION['user'];
    $pageTitle = PAGE_TITLE;
    echo $twig->render('view/courseManagement.twig.html', [
        'page_title' => "Course Management - {$pageTitle}",
        'info' => (isset($info)) ? $info : NULL,
        'user' => $user,
        'breadcrumbs' => [
            [ 'text' => "Manage Courses", 'link' => '#' ]
        ]
    ]);
?>
