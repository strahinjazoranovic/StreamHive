// Sidebar handling which uses localstorage to save the state of the sidebar
const hamburgerButton = document.querySelector(".sidebar-toggle");
const sidebar = document.querySelector(".sidebar");

// Profile and edit menu's
const profileMenuTrigger = document.querySelector(".profileMenu-trigger");
const profileMenu = document.querySelector(".profileMenu");

if (hamburgerButton && sidebar) {
  const params = new URLSearchParams(window.location.search);

  const isVideoPage = params.get("route") === "video";

  // If the user is on the video page the sidebar should always start in closed state
  if (isVideoPage) {
    sidebar.classList.remove("open");
    localStorage.setItem("sidebar", "closed");
  } else {
    // Other pages it should start in the state it was set to by user
    if (localStorage.getItem("sidebar") === "open") {
      sidebar.classList.add("open");
    } else {
      sidebar.classList.remove("open");
    }
  }

  hamburgerButton.addEventListener("click", () => {
    sidebar.classList.toggle("open");

    localStorage.setItem(
      "sidebar",
      sidebar.classList.contains("open") ? "open" : "closed",
    );
  });
}

// Profile Menu
if (profileMenuTrigger && profileMenu) {
  const closeProfileMenu = () => {
    profileMenu.classList.remove("open");
    profileMenuTrigger.setAttribute("aria-expanded", "false");
  };

  profileMenuTrigger.addEventListener("click", (event) => {
    event.stopPropagation();
    const isOpen = profileMenu.classList.toggle("open");
    profileMenuTrigger.setAttribute("aria-expanded", isOpen ? "true" : "false");
  });

  document.addEventListener("click", (event) => {
    if (
      !profileMenu.contains(event.target) &&
      !profileMenuTrigger.contains(event.target)
    ) {
      closeProfileMenu();
    }
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      closeProfileMenu();
    }
  });
}

// Admin page modal handling
const adminGrid = document.querySelector(".videoGridAdmin");

if (adminGrid) {
  const basePath = document.body.dataset.basePath || "";
  const overlay = document.querySelector(".overlay");
  const uploadModal = document.querySelector("#uploadVideoModal");
  const editModal = document.querySelector("#editVideoModal");
  const deleteModal = document.querySelector("#deleteVideoModal");
  const uploadOpenButton = document.querySelector(".openModal");
  const editButtons = document.querySelectorAll(".openEditModal");
  const closeModalButtons = document.querySelectorAll("[data-close-modal]");
  const openDeleteModalButton = document.querySelector(".openDeleteModal");

  const editVideoIdInput = document.querySelector("#editVideoId");
  const editVideoTitleInput = document.querySelector("#editVideoTitle");
  const editVideoDescriptionInput = document.querySelector(
    "#editVideoDescription",
  );
  const editVideoCategoryInput = document.querySelector("#editVideoCategory");
  const editVideoVisibilityInput = document.querySelector(
    "#editVideoVisibility",
  );
  const editThumbnailInput = document.querySelector("#editThumbnailToUpload");
  const editThumbnailPreview = document.querySelector("#editThumbnailPreview");

  const deleteVideoIdInput = document.querySelector("#deleteVideoId");
  const deleteVideoTitle = document.querySelector("#deleteVideoTitle");

  const allModals = [uploadModal, editModal, deleteModal].filter(Boolean);
  const getThumbnailUrl = (thumbnailFileName) => {
    if (thumbnailFileName !== "") {
      return `${basePath}/uploads/thumbnails/${encodeURIComponent(thumbnailFileName)}`;
    }

    return `${basePath}/logos/streamHiveLogo.png`;
  };

  const closeAllModals = () => {
    allModals.forEach((modalElement) => {
      modalElement.style.display = "none";
    });

    if (overlay) {
      overlay.style.display = "none";
    }
  };

  const openModal = (modalElement) => {
    if (!modalElement) {
      return;
    }

    closeAllModals();
    modalElement.style.display = "flex";

    if (overlay) {
      overlay.style.display = "flex";
    }
  };

  if (uploadOpenButton) {
    uploadOpenButton.addEventListener("click", () => {
      openModal(uploadModal);
    });
  }

  editButtons.forEach((editButton) => {
    editButton.addEventListener("click", () => {
      const videoId = editButton.dataset.videoId || "";
      const videoTitle = editButton.dataset.videoTitle || "";
      const videoDescription = editButton.dataset.videoDescription || "";
      const videoCategoryId = editButton.dataset.videoCategoryId || "";
      const videoVisibility = editButton.dataset.videoVisibility || "public";
      const videoThumbnail = editButton.dataset.videoThumbnail || "";

      if (editVideoIdInput) {
        editVideoIdInput.value = videoId;
      }

      if (editVideoTitleInput) {
        editVideoTitleInput.value = videoTitle;
      }

      if (editVideoDescriptionInput) {
        editVideoDescriptionInput.value = videoDescription;
      }

      if (editVideoCategoryInput) {
        editVideoCategoryInput.value = videoCategoryId;
      }

      if (editVideoVisibilityInput) {
        editVideoVisibilityInput.value = videoVisibility;
      }
      if (editThumbnailInput) {
        editThumbnailInput.value = "";
      }
      if (editThumbnailPreview) {
        editThumbnailPreview.dataset.currentThumbnail = videoThumbnail;
        editThumbnailPreview.src = getThumbnailUrl(videoThumbnail);
      }

      if (deleteVideoIdInput) {
        deleteVideoIdInput.value = videoId;
      }

      if (deleteVideoTitle) {
        deleteVideoTitle.textContent =
          videoTitle !== "" ? videoTitle : "this video";
      }

      openModal(editModal);
    });
  });

  if (editThumbnailInput && editThumbnailPreview) {
    editThumbnailInput.addEventListener("change", () => {
      const selectedFile =
        editThumbnailInput.files && editThumbnailInput.files[0];

      if (selectedFile) {
        editThumbnailPreview.src = URL.createObjectURL(selectedFile);
        return;
      }

      const currentThumbnail =
        editThumbnailPreview.dataset.currentThumbnail || "";
      editThumbnailPreview.src = getThumbnailUrl(currentThumbnail);
    });
  }

  if (openDeleteModalButton) {
    openDeleteModalButton.addEventListener("click", () => {
      const currentTitle = editVideoTitleInput
        ? editVideoTitleInput.value.trim()
        : "";
      const currentVideoId = editVideoIdInput ? editVideoIdInput.value : "";

      if (deleteVideoIdInput) {
        deleteVideoIdInput.value = currentVideoId;
      }

      if (deleteVideoTitle) {
        deleteVideoTitle.textContent =
          currentTitle !== "" ? currentTitle : "this video";
      }

      openModal(deleteModal);
    });
  }

  closeModalButtons.forEach((closeButton) => {
    closeButton.addEventListener("click", () => {
      closeAllModals();
    });
  });

  if (overlay) {
    overlay.addEventListener("click", () => {
      closeAllModals();
    });
  }

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      closeAllModals();
    }
  });
}

// Watch page custom player controls
const watchVideoPlayer = document.querySelector("#watchVideoPlayer");

if (watchVideoPlayer) {
  const watchBasePath = document.body.dataset.basePath || "";
  const watchVolumeIconSrc = `${watchBasePath}/logos/volume.svg`;
  const watchVolumeOffIconSrc = `${watchBasePath}/logos/volume-off.svg`;
  const watchPlayerContainer = document.querySelector("#watchPlayerContainer");
  const watchBigPlayButton = document.querySelector("#watchBigPlayButton");
  const watchPlayToggle = document.querySelector("#watchPlayToggle");
  const watchSeekBar = document.querySelector("#watchSeekBar");
  const watchCurrentTime = document.querySelector("#watchCurrentTime");
  const watchDuration = document.querySelector("#watchDuration");
  const watchMuteToggle = document.querySelector("#watchMuteToggle");
  const watchMuteToggleIcon = document.querySelector("#watchMuteToggleIcon");
  const watchVolumeBar = document.querySelector("#watchVolumeBar");
  const watchSpeedSelect = document.querySelector("#watchSpeedSelect");
  const watchFullscreenToggle = document.querySelector(
    "#watchFullscreenToggle",
  );
  const watchVideoReactionButtons = document.querySelectorAll(
    '.watchReactionButton[data-reaction-target="video"]',
  );
  const watchCommentReactionButtons = document.querySelectorAll(
    '.watchReactionButton[data-reaction-target="comment"]',
  );
  const watchSidebar = document.querySelector(".watchSidebar");
  let isSeeking = false;
  let isVideoReactionRequestInFlight = false;
  const commentReactionRequestsInFlight = new Set();

  const formatVideoTime = (secondsValue) => {
    const safeSeconds = Number.isFinite(secondsValue)
      ? Math.max(0, secondsValue)
      : 0;
    const totalSeconds = Math.floor(safeSeconds);
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;

    return `${minutes}:${String(seconds).padStart(2, "0")}`;
  };

  const updatePlayIcons = () => {
    const isPaused = watchVideoPlayer.paused;

    if (watchPlayToggle) {
      watchPlayToggle.textContent = isPaused ? "▶" : "❚❚";
    }

    if (watchBigPlayButton) {
      watchBigPlayButton.textContent = "▶";
    }

    if (watchPlayerContainer) {
      watchPlayerContainer.classList.toggle("is-playing", !isPaused);
    }
  };

  const setSeekBarProgress = (progressPercent) => {
    if (!watchSeekBar) {
      return;
    }

    const normalizedProgress = Number.isFinite(progressPercent)
      ? Math.max(0, Math.min(100, progressPercent))
      : 0;
    watchSeekBar.style.setProperty("--seek-progress", `${normalizedProgress}%`);
  };

  const updateSeekBar = () => {
    if (
      !watchSeekBar ||
      isSeeking ||
      !Number.isFinite(watchVideoPlayer.duration) ||
      watchVideoPlayer.duration <= 0
    ) {
      return;
    }

    const progressPercent =
      (watchVideoPlayer.currentTime / watchVideoPlayer.duration) * 100;
    watchSeekBar.value = String(progressPercent);
    setSeekBarProgress(progressPercent);
  };

  const togglePlay = () => {
    if (watchVideoPlayer.paused) {
      watchVideoPlayer.play();
    } else {
      watchVideoPlayer.pause();
    }
  };

  const updateMuteIcon = () => {
    if (!watchMuteToggleIcon) {
      return;
    }

    watchMuteToggleIcon.src = watchVideoPlayer.muted
      ? watchVolumeOffIconSrc
      : watchVolumeIconSrc;
  };

  const syncSidebarHeight = () => {
    if (!watchSidebar || !watchPlayerContainer) {
      return;
    }

    if (window.matchMedia("(max-width: 1200px)").matches) {
      watchSidebar.style.height = "";
      return;
    }

    const playerHeight = Math.round(
      watchPlayerContainer.getBoundingClientRect().height,
    );

    if (playerHeight > 0) {
      watchSidebar.style.height = `${playerHeight}px`;
    }
  };

  const applyReactionState = (
    reactionButtons,
    currentReaction,
    likesCount,
    dislikesCount,
  ) => {
    const normalizedLikes = Number.isFinite(likesCount) ? likesCount : 0;
    const normalizedDislikes = Number.isFinite(dislikesCount)
      ? dislikesCount
      : 0;
    reactionButtons.forEach((buttonElement) => {
      const actionType = buttonElement.dataset.watchAction || "";
      const countElement = buttonElement.querySelector(".watchReactionCount");
      const isActive =
        currentReaction !== null &&
        (currentReaction === "like" || currentReaction === "dislike") &&
        currentReaction === actionType;

      buttonElement.classList.toggle("isActive", isActive);

      if (!countElement) {
        return;
      }

      countElement.textContent =
        actionType === "like"
          ? String(normalizedLikes)
          : String(normalizedDislikes);
    });
  };

  watchVideoPlayer.addEventListener("loadedmetadata", () => {
    if (watchDuration) {
      watchDuration.textContent = formatVideoTime(watchVideoPlayer.duration);
    }
    setSeekBarProgress(0);
    syncSidebarHeight();
  });

  watchVideoPlayer.addEventListener("timeupdate", () => {
    if (watchCurrentTime) {
      watchCurrentTime.textContent = formatVideoTime(
        watchVideoPlayer.currentTime,
      );
    }
    updateSeekBar();
  });

  watchVideoPlayer.addEventListener("play", updatePlayIcons);
  watchVideoPlayer.addEventListener("pause", updatePlayIcons);
  watchVideoPlayer.addEventListener("click", togglePlay);

  if (watchBigPlayButton) {
    watchBigPlayButton.addEventListener("click", togglePlay);
  }

  if (watchPlayToggle) {
    watchPlayToggle.addEventListener("click", togglePlay);
  }

  if (watchSeekBar) {
    watchSeekBar.addEventListener("input", () => {
      isSeeking = true;
      setSeekBarProgress(Number(watchSeekBar.value));
    });

    watchSeekBar.addEventListener("change", () => {
      if (
        Number.isFinite(watchVideoPlayer.duration) &&
        watchVideoPlayer.duration > 0
      ) {
        const seekPercent = Number(watchSeekBar.value) / 100;
        watchVideoPlayer.currentTime = watchVideoPlayer.duration * seekPercent;
      }
      setSeekBarProgress(Number(watchSeekBar.value));

      isSeeking = false;
    });
  }

  if (watchVolumeBar) {
    watchVolumeBar.addEventListener("input", () => {
      const volumeValue = Math.max(
        0,
        Math.min(1, Number(watchVolumeBar.value)),
      );
      watchVideoPlayer.volume = volumeValue;
      watchVideoPlayer.muted = volumeValue === 0;
      updateMuteIcon();
    });
  }

  if (watchMuteToggle) {
    watchMuteToggle.addEventListener("click", () => {
      watchVideoPlayer.muted = !watchVideoPlayer.muted;
      updateMuteIcon();

      if (
        watchVolumeBar &&
        !watchVideoPlayer.muted &&
        Number(watchVolumeBar.value) === 0
      ) {
        watchVideoPlayer.volume = 1;
        watchVolumeBar.value = "1";
      }
    });
  }

  if (watchSpeedSelect) {
    watchSpeedSelect.addEventListener("change", () => {
      watchVideoPlayer.playbackRate = Number(watchSpeedSelect.value) || 1;
    });
  }

  if (watchFullscreenToggle && watchPlayerContainer) {
    watchFullscreenToggle.addEventListener("click", () => {
      if (!document.fullscreenElement) {
        watchPlayerContainer.requestFullscreen();
        return;
      }

      document.exitFullscreen();
    });
  }

  if (watchVideoReactionButtons.length > 0) {
    watchVideoReactionButtons.forEach((reactionButton) => {
      reactionButton.addEventListener("click", async () => {
        const reaction = reactionButton.dataset.watchAction || "";
        const videoId = Number(reactionButton.dataset.videoId || "0");

        if (
          isVideoReactionRequestInFlight ||
          videoId <= 0 ||
          (reaction !== "like" && reaction !== "dislike")
        ) {
          return;
        }
        isVideoReactionRequestInFlight = true;

        try {
          const response = await fetch(
            `${watchBasePath}/index.php?route=react-video`,
            {
              method: "POST",
              headers: {
                "Content-Type":
                  "application/x-www-form-urlencoded; charset=UTF-8",
                "X-Requested-With": "XMLHttpRequest",
              },
              body: new URLSearchParams({
                videoId: String(videoId),
                reaction,
              }).toString(),
            },
          );

          let payload = null;

          try {
            payload = await response.json();
          } catch (error) {
            payload = null;
          }

          if (response.status === 401 && payload && payload.redirect) {
            window.location.href = payload.redirect;
            return;
          }

          if (!response.ok || !payload || payload.success !== true) {
            return;
          }

          applyReactionState(
            watchVideoReactionButtons,
            payload.currentReaction ?? null,
            Number(payload.likes ?? 0),
            Number(payload.dislikes ?? 0),
          );
        } finally {
          isVideoReactionRequestInFlight = false;
        }
      });
    });
  }

  if (watchCommentReactionButtons.length > 0) {
    watchCommentReactionButtons.forEach((reactionButton) => {
      reactionButton.addEventListener("click", async () => {
        const reaction = reactionButton.dataset.watchAction || "";
        const commentId = Number(reactionButton.dataset.commentId || "0");

        if (
          commentId <= 0 ||
          commentReactionRequestsInFlight.has(commentId) ||
          (reaction !== "like" && reaction !== "dislike")
        ) {
          return;
        }

        commentReactionRequestsInFlight.add(commentId);

        try {
          const response = await fetch(
            `${watchBasePath}/index.php?route=react-comment`,
            {
              method: "POST",
              headers: {
                "Content-Type":
                  "application/x-www-form-urlencoded; charset=UTF-8",
                "X-Requested-With": "XMLHttpRequest",
              },
              body: new URLSearchParams({
                commentId: String(commentId),
                reaction,
              }).toString(),
            },
          );

          let payload = null;

          try {
            payload = await response.json();
          } catch (error) {
            payload = null;
          }

          if (response.status === 401 && payload && payload.redirect) {
            window.location.href = payload.redirect;
            return;
          }

          if (!response.ok || !payload || payload.success !== true) {
            return;
          }

          const currentCommentReactionButtons = document.querySelectorAll(
            `.watchReactionButton[data-reaction-target="comment"][data-comment-id="${commentId}"]`,
          );

          applyReactionState(
            currentCommentReactionButtons,
            payload.currentReaction ?? null,
            Number(payload.likes ?? 0),
            Number(payload.dislikes ?? 0),
          );
        } finally {
          commentReactionRequestsInFlight.delete(commentId);
        }
      });
    });
  }

  window.addEventListener("resize", syncSidebarHeight);

  if (typeof ResizeObserver !== "undefined" && watchPlayerContainer) {
    const playerResizeObserver = new ResizeObserver(() => {
      syncSidebarHeight();
    });
    playerResizeObserver.observe(watchPlayerContainer);
  }

  syncSidebarHeight();
  updateMuteIcon();
  setSeekBarProgress(Number(watchSeekBar ? watchSeekBar.value : "0"));
  updatePlayIcons();
}
