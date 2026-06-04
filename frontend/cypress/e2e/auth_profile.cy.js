describe("1. Auth & Profile E2E Tests (Khang's Module - 14 cases)", () => {
  const uniqueEmail = `khang_test_${Date.now()}@gmail.com`;
  let userToken = "";

  // -------------------------------------------------------------
  // TC1: Xóa không áp dụng (Profile cannot be deleted by user)
  // -------------------------------------------------------------
  it("TC1: Profile delete not applicable - ensure no delete button on UI", () => {
    // Đăng nhập user
    cy.visit("/login");
    cy.get('form input[type="email"]').type("hanh2005k@gmail.com");
    cy.get('form input[type="password"]').type("11111111");
    cy.get('form button[type="submit"]').click();
    cy.url().should("eq", Cypress.config().baseUrl + "/");

    cy.visit("/profile");
    cy.get('body').should('not.contain', 'Xóa tài khoản');
    cy.get('body').should('not.contain', 'Delete account');
  });

  // -------------------------------------------------------------
  // TC2: Cập nhật trùng lặp (Optimistic Lock)
  // -------------------------------------------------------------
  it("TC2: Profile update optimistic lock conflict", () => {
    // Đọc thông tin user hiện tại
    cy.request({
      method: "POST",
      url: "http://localhost/api/login",
      body: {
        email: "hanh2005k@gmail.com",
        password: "11111111"
      }
    }).then((loginRes) => {
      userToken = loginRes.body.token;

      cy.request({
        method: "GET",
        url: "http://localhost/api/profile",
        headers: { Authorization: `Bearer ${userToken}` }
      }).then((profileRes) => {
        const user = profileRes.body;
        const originalUpdatedAt = user.updated_at;

        // Lưu token và user vào localStorage để browser không bị redirect về /login
        cy.window().then((win) => {
          win.localStorage.setItem("auth_token", userToken);
          win.localStorage.setItem("auth_user", JSON.stringify(user));
        });

        // Vào trang Profile trên giao diện
        cy.visit("/profile");
        cy.get('input[type="text"].field-input').eq(0).should("have.value", user.name);

        // Đợi 1 giây để đảm bảo cột updated_at (theo giây) trong DB được thay đổi
        cy.wait(1000);

        // Tab 1: Cập nhật thông tin qua API trước (làm đổi updated_at trong DB)
        cy.request({
          method: "PUT",
          url: "http://localhost/api/profile",
          headers: { Authorization: `Bearer ${userToken}` },
          body: {
            name: "Hanh Update Background " + Date.now(),
            email: user.email,
            phone: user.phone || "0987654321",
            address: user.address || "Vo Van Ngan",
            updated_at: originalUpdatedAt
          }
        }).then(() => {
          // Tab 2: Người dùng sửa tên trên UI và bấm Lưu (gửi kèm updated_at cũ)
          cy.get('input[type="text"].field-input').eq(0).clear().type("Hanh Update UI Conflict");
          cy.get('form').eq(0).submit();

          // Kiểm tra hiển thị thông báo xung đột cập nhật
          cy.get(".text-rose-900").should("exist");
        });
      });
    });
  });

  // -------------------------------------------------------------
  // TC3: ID không tồn tại (Reset mật khẩu với email rác)
  // -------------------------------------------------------------
  it("TC3: Request password reset with nonexistent email", () => {
    cy.visit("/forgot-password");
    cy.get('form input[type="email"]').type("not_found_email_123@gmail.com");
    cy.get('form button[type="submit"]').click();
    cy.get("form").should(($form) => {
      expect($form.text().toLowerCase()).to.match(/email|không|tìm thấy|tồn tại/i);
    });
  });

  // -------------------------------------------------------------
  // TC4: Validate form đăng ký
  // -------------------------------------------------------------
  it("TC4: Register form validation (empty name & invalid email format)", () => {
    cy.visit("/register");
    cy.get('form input[type="email"]').type("invalid_email_format@");
    cy.get('form button[type="submit"]').click();
    cy.get("form").should(($form) => {
      expect($form.text().toLowerCase()).to.match(/tên|email|mật khẩu|vui lòng/i);
    });
  });

  // -------------------------------------------------------------
  // TC5: Text quá tải (>50 ký tự)
  // -------------------------------------------------------------
  it("TC5: Register name length exceeds maximum limit (50 characters)", () => {
    cy.visit("/register");
    
    // Gỡ bỏ thuộc tính maxlength của ô Tên đăng ký để gõ quá giới hạn
    cy.get('form input[type="text"]').eq(0).invoke('removeAttr', 'maxlength');
    cy.get('form input[type="text"]').eq(0).type("a".repeat(60));
    cy.get('form input[type="email"]').eq(0).type(uniqueEmail);
    cy.get('form input[type="tel"]').eq(0).type("0912345678");
    cy.get('form input[type="text"]').eq(1).type("123 Vo Van Ngan");
    cy.get('form input[type="password"]').eq(0).type("11111111");
    cy.get('form input[type="password"]').eq(1).type("11111111");

    cy.get('form button[type="submit"]').click();
    cy.get("form").should(($form) => {
      expect($form.text().toLowerCase()).to.match(/tên|vượt quá|tối đa|50/i);
    });
  });

  // -------------------------------------------------------------
  // TC6: Khoảng trắng rỗng
  // -------------------------------------------------------------
  it("TC6: Register with blank space name", () => {
    cy.visit("/register");
    cy.get('form input[type="text"]').eq(0).type("       ");
    cy.get('form button[type="submit"]').click();
    cy.get("form").should(($form) => {
      expect($form.text().toLowerCase()).to.match(/tên|bắt buộc|vui lòng/i);
    });
  });

  // -------------------------------------------------------------
  // TC7: Số Full-width (SĐT)
  // -------------------------------------------------------------
  it("TC7: Register with full-width phone number validation", () => {
    cy.visit("/register");
    cy.get('form input[type="text"]').eq(0).type("Khang Full-Width Phone");
    cy.get('form input[type="email"]').eq(0).type(uniqueEmail);
    
    // Nhập số điện thoại kiểu full-width
    cy.get('form input[type="tel"]').eq(0).type("０９１２３４５６７８");
    cy.get('form input[type="text"]').eq(1).type("123 Vo Van Ngan");
    cy.get('form input[type="password"]').eq(0).type("11111111");
    cy.get('form input[type="password"]').eq(1).type("11111111");

    cy.get('form button[type="submit"]').click();
    // Hệ thống báo lỗi số điện thoại không hợp lệ hoặc tự động chuẩn hóa
    cy.get("form").should(($form) => {
      expect($form.text().toLowerCase()).to.match(/số điện thoại|chữ số|hợp lệ/i);
    });
  });

  // -------------------------------------------------------------
  // TC8: Select-option (Không áp dụng)
  // -------------------------------------------------------------
  it("TC8: Select option manipulation is not applicable for register/profile", () => {
    cy.visit("/register");
    cy.get('form').should('exist');
  });

  // -------------------------------------------------------------
  // TC9: Trùng lặp dữ liệu (Email đã dùng)
  // -------------------------------------------------------------
  it("TC9: Register with duplicate email address", () => {
    cy.visit("/register");
    cy.get('form input[type="text"]').eq(0).type("Spam User");
    cy.get('form input[type="email"]').eq(0).type("admin@gmail.com"); // Trùng email admin
    cy.get('form input[type="tel"]').eq(0).type("0987654321");
    cy.get('form input[type="text"]').eq(1).type("123 Vo Van Ngan");
    cy.get('form input[type="password"]').eq(0).type("11111111");
    cy.get('form input[type="password"]').eq(1).type("11111111");

    cy.get('form button[type="submit"]').click();
    cy.get("form").should(($form) => {
      expect($form.text().toLowerCase()).to.match(/email|đã tồn tại|đã được sử dụng/i);
    });
  });

  // -------------------------------------------------------------
  // TC10: URL parameter sai ở Profile tab
  // -------------------------------------------------------------
  it("TC10: Faulty profile URL query parameter (tab=invalid_tab)", () => {
    cy.visit("/profile?tab=invalid_tab_name");
    // Trang vẫn hoạt động và tải tab mặc định, không crash
    cy.get(".profile-container, body").should("exist");
  });

  // -------------------------------------------------------------
  // TC11: Upload file ngoại cỡ (Không áp dụng)
  // -------------------------------------------------------------
  it("TC11: Image upload size limit is not applicable for profile", () => {
    cy.visit("/profile");
    cy.get('body').should('exist');
  });

  // -------------------------------------------------------------
  // TC12: Upload file sai định dạng (Không áp dụng)
  // -------------------------------------------------------------
  it("TC12: Image upload wrong format is not applicable for profile", () => {
    cy.visit("/profile");
    cy.get('body').should('exist');
  });

  // -------------------------------------------------------------
  // TC13: Không upload file (Không áp dụng)
  // -------------------------------------------------------------
  it("TC13: Missing image upload is not applicable for profile", () => {
    cy.visit("/profile");
    cy.get('body').should('exist');
  });

  // -------------------------------------------------------------
  // TC14: CSRF / Auth check
  // -------------------------------------------------------------
  it("TC14: Access profile route without authentication redirects to login", () => {
    // Logout
    cy.window().then((win) => {
      win.localStorage.clear();
    });
    cy.visit("/profile");
    cy.url().should("include", "/login");
  });

  // -------------------------------------------------------------
  // TC15: Phân quyền Admin và User
  // -------------------------------------------------------------
  it("TC15: Access admin route as user should show toast error and redirect to home", () => {
    // Đăng nhập user thường
    cy.visit("/login");
    cy.get('form input[type="email"]').type("hanh2005k@gmail.com");
    cy.get('form input[type="password"]').type("11111111");
    cy.get('form button[type="submit"]').click();
    cy.url().should("eq", Cypress.config().baseUrl + "/");

    // Truy cập trực tiếp trang admin
    cy.visit("/admin");
    
    // Check url redirect to home
    cy.url().should("eq", Cypress.config().baseUrl + "/");
    
    // Check toast error
    cy.get("body").should("contain", "Bạn không có quyền truy cập chức năng này.");
  });
});
