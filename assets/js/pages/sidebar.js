document.addEventListener("DOMContentLoaded", () => {
  // ==========================================
  // 1. XỬ LÝ ẨN/HIỆN SIDEBAR (Cách 1)
  // ==========================================
  const menuToggleBtn = document.getElementById("menu-toggle");
  const sidebar = document.getElementById("mySidebar");

  if (menuToggleBtn && sidebar) {
    menuToggleBtn.addEventListener("click", () => {
      sidebar.classList.toggle("hidden");
    });
  }

  // ==========================================
  // 2. XỬ LÝ ẨN/HIỆN SIDEBAR (Cách 2)
  // ==========================================
  const nutMenu = document.getElementById("nut-menu");
  const thanhSideBar = document.querySelector(".sidebar");

  if (nutMenu && thanhSideBar) {
    nutMenu.addEventListener("click", () => {
      thanhSideBar.classList.toggle("an-sidebar");
    });
  }

  // ==========================================
  // 3. XỬ LÝ CHECKBOX CHỌN TẤT CẢ Ở TRANG XÓA
  // ==========================================
  const checkAll = document.getElementById("checkAll");
  const checkItems = document.querySelectorAll(".checkItem");

  // Kiểm tra xem có checkAll và có ít nhất 1 ô checkItem nào không
  if (checkAll && checkItems.length > 0) {
    checkAll.addEventListener("change", function () {
      checkItems.forEach((item) => {
        item.checked = this.checked;
      });
    });

    checkItems.forEach((item) => {
      item.addEventListener("change", function () {
        if (!this.checked) {
          checkAll.checked = false;
        } else {
          // Kiểm tra xem tất cả các ô con đã được tick chưa
          const allChecked = Array.from(checkItems).every((i) => i.checked);
          checkAll.checked = allChecked;
        }
      });
    });
  }

  // ==========================================
  // 4. XỬ LÝ FORM THÊM CHI TIÊU
  // ==========================================
  const expenseForm = document.getElementById("expenseForm");
  const dateInput = document.getElementById("date");
  const btnReset = document.getElementById("btnReset");

  // Chỉ chạy code nếu form thực sự tồn tại trên trang
  if (expenseForm) {
    // Set ngày mặc định nếu ô input ngày tồn tại
    if (dateInput) {
      dateInput.valueAsDate = new Date();
    }

    expenseForm.addEventListener("submit", (e) => {
      e.preventDefault();

      const amount = document.getElementById("amount")?.value;
      const category = document.getElementById("category")?.value;

      if (amount && category) {
        alert(`Đã lưu giao dịch: ${amount} VND`);
        expenseForm.reset();

        // Form.reset() sẽ xóa luôn ngày mặc định, nên ta cần set lại
        if (dateInput) {
          dateInput.valueAsDate = new Date();
        }
      }
    });
  }

  // Nút hủy riêng biệt
  if (btnReset && expenseForm) {
    btnReset.addEventListener("click", () => {
      if (confirm("Bạn muốn hủy bỏ thay đổi?")) {
        expenseForm.reset();
        if (dateInput) {
          dateInput.valueAsDate = new Date();
        }
      }
    });
  }
});
// Format tiền VND khi nhập
document.addEventListener("DOMContentLoaded", () => {
  const amountInput = document.querySelector("input[name='amount']");

  amountInput.addEventListener("input", () => {
    let value = amountInput.value.replace(/\D/g, "");
    amountInput.value = value;
  });
});
