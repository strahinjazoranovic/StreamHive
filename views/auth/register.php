<?php

// Fallback mechanism to determine base path for assets and links
if (!isset($basePath)) {
  $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
  $viewsPosition = strpos($scriptName, '/views/');
  $projectBase = $viewsPosition !== false ? substr($scriptName, 0, $viewsPosition) : '';
  $basePath = $projectBase . '/public';
}
$error = $error ?? "";
$success = $success ?? "";
$email = $email ?? "";
$username = $username ?? "";
$role = $role ?? "user";
?>
<!DOCTYPE html>
<html lang="en">
 <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="<?= $basePath ?>/css/login.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
      rel="stylesheet"
    />
     <link
      rel="icon"
      type="image/svg+xml"
      href="<?= $basePath ?>/logos/streamHiveLogo.png"
    />
    <title>StreamHive - Register</title>
  </head>
<body>
    <form method="post" action="<?= $basePath ?>/index.php?route=register" class="loginContainer">
      <div class="loginForm">
          <a href="<?= $basePath ?>/index.php?route=home" class="navbarLogoLink">
          <h1 class="textHeader">Stream<span class="textAccent">Hive</span></h1>
        </a>
        <label>Gebruikersnaam
            <input type="text" name="username" value="<?= htmlspecialchars($username, ENT_QUOTES, "UTF-8") ?>" minlength="4" maxlength="40" required>
        </label>
        <label>Rol
            <select name="role" required>
                <option value="user" <?= $role === "user" ? "selected" : "" ?>>User</option>
                <option value="admin" <?= $role === "admin" ? "selected" : "" ?>>Admin</option>
            </select>
        </label>
        <label>E-mail
            <input type="email" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, "UTF-8") ?>" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" required>
        </label>
        <label>Wachtwoord
            <input type="password" name="password" minlength="8" maxlength="80" required>
        </label>
        <label>Bevestig wachtwoord
            <input type="password" name="confirm_password" minlength="8" maxlength="80" required>
        </label>
        <button type="submit" class="button">Account aanmaken</button>
            <p class="hint">Heb je al een account? <a href="<?= $basePath ?>/index.php?route=login">Inloggen</a>.</p>
            <?php if ($error !== "") { ?>
                <p class="hint"><?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?></p>
            <?php } elseif ($success !== "") { ?>
                <p class="hint"><?php echo htmlspecialchars($success, ENT_QUOTES, "UTF-8"); ?></p>
            <?php } ?>
      </div>
    </form>
</body>
</html>