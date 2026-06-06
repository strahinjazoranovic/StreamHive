<?php
// Fallback mechanism to determine base path for assets and links
if (!isset($basePath)) {
  $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
  $viewsPosition = strpos($scriptName, '/views/');
  $projectBase = $viewsPosition !== false ? substr($scriptName, 0, $viewsPosition) : '';
  $basePath = $projectBase . '/public';
}

// Available error messages
$errors = [
    'admin_required' => 'Access denied, you must be an admin to access this page',
    'not_found' => 'Requested resource not found.',
    'invalid_user' => 'The user ID is invalid.',
    'incorrect_method' => 'Incorrect request method. Please use POST.'
];

$type = $_GET['type'] ?? '';

// Get the error message and default to something went wrong
$message = $errors[$type] ?? 'Something went wrong.';
?>
<html lang="en">  
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="<?= $basePath ?>/css/style.css" />
    <link rel="stylesheet" href="<?= $basePath ?>/css/video.css" />
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
    <title>StreamHive - Error</title>
  </head>
  <body data-base-path="<?= $basePath ?>">
    <div>
      <nav class="navbar">
        <div class="navbarLeft">
          <button class="iconButton">
            <img
              src="<?= $basePath ?>/logos/hamburgerMenu.svg"
              class="img-hover sidebar-toggle"
              alt="sidebar button"
              draggable="false"
            />
          </button>

          <a href="<?= $basePath ?>/index.php?route=home" class="navbarLogoLink">
          <h1 class="textHeader">Stream<span class="textAccent">Hive</span></h1>
        </a>
        </div>

        <div class="navbar-center">
          <div class="searchBar">
            <input type="text" class="searchInput" placeholder="Search" />
            <button class="iconButton">
              <img
                src="<?= $basePath ?>/logos/search.svg"
                class="img-hover"
                alt="Search"
                draggable="false"
              />
            </button>
          </div>
        </div>

        <div class="navbarRight">
          <button class="iconButton">
            <a
              href="<?= $basePath ?>/index.php?route=admin"
              class="sidebarLink"
            >
            <img
              src="<?= $basePath ?>/logos/upload.svg"
              class="img-hover"
              alt="Upload button"
              draggable="false"
            />
            </a>
          </button>

          <?php if ($isLoggedIn): ?>
            <div class="profileMenuWrapper">
              <button
                type="button"
                class="iconButton profileMenu-trigger"
                aria-haspopup="true"
                aria-expanded="false"
              >
                <img
                  src="<?= $basePath ?>/logos/profile.svg"
                  class="img-hover"
                  alt="Profile button"
                  draggable="false"
                />
              </button>
              <div class="profileMenu" role="menu">
                <a href="<?= $basePath ?>/index.php?route=user&id=<?= (int)($_SESSION['user_id'] ?? 0) ?>" class="profileMenu-item">My profile</a>
                <a href="<?= $basePath ?>/index.php?route=logout" class="profileMenu-item">Logout</a>    
              </div>
            </div>
          <?php else: ?>
            <button class="iconButton">
              <a
                href="<?= $basePath ?>/index.php?route=login"
                class="sidebarLink"
              >
              <img
                src="<?= $basePath ?>/logos/profile.svg"
                class="img-hover"
                alt="Profile button"
                draggable="false"
              />
              </a>
            </button>
          <?php endif; ?>
        </div>
      </nav>

      <div class="sidebar">
        <ul class="sidebarMenu">
          <!-- If the user is not logged in show this message -->
          <?php if (!$isLoggedIn): ?>
            <div class="loginPromptSidebar">
              <p class="loginMessage">Please log in to access more pages.</p>
              <a href="<?= $basePath ?>/index.php?route=login">
                <button 
                  href="<?= $basePath ?>/index.php?route=login" 
                  class="button">
                  Log In
                </button>
              </a>
            </div>
          <!-- If the user is logged in show this list -->
          <?php else: ?>
          <li class="sidebarItem">
            <a
              href="<?= $basePath ?>/index.php?route=home"
              class="sidebarLink"
            >
              <img
                src="<?= $basePath ?>/logos/home.svg"
                class="sidebarIcon"
                alt="Home"
              />
              <span class="sidebarText">Home</span>
            </a>
          </li>

          <li class="sidebarItem">
            <a 
              href="<?= $basePath ?>/index.php?route=subscriptions" 
              class="sidebarLink"
            >
              <img
                src="<?= $basePath ?>/logos/subscriptions.svg"
                class="sidebarIcon"
                alt="Subscriptions"
              />
              <span class="sidebarText">Subscriptions</span>
            </a>
          </li>

          <div class="sidebarDivider"></div>

          <li class="sidebarItem">
            <a 
              href="<?= $basePath ?>/index.php?route=library" 
              class="sidebarLink"
            >
              <img
                src="<?= $basePath ?>/logos/library.svg"
                class="sidebarIcon"
                alt="Library"
              />
              <span class="sidebarText">Library</span>
            </a>
          </li>

          <li class="sidebarItem">
            <a 
              href="<?= $basePath ?>/index.php?route=history" 
              class="sidebarLink"
            >
              <img
                src="<?= $basePath ?>/logos/history.svg"
                class="sidebarIcon"
                alt="History"
              />
              <span class="sidebarText">History</span>
            </a>
          </li>
          <?php endif; ?>
        </ul>
      </div>

      <div class="errorContainer">
        <h1 class="errorTitle">Error</h1>
        <!-- Display the error message based on the type -->
        <p><?php echo $message; ?></p>
        <button class="button" onclick="window.history.back()">Go back</button>
      </div>
    </div>
  </body>
  <script src="<?= $basePath ?>/js/main.js"></script>
</html>
