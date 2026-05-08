document.addEventListener("DOMContentLoaded", function () {
  var menuToggle = document.getElementById("menu-toggle");
  var sidebar = document.getElementById("mySidebar");

  if (!menuToggle || !sidebar) {
    return;
  }

  menuToggle.addEventListener("click", function () {
    sidebar.classList.toggle("hidden");
  });
});
