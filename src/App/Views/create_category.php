<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Category</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <?php require __DIR__.'/partials/navbar.php'; ?>
    <div class="container">
        <h1>Kreiraj novu kategoriju</h1>
        <form action="/create/category" method="POST">
            <input type="text" name="categoryName" placeholder="Unesite naziv kategorije" required><br><br>
            <textarea name="categoryDescription" id="" placeholder="Unesite opis kategorije" required></textarea><br><br>
            <button name='btnCreateCategory'>Napravi kategoriju</button><br><br>
        </form>
        <br>
        <?php
        use App\Http\Session;

        $session = new Session();
        $flashMessage = $session->getFlash('flash_message');
        if($flashMessage !== null){
            echo $flashMessage;
        }
        ?>
    </div>
</body>
</html>