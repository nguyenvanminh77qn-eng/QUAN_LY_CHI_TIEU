document.addEventListener("DOMContentLoaded", function () {
  var menuToggle = document.getElementById("menu-toggle");
  var sidebar = document.getElementById("mySidebar");
  var storageKey = "sidebarCollapsed";

  if (!menuToggle || !sidebar) {
    return;
  }

  // Restore collapsed state from localStorage
  if (localStorage.getItem(storageKey) === "true") {
    sidebar.classList.add("hidden");
  }

  // Toggle on button click
  menuToggle.addEventListener("click", function () {
    sidebar.classList.toggle("hidden");
    localStorage.setItem(storageKey, sidebar.classList.contains("hidden"));
  });

  // ── Scroll reveal via IntersectionObserver ──
  var revealEls = document.querySelectorAll(".reveal");
  if ("IntersectionObserver" in window && revealEls.length) {
    var observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add("visible");
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.1 }
    );
    revealEls.forEach(function (el) {
      observer.observe(el);
    });
  } else {
    // Fallback: show all immediately
    revealEls.forEach(function (el) {
      el.classList.add("visible");
    });
  }
});
