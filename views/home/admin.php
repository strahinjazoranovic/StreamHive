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

$comments = isset($comments) && is_array($comments) ? $comments : [];
$isLoggedIn = $isLoggedIn ?? false;
$uploadedVideos = isset($uploadedVideos) && is_array($uploadedVideos) ? $uploadedVideos : [];
$categories = isset($categories) && is_array($categories) ? $categories : [];
$visibilityOptions = isset($visibilityOptions) && is_array($visibilityOptions) ? $visibilityOptions : ['public', 'unlisted', 'private'];
$uploadMessage = (string)($uploadMessage ?? '');
$uploadMessageType = (string)($uploadMessageType ?? '');
?>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="<?= $basePath ?>/css/style.css" />
    <link rel="stylesheet" href="<?= $basePath ?>/css/admin.css" />
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
    <title>StreamHive - Channel content</title>
  </head>
  <body data-base-path="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>">
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
          <a href="<?= $basePath ?>/index.php?route=admin" class="sidebarLink">
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
            <a
              href="<?= $basePath ?>/index.php?route=logout"
              class="profileMenu-item"
              >Logout</a
            >
          </div>
        </div>
        <?php else: ?>
        <button class="iconButton">
          <a href="<?= $basePath ?>/index.php?route=login" class="sidebarLink">
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
              class="button"
            >
              Log In
            </button>
          </a>
        </div>
        <!-- If the user is logged in show this list -->
        <?php else: ?>
        <li class="sidebarItem">
          <a href="<?= $basePath ?>/index.php?route=home" class="sidebarLink">
            <img
              src="<?= $basePath ?>/logos/home.svg"
              class="sidebarIcon"
              alt="Home"
            />
            <span class="sidebarText">Home</span>
          </a>
        </li>

        <li class="sidebarItem">
          <a href="<?= $basePath ?>/index.php?route=subscriptions" class="sidebarLink">
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

    <div class="videoGridAdmin">
      <!-- Show succes or an error message -->
      <?php if ($uploadMessage !== ''): ?>
        <p class="uploadFeedback <?= $uploadMessageType === 'success' ? 'uploadFeedbackSuccess' : 'uploadFeedbackError' ?>">
          <?= htmlspecialchars($uploadMessage, ENT_QUOTES, 'UTF-8') ?>
        </p>
      <?php endif; ?>
      <div class="videoCardAdmin">
        <div class="videoCardHeader">
          <h2>Your uploaded videos</h2>
        </div>

        <?php if (count($uploadedVideos) === 0): ?>
          <p class="emptyStateAdmin">No videos uploaded yet.</p>
        <?php else: ?>

        <div class="adminVideoTableHeader">
          <div>Video</div>
          <div>Visibility</div>
          <div>Date</div>
          <div>Views</div>
          <div>Comments</div>
          <div>Likes</div>
          <div>Dislikes</div>
        </div>

      <div class="adminVideoList">
          <?php foreach ($uploadedVideos as $video): ?>
            <?php
              $videoVisibility = (string)($video['visibilty'] ?? 'public');
              $videoId = (int)($video['id'] ?? 0);
              $videoTitle = (string)($video['title'] ?? '');
              $videoDescription = (string)($video['description'] ?? '');
              $videoCategoryId = isset($video['category_id']) && $video['category_id'] !== null
                ? (string) $video['category_id']
                : '';
              $thumbnailFileName = trim((string)($video['thumbnail'] ?? ''));
              $thumbnailUrl = $thumbnailFileName !== ''
                ? $basePath . '/uploads/thumbnails/' . rawurlencode($thumbnailFileName)
                : $basePath . '/logos/streamHiveLogo.png';
              $formattedDuration = formatVideoDuration($video['duration_seconds'] ?? null);
              $commentCount = (int)($comments[$videoId] ?? 0);
              $videoWatchPath = $videoId > 0 ? buildVideoWatchPath($basePath, $video) : '';
            ?>
            <article class="adminVideoItem">
              <div class="adminVideoMain">

                <div class="adminVideoThumbnail">
                  <?php if ($videoVisibility !== 'private' && $videoId > 0): ?>
                    <a class="adminVideoThumbnailLink" href="<?= htmlspecialchars($videoWatchPath, ENT_QUOTES, 'UTF-8') ?>">
                      <img class="adminThumbnailImage" src="<?= htmlspecialchars($thumbnailUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($videoTitle !== '' ? $videoTitle : 'Video thumbnail', ENT_QUOTES, 'UTF-8') ?>">
                      <span class="adminDurationBadge"><?= htmlspecialchars($formattedDuration, ENT_QUOTES, 'UTF-8') ?></span>
                      <span class="adminPlayOverlay" aria-hidden="true">▶</span>
                    </a>
                  <?php else: ?>
                    <div class="adminVideoThumbnailPreview">
                      <img class="adminThumbnailImage" src="<?= htmlspecialchars($thumbnailUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($videoTitle !== '' ? $videoTitle : 'Video thumbnail', ENT_QUOTES, 'UTF-8') ?>">
                      <span class="adminDurationBadge"><?= htmlspecialchars($formattedDuration, ENT_QUOTES, 'UTF-8') ?></span>
                      <span class="adminPlayOverlay" aria-hidden="true">▶</span>
                    </div>
                  <?php endif; ?>
                </div>

                <div class="adminVideoInfo">
                  <h3>
                    <?= htmlspecialchars($videoTitle !== '' ? $videoTitle : 'Untitled', ENT_QUOTES, 'UTF-8') ?>
                  </h3>
                  <?php if (!empty($videoDescription)): ?>
                    <p>
                      <?= htmlspecialchars($videoDescription, ENT_QUOTES, 'UTF-8') ?>
                    </p>
                  <?php endif; ?>
                  <div class="adminVideoActions">
                    <button
                      type="button"
                      class="adminEditButton openEditModal"
                      data-video-id="<?= $videoId ?>"
                      data-video-title="<?= htmlspecialchars($videoTitle, ENT_QUOTES, 'UTF-8') ?>"
                      data-video-description="<?= htmlspecialchars($videoDescription, ENT_QUOTES, 'UTF-8') ?>"
                      data-video-category-id="<?= htmlspecialchars($videoCategoryId, ENT_QUOTES, 'UTF-8') ?>"
                      data-video-visibility="<?= htmlspecialchars($videoVisibility, ENT_QUOTES, 'UTF-8') ?>"
                      data-video-thumbnail="<?= htmlspecialchars($thumbnailFileName, ENT_QUOTES, 'UTF-8') ?>"
                    >
                      Edit
                    </button>
                    <?php if ($videoVisibility !== 'private' && $videoId > 0): ?>
                      <a
                        class="adminVideoShareLink"
                        href="<?= htmlspecialchars($videoWatchPath, ENT_QUOTES, 'UTF-8') ?>"
                      >
                        View video
                      </a>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
                <div class="adminVideoColumn">
                  <span class="columnTitle">Visibility</span>
                  <span><?= htmlspecialchars(ucfirst($videoVisibility), ENT_QUOTES, 'UTF-8') ?></span>
                </div>

                <div class="adminVideoColumn">
                  <span class="columnTitle">Date</span>
                  <span>
                    <?= htmlspecialchars(date('M j, Y', strtotime($video['created_at'] ?? 'now')), ENT_QUOTES, 'UTF-8') ?>
                  </span>
                </div>

                <div class="adminVideoColumn">
                  <span class="columnTitle">Views</span>
                  <span><?= (int)($video['views'] ?? 0) ?></span>
                </div>

                <div class="adminVideoColumn">
                  <span class="columnTitle">Comments</span>
                  <span><?= $commentCount ?></span>
                </div>

                <div class="adminVideoColumn">
                  <span class="columnTitle">Likes</span>
                  <span><?= (int)($video['likes'] ?? 0) ?></span>
                </div>
                
                <div class="adminVideoColumn">
                  <span class="columnTitle">Dislikes</span>
                  <span><?= (int)($video['dislikes'] ?? 0) ?></span>
                </div>
            </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="videoAdd openModal">
        <img src="<?= $basePath ?>/logos/plus.svg" alt="Upload icon" class="uploadIconAdmin img-hover" id="uploadIconAdmin" />
      </div>

      <div class="overlay"></div>

      <div class="videoCardAddAdmin modal" id="uploadVideoModal">
        <div class="modalHeader">
          <h1>Upload an new video</h1>
          <img class="closeModal" data-close-modal src="<?= $basePath ?>/logos/close.svg" alt="Close Icon">
        </div>

        <form action="<?= $basePath ?>/upload.php" method="post" enctype="multipart/form-data">
          <div id="uploadVideoFile">
            <input type="file" name="fileToUpload" id="fileToUpload" required>
          </div>
          <div id="uploadVideoForm">
          <label for="thumbnailToUpload">Thumbnail</label>
          <input type="file" name="thumbnailToUpload" id="thumbnailToUpload" accept="image/*" required>
          <label for="videoTitle">Title</label>
          <input type="text" name="videoTitle" id="videoTitle" placeholder="Enter video title" required>
          <label for="videoDescription">Description</label>
          <input type="text" name="videoDescription" id="videoDescription" placeholder="Enter video description">
          <label for="videoCategory">Category</label>
          <select name="videoCategory" id="videoCategory">
            <option value="">Select category</option>
            <!-- Loop through every category found in the database -->
            <?php foreach ($categories as $category): ?>
              <option value="<?= (int)($category['id'] ?? 0) ?>">
                <?= htmlspecialchars($category['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
              </option>
            <?php endforeach; ?>
          </select>
          <label for="videoVisibility">Visibility</label>
          <select name="videoVisibility" id="videoVisibility">
            <!-- Loop through every visibility enum value from the database -->
            <?php foreach ($visibilityOptions as $visibilityOption): ?>
              <option value="<?= htmlspecialchars($visibilityOption, ENT_QUOTES, 'UTF-8') ?>" <?= $visibilityOption === 'public' ? 'selected' : '' ?>>
                <?= htmlspecialchars(ucfirst($visibilityOption), ENT_QUOTES, 'UTF-8') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <input type="submit" value="Submit video" id="fileToSubmit" name="submit">
        </form>
      </div>

      <div class="videoCardEditAdmin modal" id="editVideoModal">
        <div class="modalHeader">
          <h1>Edit video</h1>
          <img class="closeModal" data-close-modal src="<?= $basePath ?>/logos/close.svg" alt="Close Icon">
        </div>

        <form action="<?= $basePath ?>/index.php?route=manage-video" method="post" enctype="multipart/form-data">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="videoId" id="editVideoId">

          <div class="editModalLayout">
            <div class="modalForm">
              <label for="editVideoTitle">Title</label>
              <input type="text" name="videoTitle" id="editVideoTitle" placeholder="Enter video title" required>

              <label for="editVideoDescription">Description</label>
              <input type="text" name="videoDescription" id="editVideoDescription" placeholder="Enter video description">

              <label for="editVideoCategory">Category</label>
              <select name="videoCategory" id="editVideoCategory">
                <option value="">Select category</option>
                <?php foreach ($categories as $category): ?>
                  <option value="<?= (int)($category['id'] ?? 0) ?>">
                    <?= htmlspecialchars($category['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                  </option>
                <?php endforeach; ?>
              </select>

              <label for="editVideoVisibility">Visibility</label>
              <select name="videoVisibility" id="editVideoVisibility">
                <?php foreach ($visibilityOptions as $visibilityOption): ?>
                  <option value="<?= htmlspecialchars($visibilityOption, ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars(ucfirst($visibilityOption), ENT_QUOTES, 'UTF-8') ?>
                  </option>
                <?php endforeach; ?>
              </select>

              <label for="editThumbnailToUpload">Replace thumbnail</label>
              <input type="file" name="thumbnailToUpload" id="editThumbnailToUpload" accept="image/*">
              <p class="thumbnailFieldHint">Leave empty to keep the current thumbnail.</p>
            </div>

            <aside class="editThumbnailPanel">
              <h2>Thumbnail preview</h2>
              <div class="editThumbnailPreviewFrame">
                <img id="editThumbnailPreview" class="editThumbnailPreview" src="<?= $basePath ?>/logos/streamHiveLogo.png" alt="Current thumbnail preview">
              </div>
              <div class="modalActions">
                <button type="button" class="button openDeleteModal">Delete video</button>
                <button type="submit" class="buttonSecondary">Save changes</button>
              </div>
            </aside>
          </div>

          
        </form>
      </div>

      <div class="videoDeleteConfirm modal" id="deleteVideoModal">
        <div class="modalHeader">
          <h1>Delete video</h1>
          <img class="closeModal" data-close-modal src="<?= $basePath ?>/logos/close.svg" alt="Close Icon">
        </div>

        <p class="deleteMessage">
          Are you sure you want to delete <strong id="deleteVideoTitle"></strong>? This action cannot be undone.
        </p>

        <form action="<?= $basePath ?>/index.php?route=manage-video" method="post">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="videoId" id="deleteVideoId">

          <div class="modalActions">
            <button type="button" class="button" data-close-modal>Cancel</button>
            <button type="submit" class="buttonSecondary deleteButton">Delete permanently</button>
          </div>
        </form>
      </div>
    </div>
  </body>
  <script src="<?= $basePath ?>/js/main.js"></script>
</html>
