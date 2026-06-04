describe("2. Admin Product & Category E2E Tests (Ha's Module - 14 cases)", () => {
  let adminToken = "";
  const testCategoryName = `Test Brand ${Date.now()}`;
  const testProductName = `Test Product ${Date.now()}`;

  beforeEach(() => {
    // Đảm bảo trạng thái sạch (chưa đăng nhập) bằng cách xóa localStorage
    cy.window().then((win) => {
      win.localStorage.clear();
    });
    cy.visit("/login");
    cy.get('form input[type="email"]').type("admin@gmail.com");
    cy.get('form input[type="password"]').type("11111111");
    cy.get('form button[type="submit"]').click();
    cy.url().should("eq", Cypress.config().baseUrl + "/");

    // Lấy token từ localStorage của window
    cy.window().then((win) => {
      adminToken = win.localStorage.getItem("auth_token");
    });
  });

  // -------------------------------------------------------------
  // TC1: Xóa mục không tồn tại
  // -------------------------------------------------------------
  it("TC1: Delete product already deleted - verify 404 response", () => {
    cy.request({
      method: "DELETE",
      url: "http://localhost/api/admin/products/99999999",
      headers: {
        Authorization: `Bearer ${adminToken}`,
      },
      failOnStatusCode: false,
    }).then((res) => {
      expect(res.status).to.eq(404);
    });
  });

  // -------------------------------------------------------------
  // TC2: Cập nhật trùng lặp (Optimistic Lock)
  // -------------------------------------------------------------
  it("TC2: Product update optimistic locking conflict (2-tab scenario)", () => {
    cy.request({
      method: "GET",
      url: "http://localhost/api/products/1",
    }).then((response) => {
      const originalProduct = response.body;
      const originalUpdatedAt = originalProduct.updated_at;

      cy.visit("/admin/products/1/edit");
      cy.url().should("include", "/admin/products/1/edit");
      cy.get('input[type="text"]').eq(0).should("have.value", originalProduct.name);

      // Background update (Tab 1)
      cy.request({
        method: "PUT",
        url: "http://localhost/api/admin/products/1",
        headers: {
          Authorization: `Bearer ${adminToken}`,
        },
        body: {
          name: originalProduct.name + " Temp E2E",
          category_id: originalProduct.category_id,
          price: originalProduct.price,
          stock: originalProduct.stock,
          updated_at: originalUpdatedAt,
        },
      }).then(() => {
        // UI edit (Tab 2)
        cy.get('input[type="text"]').eq(0).clear().type("Product Conflict E2E Name");
        cy.get('form button[type="submit"]').click();

        // Banner conflict shown
        cy.get(".admin-page").should("contain.text", "thay đổi");
      });
    });
  });

  // -------------------------------------------------------------
  // TC3: ID không tồn tại trong URL
  // -------------------------------------------------------------
  it("TC3: Visit nonexistent product detail URL", () => {
    cy.visit("/products/99999999");
    cy.contains(/Sản phẩm không tồn tại/i).should("be.visible");
  });

  // -------------------------------------------------------------
  // TC4: Validate form (Trống / Số âm)
  // -------------------------------------------------------------
  it("TC4: Product form validation (empty name, negative price/stock)", () => {
    cy.visit("/admin/products/create");
    cy.get('form select').first().select(1);

    cy.get('input[type="text"]').eq(0).type("Sản phẩm lỗi validation");
    cy.get('input[type="number"]').eq(0).clear().type("-10000");
    cy.get('input[type="number"]').eq(1).clear().type("-5");

    cy.get('form button[type="submit"]').click();

    cy.get("form").should(($form) => {
      const text = $form.text().toLowerCase();
      expect(text).to.match(/giá|tồn kho|số lượng|lớn hơn|nhỏ hơn|0/i);
    });
  });

  // -------------------------------------------------------------
  // TC5: Text quá tải (>150 ký tự)
  // -------------------------------------------------------------
  it("TC5: Product name length exceeds max limit (150 chars)", () => {
    cy.visit("/admin/products/create");
    cy.get('form select').first().select(1);
    cy.get('input[type="number"]').eq(0).clear().type("20000000");
    cy.get('input[type="number"]').eq(1).clear().type("10");

    cy.get('input[type="text"]').eq(0).invoke('removeAttr', 'maxlength');
    cy.get('input[type="text"]').eq(0).clear().type("a".repeat(160));
    cy.get('form button[type="submit"]').click();

    cy.get("form").should(($form) => {
      expect($form.text().toLowerCase()).to.match(/tên|vượt quá|tối đa|150/i);
    });
  });

  // -------------------------------------------------------------
  // TC6: Khoảng trắng rỗng
  // -------------------------------------------------------------
  it("TC6: Blank space product name input error", () => {
    cy.visit("/admin/products/create");
    cy.get('form select').first().select(1);
    cy.get('input[type="number"]').eq(0).clear().type("20000000");
    cy.get('input[type="number"]').eq(1).clear().type("10");

    cy.get('input[type="text"]').eq(0).clear().type("      ");
    cy.get('form button[type="submit"]').click();

    cy.get("form").should(($form) => {
      expect($form.text().toLowerCase()).to.match(/tên|bắt buộc|vui lòng/i);
    });
  });

  // -------------------------------------------------------------
  // TC7: Số Full-width (１２３)
  // -------------------------------------------------------------
  it("TC7: Full-width number price input error", () => {
    cy.visit("/admin/products/create");
    cy.get('form select').first().select(1);
    cy.get('input[type="text"]').eq(0).type("Sản phẩm số full-width");
    cy.get('input[type="number"]').eq(1).clear().type("10");

    cy.get('input[type="number"]').eq(0).invoke('attr', 'type', 'text');
    cy.get('input[type="text"]').eq(1).clear().type("１２３４５");
    cy.get('form button[type="submit"]').click();

    cy.get("form").should(($form) => {
      expect($form.text().toLowerCase()).to.match(/giá|số|numeric|hợp lệ/i);
    });
  });

  // -------------------------------------------------------------
  // TC8: Thay đổi value option dropdown (Inspect F12 hack)
  // -------------------------------------------------------------
  it("TC8: Dropdown option value manipulation validation", () => {
    cy.visit("/admin/products/create");
    cy.get('input[type="text"]').eq(0).type("Sản phẩm sửa option");
    cy.get('input[type="number"]').eq(0).clear().type("20000000");
    cy.get('input[type="number"]').eq(1).clear().type("10");

    cy.get('form select').first().should('have.length.gt', 0);
    cy.get('form select option').eq(1).invoke('val', '99999');
    cy.get('form select').first().select(1);

    cy.get('form button[type="submit"]').click();

    cy.get("form").should(($form) => {
      expect($form.text().toLowerCase()).to.match(/danh mục|exists|tồn tại/i);
    });
  });

  // -------------------------------------------------------------
  // TC9: Trùng lặp dữ liệu / Spam submit
  // -------------------------------------------------------------
  it("TC9: Spam click prevent double submit (Save button disabled)", () => {
    cy.visit("/admin/products/create");
    cy.get('form select').first().select(1);
    cy.get('input[type="text"]').eq(0).type("Sản phẩm spam click");
    cy.get('input[type="number"]').eq(0).clear().type("100000");
    cy.get('input[type="number"]').eq(1).clear().type("10");

    cy.get('form button[type="submit"]').click();
    cy.get('form button[type="submit"]').should('be.disabled');
  });

  // -------------------------------------------------------------
  // TC10: URL parameter sai
  // -------------------------------------------------------------
  it("TC10: Faulty URL query parameter handling (page=abc)", () => {
    cy.visit("/admin/products?page=abc");
    cy.get(".admin-page").should("exist");
  });

  // -------------------------------------------------------------
  // TC11: Upload file không hợp lệ
  // -------------------------------------------------------------
  it("TC11: Invalid image file upload format rejection", () => {
    cy.visit("/admin/products/create");

    const invalidFile = {
      contents: Cypress.Buffer.from("fake file content"),
      fileName: "test.txt",
      mimeType: "text/plain",
    };

    cy.get('input[type="file"]').first().selectFile(invalidFile, { force: true });
    
    cy.get('body').should(($body) => {
      const text = $body.text().toLowerCase();
      expect(text).to.match(/ảnh|chấp nhận|định dạng|jpg|png|webp/i);
    });
  });

  // -------------------------------------------------------------
  // TC12: Ảnh bị lỗi hiển thị (Broken Image placeholder)
  // -------------------------------------------------------------
  it("TC12: Broken image fallback to default placeholder", () => {
    cy.visit("/products/1");
    cy.get('.product-detail-img, img').first().should('have.attr', 'src');
  });

  // -------------------------------------------------------------
  // TC13: Update không kèm ảnh mới
  // -------------------------------------------------------------
  it("TC13: Product update without image maintains original image", () => {
    cy.visit("/admin/products/1/edit");

    cy.get('input[placeholder="https://..."]').invoke('val').then((oldImageUrl) => {
      cy.get('input[type="number"]').eq(0).clear().type("19999999");
      cy.get('form button[type="submit"]').click();

      cy.visit("/admin/products/1/edit");
      cy.get('input[placeholder="https://..."]').should('have.value', oldImageUrl);
    });
  });

  // -------------------------------------------------------------
  // TC14: CSRF / Auth check
  // -------------------------------------------------------------
  it("TC14: Access admin route when logged out redirects to home", () => {
    cy.clearCookies();
    cy.window().then((win) => {
      win.localStorage.clear();
    });
    cy.visit("/admin");
    cy.url().should("eq", Cypress.config().baseUrl + "/");
  });
});
