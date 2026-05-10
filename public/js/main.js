// Sidebar handling which uses localstorage to save the state of the sidebar(open or closed)
const hamburgerButton = document.querySelector(".sidebar-toggle");
const sidebar = document.querySelector(".sidebar");

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
