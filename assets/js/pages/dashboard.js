// dashboard.js - JavaScript cho trang dashboard

document.addEventListener("DOMContentLoaded", function () {
  // Toggle sidebar
  const sidebarToggle = document.querySelector(".sidebar-toggle");
  const sidebar = document.querySelector(".sidebar");

  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener("click", function () {
      sidebar.classList.toggle("hidden");
    });
  }

  // Khởi tạo các biểu đồ hoặc tương tác khác nếu cần
  // Ví dụ: Chart.js hoặc tương tự có thể được thêm vào đây

  console.log("Dashboard JS loaded");
});
