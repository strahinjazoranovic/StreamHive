<?php
require_once __DIR__ . '/../../app/helpers/videoDuration.php';
// Fallback mechanism to determine base path for assets and links
if (!isset($basePath)) {
  $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
  $viewsPosition = strpos($scriptName, '/views/');
  $projectBase = $viewsPosition !== false ? substr($scriptName, 0, $viewsPosition) : '';
  $basePath = $projectBase . '/public';
}
$isLoggedIn = $isLoggedIn ?? false;
$video = isset($video) && is_array($video) ? $video : [];
$sidebarVideos = isset($sidebarVideos) && is_array($sidebarVideos) ? $sidebarVideos : [];
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

      <div class="container containerVideos">
        <?php
          $videoId = (int)($video['id'] ?? 0);
          $fileName = (string)($video['filename'] ?? '');
          $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        ?>
        <div class="watchLayout">
          <section class="watchMainColumn">
            <div class="watchPlayerContainer" id="watchPlayerContainer">
              <?php if ($fileName !== ''): ?>
                <video
                  id="watchVideoPlayer"
                  class="watchVideoPlayer"
                  preload="metadata"
                  tabindex="0"
                >
                  <source src="<?= $basePath ?>/uploads/<?= rawurlencode($fileName) ?>" type="video/<?= htmlspecialchars($fileExtension, ENT_QUOTES, 'UTF-8') ?>" />
                </video>
                <button type="button" id="watchBigPlayButton" class="watchBigPlayButton" aria-label="Play video">▶</button>
                <div class="watchCustomControls" id="watchCustomControls">
                  <input type="range" id="watchSeekBar" class="watchSeekBar" min="0" max="100" step="0.1" value="0" aria-label="Seek video">
                  <div class="watchControlsRow">
                    <div class="watchControlsLeft">
                      <button type="button" id="watchPlayToggle" class="watchControlButton" aria-label="Play or pause">▶</button>
                      <span id="watchCurrentTime" class="watchTimeLabel">0:00</span>
                      <span class="watchTimeDivider">/</span>
                      <span id="watchDuration" class="watchTimeLabel">0:00</span>
                    </div>
                    <div class="watchControlsRight">
                      <button type="button" id="watchMuteToggle" class="watchControlButton" aria-label="Mute or unmute">
                        <img
                          id="watchMuteToggleIcon"
                          class="watchControlIcon"
                          src="<?= $basePath ?>/logos/volume.svg"
                          alt=""
                          aria-hidden="true"
                          height="18"
                          width="18"
                        >
                      </button>
                      <input type="range" id="watchVolumeBar" class="watchVolumeBar" min="0" max="1" step="0.01" value="1" aria-label="Volume">
                      <select id="watchSpeedSelect" class="watchSpeedSelect" aria-label="Playback speed">
                        <option value="0.5">0.5x</option>
                        <option value="0.75">0.75x</option>
                        <option value="1" selected>1x</option>
                        <option value="1.25">1.25x</option>
                        <option value="1.5">1.5x</option>
                        <option value="2">2x</option>
                      </select>
                      <button type="button" id="watchFullscreenToggle" class="watchControlButton" aria-label="Toggle fullscreen">⛶</button>
                    </div>
                  </div>
                </div>
              <?php else: ?>
                <p class="emptyState">Video file not found.</p>
              <?php endif; ?>
            </div>

            <section class="watchVideoDetailsCard">
              <h2 class="watchVideoTitle"><?= htmlspecialchars($video['title'] ?? 'Untitled', ENT_QUOTES, 'UTF-8') ?></h2>

              <div class="watchVideoDescriptionCard">
                <div class="watchUploaderActions">
                  <img
                    src="<?= $basePath ?>/logos/profile.svg"
                    class="watchUploaderProfileImage"
                    alt="Uploader profile image"
                    height="40"
                    width="40"
                  />
                  <div class="watchUploaderInfo">
                    <p class="watchUploaderName"><?= htmlspecialchars($video['username'] ?? 'Unknown user', ENT_QUOTES, 'UTF-8') ?></p>
                    <span class="watchUploaderSubscribers"><?= (int)($subscriptions['id'] ?? 0) ?> subscribers</span>
                  </div>
                  <button type="button" class="watchActionButtonSubscribe" data-watch-action="subscribe" data-video-id="<?= $videoId ?>">
                  <span>Subscribe </span>
                  </button>
                </div>
                <div class="watchActions">
                  <button type="button" class="watchActionButton" data-watch-action="like" data-video-id="<?= $videoId ?>">
                    <img src="<?= $basePath ?>/logos/like.svg" alt="Like" height="20" width="20">
                    <span><?= (int)($video['likes'] ?? 0) ?></span>
                  </button>
                  <button type="button" class="watchActionButton" data-watch-action="dislike" data-video-id="<?= $videoId ?>">
                    <img src="<?= $basePath ?>/logos/dislike.svg" alt="Dislike" height="20" width="20">
                    <span><?= (int)($video['dislikes'] ?? 0) ?></span>
                  </button>
                  <a
                  class="watchActionButton"
                  href="<?= $basePath ?>/uploads/<?= rawurlencode($fileName) ?>"
                  download="<?= htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8') ?>"
                >
                  Download
                </a> 
                </div>
              </div>

              <div class="watchMetaActionsRow">
                <div class="watchMetaText">
                  <span class="watchMetaItem"><?= (int)($video['views'] ?? 0) ?> views</span>
                  <span class="watchMetaDot">•</span>
                  <span class="watchMetaItem"><?= htmlspecialchars($formatTimeAgo($video['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                </div>   
                 <?php if (!empty($video['description'])): ?>
                  <p class="watchVideoDescription"><?= htmlspecialchars((string) $video['description'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php else: ?>
                  <p class="watchVideoDescription watchVideoDescriptionMuted">No description added yet.</p>
                <?php endif; ?>
              </div>

            </section>

            <section class="watchCommentsSection watchCommentsSectionUnderVideo">
              <h3 class="watchSidebarHeading">Comments</h3>

              <div class="watchCommentComposer">
                <input type="text" class="watchCommentInput" placeholder="Add a comment..." disabled>
              </div>

              <div class="watchCommentPlaceholderList">
                <!-- <article class="watchCommentPlaceholder">
                </article> -->
              </div>
            </section>
          </section>

          <aside class="watchSidebar">
            <section class="watchSuggestionSection">

              <?php if (count($sidebarVideos) === 0): ?>
                <p class="watchSidebarEmpty">No videos found.</p>
              <?php else: ?>
                <div class="watchSuggestionList">
                  <?php foreach (array_slice($sidebarVideos, 0, 18) as $sidebarVideo): ?>
                    <?php
                      $sidebarVideoId = (int)($sidebarVideo['id'] ?? 0);
                      $sidebarTitle = (string)($sidebarVideo['title'] ?? 'Untitled');
                      $sidebarThumbnailFileName = trim((string)($sidebarVideo['thumbnail'] ?? ''));
                      $sidebarThumbnailUrl = $sidebarThumbnailFileName !== ''
                        ? $basePath . '/uploads/thumbnails/' . rawurlencode($sidebarThumbnailFileName)
                        : $basePath . '/logos/streamHiveLogo.png';
                      $sidebarFormattedDuration = formatVideoDuration($sidebarVideo['duration_seconds'] ?? null);
                    ?>
                    <a class="watchSuggestionCard" href="<?= $basePath ?>/index.php?route=video&id=<?= $sidebarVideoId ?>">
                      <div class="watchSuggestionThumbnailWrap">
                        <img class="watchSuggestionThumbnail" src="<?= htmlspecialchars($sidebarThumbnailUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($sidebarTitle, ENT_QUOTES, 'UTF-8') ?>">
                        <span class="watchSuggestionDurationBadge"><?= htmlspecialchars($sidebarFormattedDuration, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="watchSuggestionPlay" aria-hidden="true">▶</span>
                      </div>
                      <div class="watchSuggestionInfo">
                        <h4><?= htmlspecialchars($sidebarTitle, ENT_QUOTES, 'UTF-8') ?></h4>
                        <p><?= htmlspecialchars((string)($sidebarVideo['username'] ?? 'Unknown user'), ENT_QUOTES, 'UTF-8') ?></p>
                        <p><?= (int)($sidebarVideo['views'] ?? 0) ?> views • <?= htmlspecialchars($formatTimeAgo($sidebarVideo['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                      </div>
                    </a>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </section>
          </aside>
        </div>
      </div>
    </div>
  </body>
  <script src="<?= $basePath ?>/js/main.js"></script>
</html>
