<?php
require_once './vendor/autoload.php';

if (isset($_POST['submit'])) {
    require_once './api/config/db_connect.php';

    $id = $_SERVER['REMOTE_USER'];
    
    $query = "SELECT * FROM user WHERE bannerId=:id";

    $statement = $dbh->prepare($query);
    $statement->bindParam(':bannerId', $id);
    $statement->execute();
    $result = $statement->fetch(PDO::FETCH_ASSOC);

    if ($result != null) {
        session_start();
        $_SESSION['user'] = $result;
        header("Location: landing.php");
    } else {
        $loader = new \Twig\Loader\FilesystemLoader('./templates');
        $twig = new \Twig\Environment($loader, [ ]);
        
        echo $twig->render('view/noUser.twig.html', [
            'page_title' => 'No User - Pedagogical Support',
            'username' => $username
        ]);
    }
} else {
    $loader = new \Twig\Loader\FilesystemLoader('./templates');
    $twig = new \Twig\Environment($loader, [ ]);
    
    echo $twig->render('view/login.twig.html', [
        'page_title' => 'Login - Pedagogical Support'
    ]);
}