// Sidebar handling which uses localstorage to save the state of the sidebar(open or closed)
const hamburgerButton = document.querySelector(".sidebar-toggle");
const sidebar = document.querySelector(".sidebar");
if (hamburgerButton && sidebar) {
  if (localStorage.getItem("sidebar") === "open") {
    sidebar.classList.add("open");
  }

  hamburgerButton.addEventListener("click", () => {
    sidebar.classList.toggle("open");

    if (sidebar.classList.contains("open")) {
      localStorage.setItem("sidebar", "open");
    } else {
      localStorage.setItem("sidebar", "closed");
    }
  });
}

const profileMenuTrigger = document.querySelector(".profileMenu-trigger");
const profileMenu = document.querySelector(".profileMenu");

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
    if (!profileMenu.contains(event.target) && !profileMenuTrigger.contains(event.target)) {
      closeProfileMenu();
    }
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      closeProfileMenu();
    }
  });
}
