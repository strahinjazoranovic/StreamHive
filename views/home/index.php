<?php
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
$formatTimeAgo = static function ($createdAtValue) {
  $createdAt = strtotime((string)($createdAtValue ?? ''));

  if (!$createdAt) {
    return 'Unknown date';
  }

  $secondsAgo = time() - $createdAt;

  if ($secondsAgo < 0) {
    return 'Just now';
  }

  $yearsAgo = (int) floor($secondsAgo / 31536000);
  if ($yearsAgo >= 1) {
    return $yearsAgo . ' year' . ($yearsAgo === 1 ? '' : 's') . ' ago';
  }

  $daysAgo = (int) floor($secondsAgo / 86400);
  if ($daysAgo >= 1) {
    return $daysAgo . ' day' . ($daysAgo === 1 ? '' : 's') . ' ago';
  }

  $hoursAgo = (int) floor($secondsAgo / 3600);
  if ($hoursAgo >= 1) {
    return $hoursAgo . ' hour' . ($hoursAgo === 1 ? '' : 's') . ' ago';
  }

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
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
      rel="stylesheet"
    />
    <link
      rel="icon"
      type="image/svg+xml"
      href="<?= $basePath ?>/logos/home.svg"
    />
    <title>StreamHive</title>
  </head>
  <body data-base-path="<?= $basePath ?>">
    <div>
      <nav class="navbar">
        <div class="navbar-left">
          <button class="icon-button">
            <img
              src="<?= $basePath ?>/logos/hamburgermenu.svg"
              class="img-hover sidebar-toggle"
              alt="sidebar button"
              draggable="false"
            />
          </button>

          <h1 class="text-header">
            Stream<span class="text-accent">Hive</span>
          </h1>
        </div>

        <div class="navbar-center">
          <div class="search-bar">
            <input type="text" class="search-input" placeholder="Zoek" />
            <button class="icon-button">
              <img
                src="<?= $basePath ?>/logos/search.svg"
                class="img-hover"
                alt="Search"
                draggable="false"
              />
            </button>
          </div>
        </div>

        <div class="navbar-right">
          <button class="icon-button">
            <a
              href="<?= $basePath ?>/index.php?route=admin"
              class="sidebar-link"
            >
            <img
              src="<?= $basePath ?>/logos/upload.svg"
              class="img-hover"
              alt="Upload button"
              draggable="false"
            />
            </a>
          </button>

          <button class="icon-button">
            <a
              href="<?= $basePath ?>/index.php?route=login"
              class="sidebar-link"
            >
            <img
              src="<?= $basePath ?>/logos/profile.svg"
              class="img-hover"
              alt="Profile button"
              draggable="false"
            />
            </a>
          </button>
        </div>
      </nav>

      <div class="sidebar">
        <ul class="sidebar-menu">
          <li class="sidebar-item">
            <a
              href="<?= $basePath ?>/index.php?route=home"
              class="sidebar-link"
            >
              <img
                src="<?= $basePath ?>/logos/home.svg"
                class="sidebar-icon"
                alt="Home"
              />
              <span class="sidebar-text">Home</span>
            </a>
          </li>

          <li class="sidebar-item">
            <a href="#" class="sidebar-link">
              <img
                src="<?= $basePath ?>/logos/trending.svg"
                class="sidebar-icon"
                alt="Trending"
              />
              <span class="sidebar-text">Trending</span>
            </a>
          </li>

          <li class="sidebar-item">
            <a href="#" class="sidebar-link">
              <img
                src="<?= $basePath ?>/logos/subscriptions.svg"
                class="sidebar-icon"
                alt="Subscriptions"
              />
              <span class="sidebar-text">Subscriptions</span>
            </a>
          </li>

          <div class="sidebar-divider"></div>

          <li class="sidebar-item">
            <a href="#" class="sidebar-link">
              <img
                src="<?= $basePath ?>/logos/library.svg"
                class="sidebar-icon"
                alt="Library"
              />
              <span class="sidebar-text">Library</span>
            </a>
          </li>

          <li class="sidebar-item">
            <a href="#" class="sidebar-link">
              <img
                src="<?= $basePath ?>/logos/history.svg"
                class="sidebar-icon"
                alt="History"
              />
              <span class="sidebar-text">History</span>
            </a>
          </li>
        </ul>
      </div>

      <div class="container">
        <div class="content">
          <!-- If there are no videos in the array, show an empty message -->
          <?php if (count($videos) === 0): ?>
            <p class="empty-state">No videos available yet.</p>
          <?php else: ?>
            <!-- But if there are videos, loop through every one of them and show a card for every video found in the array -->
            <?php foreach ($videos as $video): ?>
              <article class="video-card">
                <!-- <a href="<?= $basePath ?>/index.php?route=video&id=<?= (int)($video['id'] ?? 0) ?>">
                  <img
                    src="<?= htmlspecialchars($video['thumbnail_path'] ?? $basePath . '/images/default_thumbnail.jpg', ENT_QUOTES, 'UTF-8') ?>"
                    alt="Video thumbnail"
                    class="video-thumbnail"
                  />
                </a> -->
                <h2 class="video-title"><?= htmlspecialchars($video['title'] ?? 'Untitled', ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="video-user"><?= htmlspecialchars($video['username'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                <div class="video-div">
                  <p class="video-meta"><?= (int)($video['views'] ?? 0) ?> views</p>
                  <p class="video-meta"><?= htmlspecialchars($formatTimeAgo($video['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
              </article>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </body>
  <script src="<?= $basePath ?>/js/main.js"></script>
</html>
