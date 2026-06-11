<?php
require_once __DIR__ . '/../../app/helpers/videoDuration.php';
require_once __DIR__ . '/../../app/helpers/videoAccess.php';
// Fallback mechanism to determine base path for assets and links
if (!isset($basePath)) {
  $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
  $viewsPosition = strpos($scriptName, '/views/');
  $projectBase = $viewsPosition !== false ? substr($scriptName, 0, $viewsPosition) : '';
  $basePath = $projectBase . '/public';
}
// Ensure $videos is defined and is an array to prevent errors in the view
if (!isset($videos) || !is_array($videos)) {
  $videos = [];
}
// Check if user is logged in
$isLoggedIn = $isLoggedIn ?? false;

// Channel info
$channelUser = isset($channelUser) && is_array($channelUser) ? $channelUser : [];
$channelName = trim((string)($channelUser['username'] ?? 'StreamHive Creator'));
$channelUserId = (int)($channelUser['id'] ?? 0);
$channelInitial = strtoupper(substr($channelName !== '' ? $channelName : '?', 0, 1));
$handleBase = preg_replace('/[^a-zA-Z0-9._-]/', '', str_replace(' ', '', $channelName));
$channelHandle = '@' . ($handleBase !== '' ? strtolower($handleBase) : 'streamhive_creator');
$channelCreatedAt = strtotime((string)($channelUser['created_at'] ?? ''));
$joinedLabel = $channelCreatedAt ? date('M Y', $channelCreatedAt) : null;

// Subscription status
$userSubscribe = (bool)($userSubscribe ?? false);
$subscriberCount = (int)($subscriberCount ?? 0);
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$canSubscribe = $channelUserId > 0 && $currentUserId !== $channelUserId;

// Video count
$videoCount = count($videos);

// Format time with the createdAtValue
$formatTimeAgo = static function ($createdAtValue) {
  $createdAt = strtotime((string)($createdAtValue ?? ''));

  if (!$createdAt) {
    return 'Unknown date';
  }

  $secondsAgo = time() - $createdAt;

  if ($secondsAgo < 0) {
    return 'Just now';
  }

  // Years
  $yearsAgo = (int) floor($secondsAgo / 31536000);
  if ($yearsAgo >= 1) {
    return $yearsAgo . ' year' . ($yearsAgo === 1 ? '' : 's') . ' ago';
  }

  // Days
  $daysAgo = (int) floor($secondsAgo / 86400);
  if ($daysAgo >= 1) {
    return $daysAgo . ' day' . ($daysAgo === 1 ? '' : 's') . ' ago';
  }

  // Hours
  $hoursAgo = (int) floor($secondsAgo / 3600);
  if ($hoursAgo >= 1) {
    return $hoursAgo . ' hour' . ($hoursAgo === 1 ? '' : 's') . ' ago';
  }

  // Minutes
  $minutesAgo = (int) floor($secondsAgo / 60);
  if ($minutesAgo >= 1) {
    return $minutesAgo . ' minute' . ($minutesAgo === 1 ? '' : 's') . ' ago';
  }

  // Seconds
  return $secondsAgo <= 0
    ? 'Just now'
    : $secondsAgo . ' second' . ($secondsAgo === 1 ? '' : 's') . ' ago';
};

// Helpful for debugging to see the structure of the videos array
// print_r($videos);
?>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="<?= $basePath ?>/css/style.css" />
    <link rel="stylesheet" href="<?= $basePath ?>/css/profile.css" />
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
    <title>StreamHive - <?= htmlspecialchars($channelName, ENT_QUOTES, 'UTF-8') ?? 'Profile' ?></title>
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
      <div class="container profileContainer">
        <section class="channelPage">
          <div class="channelBanner">
            <div class="channelBannerShade"></div>
          </div>

          <header class="channelHeader">
            <div class="channelAvatar" aria-hidden="true">
              <?= htmlspecialchars($channelInitial, ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="channelMeta">
              <h1 class="channelTitle"><?= htmlspecialchars($channelName, ENT_QUOTES, 'UTF-8') ?></h1>
              <p class="channelStats">
                <span class="channelHandle"><?= htmlspecialchars($channelHandle, ENT_QUOTES, 'UTF-8') ?></span>
                <span class="channelStatsDot">•</span>
                <span class="watchUploaderSubscribers" data-channel-id="<?= $channelUserId ?>"><?= $subscriberCount ?> subscriber<?= $subscriberCount === 1 ? '' : 's' ?></span>
                <span class="channelStatsDot">•</span>
                <span><?= $videoCount ?> Video<?= $videoCount === 1 ? '' : 's' ?></span>
                <?php if ($joinedLabel !== null): ?>
                  <span class="channelStatsDot">•</span>
                  <span>Joined <?= htmlspecialchars($joinedLabel, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
                
              </p>
              <?php if ($canSubscribe): ?>
                <button
                  type="button"
                  class="channelSubscribeButton<?= $userSubscribe ? ' isActive' : '' ?>"
                  data-watch-action="subscribe"
                  data-channel-id="<?= $channelUserId ?>"
                  data-subscribed-label="Subscribed"
                  data-unsubscribed-label="Subscribe"
                >
                  <?= $userSubscribe ? 'Subscribed' : 'Subscribe' ?>
                </button>
              <?php endif; ?>
            </div>
          </header>

          <nav class="channelTabs" aria-label="Channel sections">
            <span class="channelTab isActive">Videos</span>
          </nav>

          <?php if ($videoCount === 0): ?>
            <p class="profileEmptyState">This channel has no videos.</p>
          <?php else: ?>
            <div class="profileVideoGrid">
              <?php foreach ($videos as $video): ?>
                <?php
                  $videoId = (int)($video['id'] ?? 0);
                  $videoWatchPath = $videoId > 0 ? buildVideoWatchPath($basePath, $video) : $basePath . '/index.php?route=home';
                  $thumbnailFileName = trim((string)($video['thumbnail'] ?? ''));
                  $thumbnailUrl = $thumbnailFileName !== ''
                    ? $basePath . '/uploads/thumbnails/' . rawurlencode($thumbnailFileName)
                    : $basePath . '/logos/streamHiveLogo.png';
                  $formattedDuration = formatVideoDuration($video['duration_seconds'] ?? null);
                ?>
                <a class="profileVideoCardLink" href="<?= htmlspecialchars($videoWatchPath, ENT_QUOTES, 'UTF-8') ?>">
                  <article class="profileVideoCard">
                    <div class="profileVideoThumbnailWrap">
                      <img
                        class="profileVideoThumbnailImage"
                        src="<?= htmlspecialchars($thumbnailUrl, ENT_QUOTES, 'UTF-8') ?>"
                        alt="<?= htmlspecialchars($video['title'] ?? 'Video thumbnail', ENT_QUOTES, 'UTF-8') ?>"
                      >
                      <span class="profileVideoDurationBadge"><?= htmlspecialchars($formattedDuration, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <h2 class="profileVideoTitle">
                      <?= htmlspecialchars($video['title'] ?? 'Untitled', ENT_QUOTES, 'UTF-8') ?>
                    </h2>
                    <div class="profileVideoMetaRow">
                      <p class="profileVideoMeta"><?= (int)($video['views'] ?? 0) ?> views</p>
                      <span class="profileVideoMetaDot">•</span>
                      <p class="profileVideoMeta"><?= htmlspecialchars($formatTimeAgo($video['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                  </article>
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>
      </div>
    </div>
  </body>
  <script src="<?= $basePath ?>/js/main.js"></script>
</html>