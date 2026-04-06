// JS thuần xử lý các sự kiện trên trang
document.addEventListener("DOMContentLoaded", () => {
  const form = document.querySelector(".reset-form");

  if (form) {
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      const emailInput = document.getElementById("email").value;
      console.log("Reset link requested for:", emailInput);
      // Xử lý logic gọi API gửi email tại đây...
    });
  }
});
