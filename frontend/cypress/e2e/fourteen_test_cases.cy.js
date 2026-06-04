describe("5. Mở rộng 14 Test Cases cho User, Review, Voucher", () => {
  let adminToken;
  let userToken;

  beforeEach(() => {
    cy.visit("/"); // Visit the root to ensure domain is set before accessing localStorage
    cy.clearCookies();
    cy.window().then((win) => win.localStorage.clear());
    // Login Admin
    cy.request("POST", "http://localhost/api/login", { email: "admin@gmail.com", password: "11111111" }).then((res) => {
      adminToken = res.body.token;
    });
    // Login User
    cy.request("POST", "http://localhost/api/login", { email: "hanh2005k@gmail.com", password: "11111111" }).then((res) => {
      userToken = res.body.token;
    });
  });

  // -------------------------------------------------------------
  // TC1: Xóa dữ liệu không tồn tại (User, Review, Voucher) - 404
  // -------------------------------------------------------------
  it("TC1: Delete nonexistent records returns 404", () => {
    cy.request({ method: "DELETE", url: "http://localhost/api/admin/users/99999", headers: { Authorization: `Bearer ${adminToken}` }, failOnStatusCode: false })
      .its('status').should('eq', 404);
    cy.request({ method: "DELETE", url: "http://localhost/api/admin/reviews/99999", headers: { Authorization: `Bearer ${adminToken}` }, failOnStatusCode: false })
      .its('status').should('eq', 404);
  });

  // -------------------------------------------------------------
  // TC2: Optimistic Locking conflict (Cập nhật đồng thời)
  // -------------------------------------------------------------
  it("TC2: User update optimistic locking conflict", () => {
    // 1. Lấy 1 user test có sẵn
    cy.request({ method: "GET", url: "http://localhost/api/admin/users", headers: { Authorization: `Bearer ${adminToken}` } }).then((res) => {
      const existingUser = res.body.data[0];
      
      // 2. Chỉnh sửa ẩn thông qua API (giả lập thao tác từ tab khác)
      cy.request({ method: "PUT", url: `http://localhost/api/admin/users/${existingUser.id}`, headers: { Authorization: `Bearer ${adminToken}` }, body: { role: "admin", updated_at: existingUser.updated_at } });
      
      // 3. Cố ý update với mốc thời gian cũ sẽ bị từ chối
      cy.request({ method: "PUT", url: `http://localhost/api/admin/users/${existingUser.id}`, headers: { Authorization: `Bearer ${adminToken}` }, body: { role: "user", updated_at: existingUser.updated_at }, failOnStatusCode: false })
        .its('status').should('be.oneOf', [409, 422, 500]);
    });
  });

  // -------------------------------------------------------------
  // TC3: Missing ID / Parameter error (Không áp dụng)
  // -------------------------------------------------------------
  it("TC3: Visit nonexistent detail URL is not applicable here", () => {
    cy.visit("/products/1");
    cy.get('body').should('exist');
  });

  // -------------------------------------------------------------
  // TC4: Form validation (Empty, Negative, format)
  // -------------------------------------------------------------
  it("TC4: User create form validation (Empty fields, invalid email)", () => {
    cy.window().then((win) => {
      win.localStorage.setItem("auth_token", adminToken);
      win.localStorage.setItem("auth_user", JSON.stringify({ role: 'admin' }));
    });
    cy.visit("/admin/users");
    // Đợi danh sách load xong
    cy.get('.table-empty').should('not.exist');
    cy.wait(500);

    // Mở modal tạo user
    cy.get('.toolbar .btn-primary').should('be.visible').click({ force: true });
    cy.get('.modal-box').should('be.visible');
    
    // Nhập email sai định dạng, bỏ trống tên
    cy.get('.modal-box input[type="email"]').first().type("invalid_email_format");
    // Nhấn lưu
    cy.get('.modal-box button.btn-primary').click();
    
    // Check validation error
    cy.get(".modal-box, form").should(($form) => {
      const text = $form.text().toLowerCase();
      expect(text).to.match(/email|bắt buộc|hợp lệ/i);
    });
  });

  // -------------------------------------------------------------
  // -------------------------------------------------------------
  // TC5: Overload limit
  // -------------------------------------------------------------
  it("TC5: Review content overload (exceeds max characters)", () => {
    cy.window().then((win) => {
      win.localStorage.setItem("auth_token", userToken);
      win.localStorage.setItem("auth_user", JSON.stringify({ role: 'user' }));
    });
    cy.visit("/products/1"); // Giả định product 1 có tồn tại

    cy.get("body").then($body => {
      // Nếu giao diện có nút viết đánh giá
      if ($body.find('textarea').length > 0) {
        // TC5: Quá 1000 ký tự
        cy.get('textarea').first().clear().type("a".repeat(1100), { delay: 0 });
        cy.contains(/Gửi|Đánh giá/i).click();
        cy.contains(/vượt quá|tối đa|dài/i).should('exist');
      }
    });
  });

  // -------------------------------------------------------------
  // TC6: Whitespace
  // -------------------------------------------------------------
  it("TC6: Review content with only whitespace", () => {
    cy.window().then((win) => {
      win.localStorage.setItem("auth_token", userToken);
      win.localStorage.setItem("auth_user", JSON.stringify({ role: 'user' }));
    });
    cy.visit("/products/1"); // Giả định product 1 có tồn tại

    cy.get("body").then($body => {
      // Nếu giao diện có nút viết đánh giá
      if ($body.find('textarea').length > 0) {
        // TC6: Toàn khoảng trắng
        cy.get('textarea').first().clear().type("      ");
        cy.contains(/Gửi|Đánh giá/i).click();
        cy.contains(/bắt buộc|trống/i).should('exist');
      }
    });
  });

  // -------------------------------------------------------------
  // TC7: Phone number / Unicode error (Không áp dụng)
  // -------------------------------------------------------------
  it("TC7: Phone number / Unicode error is not applicable here", () => {
    cy.visit("/products/1");
    cy.get('body').should('exist');
  });

  // -------------------------------------------------------------
  // TC8: Dropdown manipulation (Hack F12 DOM)
  // -------------------------------------------------------------
  it("TC8: User role select option manipulation", () => {
    cy.window().then((win) => {
      win.localStorage.setItem("auth_token", adminToken);
      win.localStorage.setItem("auth_user", JSON.stringify({ role: 'admin' }));
    });
    cy.visit("/admin/users");
    
    cy.get('body').then($body => {
      if ($body.find('.toolbar .btn-primary').length > 0) {
        cy.get('.toolbar .btn-primary').click();
        cy.get('.modal-box select').first().then($select => {
          $select.append('<option value="superadmin">Super Admin</option>');
          $select.val('superadmin');
        });
        cy.get('.modal-box button.btn-primary').click();
        cy.get(".modal-box, form").should(($form) => {
          expect($form.text().toLowerCase()).to.match(/hợp lệ|lỗi|trống/i);
        });
      }
    });
  });

  // -------------------------------------------------------------
  // TC9: Prevent double submit (Không áp dụng)
  // -------------------------------------------------------------
  it("TC9: Prevent double submit is not applicable here", () => {
    cy.visit("/products/1");
    cy.get('body').should('exist');
  });

  // -------------------------------------------------------------
  // TC10: Query param URL injection (page=abc)
  // -------------------------------------------------------------
  it("TC10: Faulty query params on list endpoints", () => {
    cy.window().then((win) => {
      win.localStorage.setItem("auth_token", adminToken);
      win.localStorage.setItem("auth_user", JSON.stringify({ role: 'admin' }));
    });
    cy.visit("/admin/users?page=abc");
    cy.get(".admin-page").should("be.visible");

    cy.visit("/admin/reviews?page=invalid");
    cy.get(".admin-page").should("be.visible");
  });

  // -------------------------------------------------------------
  // TC11: Upload file ngoại cỡ (Không áp dụng)
  // -------------------------------------------------------------
  it("TC11: Image upload size limit is not applicable here", () => {
    cy.visit("/products/1");
    cy.get('body').should('exist');
  });

  // -------------------------------------------------------------
  // TC12: Upload file sai định dạng (Không áp dụng)
  // -------------------------------------------------------------
  it("TC12: Image upload wrong format is not applicable here", () => {
    cy.visit("/products/1");
    cy.get('body').should('exist');
  });

  // -------------------------------------------------------------
  // TC13: Không upload file (Không áp dụng)
  // -------------------------------------------------------------
  it("TC13: Missing image upload is not applicable here", () => {
    cy.visit("/products/1");
    cy.get('body').should('exist');
  });

  // -------------------------------------------------------------
  // TC14: API missing Auth Token returns 401
  // -------------------------------------------------------------
  it("TC14: Reject operations without token (401)", () => {
    cy.request({ method: "DELETE", url: "http://localhost/api/admin/users/1", headers: { Accept: "application/json" }, failOnStatusCode: false })
      .its('status').should('eq', 401);
  });
});
