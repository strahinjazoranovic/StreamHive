<?php
require_once __DIR__ . '/../../app/helpers/videoDuration.php';
// Fallback mechanism to determine base path for assets and links
if (!isset($basePath)) {
  $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
  $viewsPosition = strpos($scriptName, '/views/');
  $projectBase = $viewsPosition !== false ? substr($scriptName, 0, $viewsPosition) : '';
  $basePath = $projectBase . '/public';
}
if (!isset($videos) || !is_array($videos)) {
  $videos = [];
}
$isLoggedIn = $isLoggedIn ?? false;
$formatTimeAgo = static function ($createdAtValue) {
  $createdAt = strtotime((string)($createdAtValue ?? ''));

  if (!$createdAt) {
    return 'Unknown date';
  }

  // Calculate the difference in seconds between the current time and the created_at time of the video
  $secondsAgo = time() - $createdAt;

  // If the created_at time is in the future, we can return "Just now" or a similar message
  if ($secondsAgo < 0) {
    return 'Just now';
  }

  // If the video was created more than a year ago, show the number of years
  $yearsAgo = (int) floor($secondsAgo / 31536000);
  if ($yearsAgo >= 1) {
    return $yearsAgo . ' year' . ($yearsAgo === 1 ? '' : 's') . ' ago';
  }

  // If the video was created more than a month ago, show the number of months
  $daysAgo = (int) floor($secondsAgo / 86400);
  if ($daysAgo >= 1) {
    return $daysAgo . ' day' . ($daysAgo === 1 ? '' : 's') . ' ago';
  }

  // If the video was created more than a hour ago, show the number of hours
  $hoursAgo = (int) floor($secondsAgo / 3600);
  if ($hoursAgo >= 1) {
    return $hoursAgo . ' hour' . ($hoursAgo === 1 ? '' : 's') . ' ago';
  }

  // If the video was created less than an hour ago, return "Less than 1 hour ago"
  return 'Less than 1 hour ago';
};

// Helpful for debugging to see the structure of the videos array
// print_r($videos);
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
    <title>StreamHive</title>
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
            <input type="text" class="searchInput" placeholder="Zoek" />
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

      <!-- If the user is not logged in show the container-guest which is centered unlinke the container-videos -->
      <div class="container <?= $isLoggedIn ? 'containerVideos' : 'containerGuest' ?>">
        <!-- If the user is not logged in show this message -->
        <?php if (!$isLoggedIn): ?>
          <div class="loginPrompt">
            <p class="loginMessage">Please log in or create an account to access videos.</p>
            <a href="<?= $basePath ?>/index.php?route=login">
              <button class="button">
                Log In
              </button>
            </a>
            <a href="<?= $basePath ?>/index.php?route=register">
              <button class="buttonSecondary">
                Create an account
              </button>
            </a>
          </div>
        <?php else: ?>
          <div class="content">
            <!-- If the user is logged in and there are no videos in the array, show an empty message -->
            <?php if (count($videos) === 0): ?>
              <p class="emptyState">No videos available.</p>
            <?php else: ?>
              <!-- But if there are videos, loop through every one of them and show a card for every video found in the array -->
              <?php foreach ($videos as $video): ?>
                <?php
                  $videoId = (int)($video['id'] ?? 0);
                  $thumbnailFileName = trim((string)($video['thumbnail'] ?? ''));
                  $thumbnailUrl = $thumbnailFileName !== ''
                    ? $basePath . '/uploads/thumbnails/' . rawurlencode($thumbnailFileName)
                    : $basePath . '/logos/streamHiveLogo.png';
                  $formattedDuration = formatVideoDuration($video['duration_seconds'] ?? null);
                ?>
                <article class="videoCard">
                  <?php if ($videoId > 0): ?>
                    <a class="videoThumbnailLink" href="<?= $basePath ?>/index.php?route=video&id=<?= $videoId ?>">
                      <img class="videoThumbnailImage" src="<?= htmlspecialchars($thumbnailUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($video['title'] ?? 'Video thumbnail', ENT_QUOTES, 'UTF-8') ?>">
                      <span class="videoDurationBadge"><?= htmlspecialchars($formattedDuration, ENT_QUOTES, 'UTF-8') ?></span>
                      <span class="videoPlayOverlay" aria-hidden="true">▶</span>
                    </a>
                  <?php else: ?>
                    <div class="videoThumbnailLink">
                      <img class="videoThumbnailImage" src="<?= htmlspecialchars($thumbnailUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($video['title'] ?? 'Video thumbnail', ENT_QUOTES, 'UTF-8') ?>">
                      <span class="videoDurationBadge"><?= htmlspecialchars($formattedDuration, ENT_QUOTES, 'UTF-8') ?></span>
                      <span class="videoPlayOverlay" aria-hidden="true">▶</span>
                    </div>
                  <?php endif; ?>

                  <h2 class="videoTitle"><?= htmlspecialchars($video['title'] ?? 'Untitled', ENT_QUOTES, 'UTF-8') ?></h2>
                  <p class="videoUser"><?= htmlspecialchars($video['username'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                  <div class="videoDiv">
                    <p class="video-meta"><?= (int)($video['views'] ?? 0) ?> views</p>
                    <p class="video-meta"><?= htmlspecialchars($formatTimeAgo($video['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                  </div>
                </article>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </body>
  <script src="<?= $basePath ?>/js/main.js"></script>
</html>
