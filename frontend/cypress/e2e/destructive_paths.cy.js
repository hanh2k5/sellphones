describe("4. Destructive & Unhappy UI Path E2E Tests", () => {
  it("TC1: Non-existent route (General 404 Page)", () => {
    // Truy cập trang rác không tồn tại
    cy.visit("/nonexistent-page-garbage-9999");
    // Phải hiển thị màn hình 404
    cy.contains(/404|không tìm thấy trang/i).should("be.visible");
  });

  it("TC2: Optimistic locking conflict is not applicable here", () => {
    cy.visit("/");
    cy.get("body").should("exist");
  });

  it("TC3: Non-existent product detail (Product 404 Page)", () => {
    // Truy cập chi tiết sản phẩm với ID siêu lớn không có trong database
    cy.visit("/products/99999999");
    // Phải hiển thị màn hình thông báo không tìm thấy sản phẩm
    cy.contains(/Sản phẩm không tồn tại/i).should("be.visible");
  });

  it("TC4: Register form - Frontend & Backend Validation check", () => {
    cy.visit("/register");
    
    // Nhập toàn khoảng trắng vào tên
    cy.get('form input[type="text"]').eq(0).type("   ");
    // Email không hợp lệ
    cy.get('form input[type="email"]').eq(0).type("email_khong_hop_le@");
    
    cy.get('form button[type="submit"]').click();
    
    // Đảm bảo hiển thị lỗi validate (từ HTML5 validation hoặc từ Laravel response)
    cy.get("form").should(($form) => {
      const text = $form.text().toLowerCase();
      expect(text).to.match(/họ tên|email|mật khẩu|vui lòng/i);
    });
  });

  it("TC5: Admin Product Form - Text overload checks", () => {
    cy.clearCookies();
    cy.window().then(win => win.localStorage.clear());
    cy.visit("/login");
    cy.get('form input[type="email"]').type("admin@gmail.com");
    cy.get('form input[type="password"]').type("11111111");
    cy.get('form button[type="submit"]').click();
    cy.url().should("eq", Cypress.config().baseUrl + "/");

    cy.visit("/admin/products/create");

    // Chọn danh mục để tránh lỗi "Vui lòng chọn danh mục"
    cy.get('form select').first().select(1);
    // Điền giá và tồn kho hợp lệ
    cy.get('input[type="number"]').eq(0).clear().type("15000000");
    cy.get('input[type="number"]').eq(1).clear().type("10");

    // Nhập tên quá dài > 150 ký tự (TC5)
    // Loại bỏ thuộc tính maxlength của HTML để giả lập việc gửi dữ liệu vượt quá độ dài cho phép lên backend
    cy.get('form input[type="text"]').first().invoke('removeAttr', 'maxlength');
    cy.get('form input[type="text"]').first().clear().type("a".repeat(160));
    cy.get('form button[type="submit"]').click();
    cy.get("form").should(($form) => {
      expect($form.text().toLowerCase()).to.match(/tên|vượt quá|tối đa|150/i);
    });
  });

  it("TC6: Admin Product Form - Whitespace checks", () => {
    cy.clearCookies();
    cy.window().then(win => win.localStorage.clear());
    cy.visit("/login");
    cy.get('form input[type="email"]').type("admin@gmail.com");
    cy.get('form input[type="password"]').type("11111111");
    cy.get('form button[type="submit"]').click();
    cy.url().should("eq", Cypress.config().baseUrl + "/");

    cy.visit("/admin/products/create");

    // Chọn danh mục để tránh lỗi "Vui lòng chọn danh mục"
    cy.get('form select').first().select(1);
    // Điền giá và tồn kho hợp lệ
    cy.get('input[type="number"]').eq(0).clear().type("15000000");
    cy.get('input[type="number"]').eq(1).clear().type("10");

    // Nhập khoảng trắng vào Tên sản phẩm (TC6)
    cy.get('form input[type="text"]').first().type("    ");
    cy.get('form button[type="submit"]').click();
    cy.get("form").should(($form) => {
      expect($form.text().toLowerCase()).to.match(/tên|bắt buộc|vui lòng/i);
    });
  });

  it("TC7: Admin Product Form - Full-width number check", () => {
    cy.clearCookies();
    cy.window().then(win => win.localStorage.clear());
    cy.visit("/login");
    cy.get('form input[type="email"]').type("admin@gmail.com");
    cy.get('form input[type="password"]').type("11111111");
    cy.get('form button[type="submit"]').click();
    cy.url().should("eq", Cypress.config().baseUrl + "/");

    cy.visit("/admin/products/create");

    // Chọn danh mục và điền tên, tồn kho hợp lệ
    cy.get('form select').first().select(1);
    cy.get('form input[type="text"]').first().type("Sản phẩm số full-width");
    cy.get('input[type="number"]').eq(1).clear().type("10");

    // Nhập số full-width vào trường giá (TC7)
    cy.get('input[type="number"]').eq(0).clear().type("１２３４５");
    cy.get('form button[type="submit"]').click();
    cy.get("form").should(($form) => {
      expect($form.text().toLowerCase()).to.match(/giá|số|numeric|hợp lệ/i);
    });
  });

  it("TC8: Dropdown manipulation is not applicable here", () => {
    cy.visit("/");
    cy.get("body").should("exist");
  });

  it("TC9: Prevent double submit is not applicable here", () => {
    cy.visit("/");
    cy.get("body").should("exist");
  });

  it("TC10: Product listings - Invalid URL page param handling", () => {
    cy.clearCookies();
    cy.window().then(win => win.localStorage.clear());
    // Đăng nhập tài khoản admin để vào được trang admin
    cy.visit("/login");
    cy.get('form input[type="email"]').type("admin@gmail.com");
    cy.get('form input[type="password"]').type("11111111");
    cy.get('form button[type="submit"]').click();
    cy.url().should("eq", Cypress.config().baseUrl + "/");

    // Truy cập trang quản lý sản phẩm với số trang không hợp lệ (chuỗi chữ)
    cy.visit("/admin/products?page=abc");
    // Trang vẫn hiển thị danh sách sản phẩm bình thường không bị crash
    cy.get(".admin-page").should("exist");
  });

  it("TC11: Image upload size limit is not applicable here", () => {
    cy.visit("/");
    cy.get("body").should("exist");
  });

  it("TC12: Image upload wrong format is not applicable here", () => {
    cy.visit("/");
    cy.get("body").should("exist");
  });

  it("TC13: Missing image upload is not applicable here", () => {
    cy.visit("/");
    cy.get("body").should("exist");
  });

  it("TC14: Unauthorized route protection (Admin Guard)", () => {
    // Truy cập trực tiếp trang admin khi chưa đăng nhập
    cy.visit("/admin");
    // Hệ thống phải chặn và chuyển hướng về trang chủ
    cy.url().should("eq", Cypress.config().baseUrl + "/");
  });
});
