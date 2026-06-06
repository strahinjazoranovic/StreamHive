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

// Subscription status
$userSubscribe = (bool)($userSubscribe ?? false);
$subscriberCount = (int)($subscriberCount ?? 0);
$canSubscribe = (bool)($canSubscribe ?? false);

// Comments
$comments = isset($comments) && is_array($comments) ? $comments : [];
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$editingCommentId = (int)($_GET['editComment'] ?? 0);

// Names and initials
$channelUser = isset($channelUser) && is_array($channelUser) ? $channelUser : [];
$channelName = trim((string)($channelUser['username'] ?? 'StreamHive Creator'));
$channelInitial = strtoupper(substr($channelName !== '' ? $channelName : '?', 0, 1));

// Video's
$video = isset($video) && is_array($video) ? $video : [];
$sidebarVideos = isset($sidebarVideos) && is_array($sidebarVideos) ? $sidebarVideos : [];

// Watch later status
$watchLaterVideos = isset($watchLaterVideos) && is_array($watchLaterVideos) ? $watchLaterVideos : [];
$isVideoInWatchLater = false;
$currentVideoId = (int)($video['id'] ?? 0);

// Check if the current video is in the user's watch later list
foreach ($watchLaterVideos as $watchLaterVideo) {
  if ((int)($watchLaterVideo['video_id'] ?? 0) === $currentVideoId) {
    $isVideoInWatchLater = true;
    break;
  }
}

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
     <title>StreamHive - <?= htmlspecialchars($video['title'] ?? 'Video', ENT_QUOTES, 'UTF-8') ?? 'Video' ?></title>
  </head>
  <body data-base-path="<?= $basePath ?>" class="bodyVideo">
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

      <div class="container containerVideos">
        <?php
          $videoId = (int)($video['id'] ?? 0);
          $fileName = (string)($video['filename'] ?? '');
          $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
          $videoCategoryName = trim((string)($video['category_name'] ?? ''));
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
                  <div class="channelAvatarVideo" aria-hidden="true">
                    <span class="channelAvatarTextSmall"><?= htmlspecialchars($channelInitial, ENT_QUOTES, 'UTF-8') ?></span>
                  </div>
                  <div class="watchUploaderInfo">
                    <a href="<?= $basePath ?>/index.php?route=user&id=<?= (int)($video['user_id'] ?? 0) ?>" class="watchUploaderNameLink">
                      <p class="watchUploaderName">
                        <?= htmlspecialchars($video['username'] ?? 'Unknown user', ENT_QUOTES, 'UTF-8') ?>
                      </p>
                    </a>
                    <span class="watchUploaderSubscribers" data-channel-id="<?= (int)($video['user_id'] ?? 0) ?>"><?= $subscriberCount ?> subscriber<?= $subscriberCount === 1 ? '' : 's' ?></span>
                  </div>
                  <?php if ($canSubscribe): ?>
                    <button
                      type="button"
                      class="watchActionButtonSubscribe<?= $userSubscribe ? ' isActive' : '' ?>"
                      data-watch-action="subscribe"
                      data-channel-id="<?= (int)($video['user_id'] ?? 0) ?>"
                      data-subscribed-label="Subscribed"
                      data-unsubscribed-label="Subscribe"
                    >
                      <?= $userSubscribe ? 'Subscribed' : 'Subscribe' ?>
                    </button>
                  <?php endif; ?>
                </div>
                <div class="watchActions">
                  <button type="button" class="watchActionButton watchReactionButton<?= $userReactionType === true ? ' isActive' : '' ?>" data-watch-action="like" data-video-id="<?= $videoId ?>" data-reaction-target="video">
                    <img src="<?= $basePath ?>/logos/like.svg" alt="Like" height="20" width="20">
                    <span class="watchReactionCount"><?= (int)($video['likes'] ?? 0) ?></span>
                  </button>
                  <button type="button" class="watchActionButton watchReactionButton<?= $userReactionType === false ? ' isActive' : '' ?>" data-watch-action="dislike" data-video-id="<?= $videoId ?>" data-reaction-target="video">
                    <img src="<?= $basePath ?>/logos/dislike.svg" alt="Dislike" height="20" width="20">
                    <span class="watchReactionCount"><?= (int)($video['dislikes'] ?? 0) ?></span>
                  </button>
                  <button 
                    type="button" 
                    class="watchActionButton watchLaterButton<?= $isVideoInWatchLater ? ' isActive' : '' ?>" 
                    data-watch-action="watch-later"
                    data-video-id="<?= $videoId ?>"
                    data-user-id="<?= (int)($_SESSION['user_id'] ?? 0) ?>"
                    >
                    <?= $isVideoInWatchLater ? 'Saved' : 'Watch later' ?>
                  </button>
                  <a
                    class="watchActionButtonDownload"
                    href="<?= $basePath ?>/uploads/<?= rawurlencode($fileName) ?>"
                    download="<?= htmlspecialchars($video['title'] ?? 'StreamHive Video', ENT_QUOTES, 'UTF-8') ?>"
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
                  <?php if ($videoCategoryName !== ''): ?>
                    <span class="watchMetaDot">•</span>
                    <span class="watchMetaItem">#<?= htmlspecialchars($videoCategoryName, ENT_QUOTES, 'UTF-8') ?></span>
                  <?php endif; ?>
                </div>   
                 <?php if (!empty($video['description'])): ?>
                  <p class="watchVideoDescription"><?= htmlspecialchars((string) $video['description'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php else: ?>
                  <p class="watchVideoDescription watchVideoDescriptionMuted">No description added yet.</p>
                <?php endif; ?>
              </div>

            </section>

            <section class="watchCommentsSection watchCommentsSectionUnderVideo">
              <form action="<?= $basePath ?>/index.php?route=manage-comment" method="POST">
                <h3 class="watchSidebarHeading">
                  <!-- Show comments count with proper text -->
                  <?php 
                    $count = count($comments);
                    echo $count . ' ' . ($count === 1 ? 'Comment' : 'Comments');
                  ?>
                </h3>
                <div class="watchCommentDiv">
                  <input type="text" name="comment" class="watchCommentInput" placeholder="Add a comment">
                  <!-- Button -->
                  <input type="submit" value="Comment" id="commentToSubmit" name="submit">   
                </div>  
                <!-- hidden inputs to pass videoId and userId -->
                <input type="hidden" name="videoId" value="<?= $videoId ?>">
                <input type="hidden" name="action" value="create">
              </form>

              <div class="watchCommentList">
                <?php if (count($comments) === 0): ?>
                  <p class="emptyState">No comments found for this video.</p>
                <?php else: ?>
                  <?php foreach ($comments as $comment): ?>
                    <?php
                      $commentId = (int)($comment['id'] ?? 0);
                      $commenterId = (int)($comment['user_id'] ?? 0);
                      $commentContent = (string)($comment['content'] ?? '');
                      $commentDate = (string)($comment['created_at'] ?? 'Some time ago');
                      $isEditedComment = preg_match('/\s*\(edited\)$/i', $commentContent) === 1;
                      $commentTextWithoutEditedSuffix = preg_replace('/\s*\(edited\)$/i', '', $commentContent);
                      $commentTextWithoutEditedSuffix = trim((string)($commentTextWithoutEditedSuffix ?? $commentContent));
                      $commentUserReactionType = $comment['current_user_reaction_type'] ?? null;
                      $commentUserReactionType = $commentUserReactionType === null ? null : (int)$commentUserReactionType;
                      $isCommentOwner = $isLoggedIn && $currentUserId > 0 && $currentUserId === $commenterId;
                      $isEditingThisComment = $isCommentOwner && $editingCommentId === $commentId;
                    ?>
                    <article class="watchComment" id="comment-<?= $commentId ?>">
                      <div class="watchCommentContainer">
                        <div class="channelAvatarVideo" aria-hidden="true">
                          <span class="channelAvatarTextSmall"><?= htmlspecialchars(strtoupper(substr($comment['username'] ?? '?', 0, 1)), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div>
                          <a href="<?= $basePath ?>/index.php?route=user&id=<?= $commenterId ?>" class="watchUploaderNameLink">
                            <h3 class="commentUserTitle">
                              <?= htmlspecialchars($comment['username'] ?? 'StreamHive commentor', ENT_QUOTES, 'UTF-8') ?>
                              <span class="commentDate"><?= htmlspecialchars($formatTimeAgo($commentDate), ENT_QUOTES, 'UTF-8') ?></span>
                            </h3>
                          </a>

                          <!-- If the comment is being edited show this form -->
                          <?php if ($isEditingThisComment): ?>
                            <form class="commentEditForm" action="<?= $basePath ?>/index.php?route=manage-comment" method="POST">
                              <input type="hidden" name="action" value="edit">
                              <input type="hidden" name="videoId" value="<?= $videoId ?>">
                              <input type="hidden" name="commentId" value="<?= $commentId ?>">
                              <input
                                type="text"
                                name="comment"
                                class="watchCommentInput commentEditInput"
                                value="<?= htmlspecialchars($commentTextWithoutEditedSuffix, ENT_QUOTES, 'UTF-8') ?>"
                                required
                              >
                              <div class="commentEditActions">
                                <button type="submit" class="commentActionButton commentActionButtonPrimary">Save</button>
                                <a class="commentActionButton" href="<?= $basePath ?>/index.php?route=video&id=<?= $videoId ?>#comment-<?= $commentId ?>">Cancel</a>
                              </div>
                            </form>
                          <!-- But if the comment isn't being edited show this UI -->
                          <?php else: ?>
                            <p class="commentText">
                              <?= htmlspecialchars($commentTextWithoutEditedSuffix !== '' ? $commentTextWithoutEditedSuffix : 'No comment text', ENT_QUOTES, 'UTF-8') ?>
                              <?php if ($isEditedComment): ?>
                                <span class="commentEditedTag"> (edited)</span>
                              <?php endif; ?>
                            </p>
                            <div class="watchActions">
                              <button type="button" class="watchActionButton watchReactionButton<?= $commentUserReactionType === 1 ? ' isActive' : '' ?>" data-watch-action="like" data-comment-id="<?= $commentId ?>" data-reaction-target="comment">
                                <img src="<?= $basePath ?>/logos/like.svg" alt="Like" height="20" width="20">
                                <span class="watchReactionCount"><?= (int)($comment['likes'] ?? 0) ?></span>
                              </button>
                              <button type="button" class="watchActionButton watchReactionButton<?= $commentUserReactionType === 0 ? ' isActive' : '' ?>" data-watch-action="dislike" data-comment-id="<?= $commentId ?>" data-reaction-target="comment">
                                <img src="<?= $basePath ?>/logos/dislike.svg" alt="Dislike" height="20" width="20">
                                <span class="watchReactionCount"><?= (int)($comment['dislikes'] ?? 0) ?></span>
                              </button>
                            </div>
                          <?php endif; ?>
                        </div>
                      </div>

                      <?php if ($isCommentOwner && !$isEditingThisComment): ?>
                        <div class="commentManageActions">
                          <a class="commentActionButton" href="<?= $basePath ?>/index.php?route=video&id=<?= $videoId ?>&editComment=<?= $commentId ?>#comment-<?= $commentId ?>">Edit</a>
                          <form class="commentDeleteForm" action="<?= $basePath ?>/index.php?route=manage-comment" method="POST">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="videoId" value="<?= $videoId ?>">
                            <input type="hidden" name="commentId" value="<?= $commentId ?>">
                            <button type="submit" class="commentActionButton commentActionDelete">Delete</button>
                          </form> 
                        </div>
                      <?php endif; ?>
                    </article>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </section>
          </section>

          <aside class="watchSidebar">
            <section class="watchSuggestionSection">

              <?php if (count($sidebarVideos) === 0): ?>
                <p class="watchSidebarEmpty">No suggested videos found.</p>
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
