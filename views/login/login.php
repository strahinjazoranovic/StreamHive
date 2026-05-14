<?php
// Fallback mechanism to determine base path for assets and links
if (!isset($basePath)) {
  $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
  $viewsPosition = strpos($scriptName, '/views/');
  $projectBase = $viewsPosition !== false ? substr($scriptName, 0, $viewsPosition) : '';
  $basePath = $projectBase . '/public';
}
$error = $error ?? "";
$email = $email ?? "";
?>
<!DOCTYPE html>
<html lang="en">
 <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="<?= $basePath ?>/css/login.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="icon" href="../assets/logo.png" type="image/png" />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
      rel="stylesheet"
    />
    <link
      rel="icon"
      type="image/svg+xml"
      href="<?= $basePath ?>/logos/streamHiveLogo.png"
    />
    <title>StreamHive - Login</title>
  </head>
<body>
    <form method="post" action="<?= $basePath ?>/index.php?route=login" class="loginContainer">
        <div class="loginForm">
            <h1 class="textHeader">
                Stream<span class="textAccent">Hive</span>
            </h1>
            <label>E-mail
                <input type="email" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, "UTF-8") ?>" required>
            </label>
            <label>Wachtwoord
                <input type="password" name="password" required>
            </label>
            <button type="submit" class="button">Inloggen</button>
            <p class="hint">Nog geen account? <a href="<?= $basePath ?>/index.php?route=register">Meld je aan</a>.</p>
            <?php if ($error !== "") { ?>
                <p class="hint"><?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?></p>
            <?php } ?>
        </div>
    </form>
</body>
</html>
