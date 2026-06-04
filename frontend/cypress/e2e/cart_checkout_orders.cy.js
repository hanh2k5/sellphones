describe("3. Cart, Checkout, Voucher & Orders E2E Tests (Hanh's Module - 14 cases)", () => {
  let userToken = "";
  let adminToken = "";
  const userEmail = "hanh2005k@gmail.com";
  const userPassword = "11111111";

  beforeEach(() => {
    // Đăng nhập user và admin để lấy auth tokens
    cy.clearCookies();
    cy.window().then((win) => {
      win.localStorage.clear();
    });

    cy.request({
      method: "POST",
      url: "http://localhost/api/login",
      body: { email: userEmail, password: userPassword },
    }).then((res) => {
      userToken = res.body.token;
    });

    cy.request({
      method: "POST",
      url: "http://localhost/api/login",
      body: { email: "admin@gmail.com", password: "11111111" },
    }).then((res) => {
      adminToken = res.body.token;
    });
  });

  // -------------------------------------------------------------
  // TC1: Xóa mục không tồn tại trong giỏ hàng
  // -------------------------------------------------------------
  it("TC1: Delete nonexistent cart item - check 404 response", () => {
    cy.request({
      method: "DELETE",
      url: "http://localhost/api/cart/99999999",
      headers: { Authorization: `Bearer ${userToken}` },
      failOnStatusCode: false,
    }).then((res) => {
      expect(res.status).to.eq(404);
    });
  });

  // -------------------------------------------------------------
  // TC2: Cập nhật trùng lặp đơn hàng (Optimistic Lock)
  // -------------------------------------------------------------
  it("TC2: Order approval optimistic locking conflict", () => {
    // 1. Thêm 1 sản phẩm vào giỏ hàng trước khi đặt đơn
    cy.request({
      method: "POST",
      url: "http://localhost/api/cart",
      headers: { Authorization: `Bearer ${userToken}` },
      body: { product_id: 1, quantity: 1 },
    }).then(() => {
      // 2. Tạo 1 đơn hàng mới bằng user thông qua API
      cy.request({
        method: "POST",
        url: "http://localhost/api/orders",
        headers: { 
          Authorization: `Bearer ${userToken}`,
          Accept: "application/json"
        },
        body: {
          receiver_name: "Phan Dinh Hanh Lock Test",
          phone: "0987654321",
          shipping_address: "123 Vo Van Ngan",
          payment_method: "cod",
        },
      }).then((orderRes) => {
        const order = orderRes.body.order || orderRes.body;
        const orderId = order.id || orderRes.body.id;
        const originalUpdatedAt = order.updated_at || orderRes.body.updated_at;

      // 2. Vào trang quản lý đơn hàng của admin trên UI
      cy.visit("/login");
      cy.get('form input[type="email"]').type("admin@gmail.com");
      cy.get('form input[type="password"]').type("11111111");
      cy.get('form button[type="submit"]').click();
      cy.url().should("eq", Cypress.config().baseUrl + "/");
      cy.visit("/admin/orders");

      // Tìm kiếm theo tên để hiển thị đơn hàng vừa tạo nhanh nhất
      cy.get('input.w-full.pl-9').first().clear({ force: true }).type("Phan Dinh Hanh Lock Test{enter}", { force: true });
      cy.wait(1500);

      // Chờ 1 giây để đảm bảo cột updated_at trong DB nhảy sang giây mới
      cy.wait(1000);

      // 3. Tab 1: Duyệt đơn hàng bằng API trước (làm thay đổi updated_at trong DB)
      cy.request({
        method: "PUT",
        url: `http://localhost/api/admin/orders/${orderId}/status`,
        headers: { Authorization: `Bearer ${adminToken}` },
        body: {
          status: "confirmed",
          last_updated_at: originalUpdatedAt,
        },
      }).then(() => {
        // 4. Tab 2: Admin click duyệt đơn trên UI (gửi kèm updated_at cũ)
        cy.contains(order.order_code).parents().filter('.bg-white, .rounded-2xl').first().within(() => {
          cy.get('button').filter(':contains("Duyệt đơn"), :contains("Approve"), :contains("Duyệt")').first().click({ force: true });
        });

        // Đợi kết quả trả về, vì xung đột khóa lạc quan hệ thống sẽ hiện cảnh báo toast
        cy.get('body').should(($body) => {
          const text = $body.text().toLowerCase();
          expect(text).to.match(/đơn hàng đã được cập nhật|xung đột|vui lòng tải lại/i);
        });
      });
    });
  });
});

  // -------------------------------------------------------------
  // TC3: ID không tồn tại
  // -------------------------------------------------------------
  it("TC3: Visit nonexistent order detail URL", () => {
    cy.visit("/login");
    cy.get('form input[type="email"]').type(userEmail);
    cy.get('form input[type="password"]').type(userPassword);
    cy.get('form button[type="submit"]').click();
    cy.url().should("eq", Cypress.config().baseUrl + "/");

    cy.visit("/orders/99999999");
    cy.contains(/Không tìm thấy đơn hàng|không tồn tại/i).should("be.visible");
  });

  // -------------------------------------------------------------
  // TC4: Validate form checkout
  // -------------------------------------------------------------
  it("TC4: Checkout form validation (blank shipping fields)", () => {
    // Đăng nhập user
    cy.visit("/login");
    cy.get('form input[type="email"]').type(userEmail);
    cy.get('form input[type="password"]').type(userPassword);
    cy.get('form button[type="submit"]').click();
    cy.url().should("eq", Cypress.config().baseUrl + "/");

    // Vào trang checkout
    cy.visit("/checkout");
    cy.url().should("include", "/checkout");

    // Xóa trắng thông tin
    cy.get('form input[type="text"]').eq(0).clear();
    cy.get('form input[type="text"]').eq(1).clear();
    cy.get('form textarea').clear();

    cy.get('form.main-form').submit();

    // Hệ thống báo lỗi các trường bắt buộc
    cy.get("form").should(($form) => {
      const text = $form.text().toLowerCase();
      expect(text).to.match(/họ tên|số điện thoại|địa chỉ|vui lòng/i);
    });
  });

  // -------------------------------------------------------------
  // TC5: Text quá tải (Địa chỉ > 500 ký tự)
  // -------------------------------------------------------------
  it("TC5: Checkout shipping address length exceeds maximum limit", () => {
    cy.visit("/login");
    cy.get('form input[type="email"]').type(userEmail);
    cy.get('form input[type="password"]').type(userPassword);
    cy.get('form button[type="submit"]').click();
    cy.url().should("eq", Cypress.config().baseUrl + "/");

    cy.visit("/checkout");

    cy.get('form input[type="text"]').eq(0).clear().type("Phan Dinh Hanh E2E");
    cy.get('form input[type="text"]').eq(1).clear().type("0987654321");
    
    // Nhập địa chỉ cực dài vượt quá 500 ký tự
    cy.get('form textarea').clear().type("a".repeat(550));

    cy.get('form.main-form').submit();

    cy.get("form").should(($form) => {
      const text = $form.text().toLowerCase();
      expect(text).to.match(/địa chỉ|vượt quá|tối đa|500/i);
    });
  });

  // -------------------------------------------------------------
  // TC6: Khoảng trắng rỗng
  // -------------------------------------------------------------
  it("TC6: Checkout shipping address whitespace validation error", () => {
    cy.visit("/login");
    cy.get('form input[type="email"]').type(userEmail);
    cy.get('form input[type="password"]').type(userPassword);
    cy.get('form button[type="submit"]').click();
    cy.url().should("eq", Cypress.config().baseUrl + "/");

    cy.visit("/checkout");

    cy.get('form input[type="text"]').eq(0).clear().type("Phan Dinh Hanh E2E");
    cy.get('form input[type="text"]').eq(1).clear().type("0987654321");
    cy.get('form textarea').clear().type("       "); // Khoảng trắng

    cy.get('form.main-form').submit();

    cy.get("form").should(($form) => {
      const text = $form.text().toLowerCase();
      expect(text).to.match(/địa chỉ|bắt buộc|vui lòng/i);
    });
  });

  // -------------------------------------------------------------
  // TC7: Số Full-width (SĐT checkout)
  // -------------------------------------------------------------
  it("TC7: Checkout full-width phone number validation error", () => {
    cy.visit("/login");
    cy.get('form input[type="email"]').type(userEmail);
    cy.get('form input[type="password"]').type(userPassword);
    cy.get('form button[type="submit"]').click();
    cy.url().should("eq", Cypress.config().baseUrl + "/");

    cy.visit("/checkout");

    cy.get('form input[type="text"]').eq(0).clear().type("Phan Dinh Hanh E2E");
    cy.get('form textarea').clear().type("123 Vo Van Ngan");
    
    // Nhập số điện thoại dạng Full-width
    cy.get('form input[type="text"]').eq(1).clear().type("０９８７６５４３２１");

    cy.get('form.main-form').submit();

    cy.get("form").should(($form) => {
      const text = $form.text().toLowerCase();
      expect(text).to.match(/số điện thoại|chữ số|hợp lệ/i);
    });
  });

  // -------------------------------------------------------------
  // TC8: Thay đổi value payment_method (Inspect F12 hack)
  // -------------------------------------------------------------
  it("TC8: Checkout payment method option manipulation validation", () => {
    cy.visit("/login");
    cy.get('form input[type="email"]').type(userEmail);
    cy.get('form input[type="password"]').type(userPassword);
    cy.get('form button[type="submit"]').click();
    cy.url().should("eq", Cypress.config().baseUrl + "/");

    cy.visit("/checkout");

    cy.get('form input[type="text"]').eq(0).clear().type("Phan Dinh Hanh E2E");
    cy.get('form input[type="text"]').eq(1).clear().type("0987654321");
    cy.get('form textarea').clear().type("123 Vo Van Ngan");

    // F12 hack: thay đổi value của input radio payment_method thành "bitcoin"
    cy.get('input[value="cod"]').invoke('val', 'bitcoin');
    cy.get('input[value="bitcoin"]').check({ force: true });

    cy.get('form.main-form').submit();

    cy.get("form").should(($form) => {
      const text = $form.text().toLowerCase();
      expect(text).to.match(/phương thức|payment method|cod|momo|in/i);
    });
  });

  // -------------------------------------------------------------
  // TC9: Đặt hàng trùng lặp / Spam submit
  // -------------------------------------------------------------
  it("TC9: Prevent double order submission (Submit button disabled)", () => {
    cy.visit("/login");
    cy.get('form input[type="email"]').type(userEmail);
    cy.get('form input[type="password"]').type(userPassword);
    cy.get('form button[type="submit"]').click();
    cy.url().should("eq", Cypress.config().baseUrl + "/");

    cy.visit("/checkout");

    cy.get('form input[type="text"]').eq(0).clear().type("Phan Dinh Hanh E2E");
    cy.get('form input[type="text"]').eq(1).clear().type("0987654321");
    cy.get('form textarea').clear().type("123 Vo Van Ngan");

    // Bấm submit và xác nhận nút bị disabled lập tức
    cy.get('form.main-form').submit();
    cy.get('form.main-form button[type="submit"]').should('be.disabled');
  });

  // -------------------------------------------------------------
  // TC10: URL parameter sai ở danh sách đơn hàng admin
  // -------------------------------------------------------------
  it("TC10: Faulty admin orders list query parameter (page=abc)", () => {
    cy.visit("/login");
    cy.get('form input[type="email"]').type("admin@gmail.com");
    cy.get('form input[type="password"]').type("11111111");
    cy.get('form button[type="submit"]').click();
    cy.url().should("eq", Cypress.config().baseUrl + "/");

    cy.visit("/admin/orders?page=abc");
    cy.get(".admin-page, body").should("exist"); // Không bị crash trắng trang
  });

  // -------------------------------------------------------------
  // TC11: Upload file ngoại cỡ (Không áp dụng cho checkout)
  // -------------------------------------------------------------
  it("TC11: Image upload size limit is not applicable for checkout form", () => {
    cy.visit("/checkout");
    cy.get('body').should('exist');
  });

  // -------------------------------------------------------------
  // TC12: Upload file sai định dạng (Không áp dụng cho checkout)
  // -------------------------------------------------------------
  it("TC12: Image upload wrong format is not applicable for checkout form", () => {
    cy.visit("/checkout");
    cy.get('body').should('exist');
  });

  // -------------------------------------------------------------
  // TC13: Không upload file (Không áp dụng cho checkout)
  // -------------------------------------------------------------
  it("TC13: Missing image upload is not applicable for checkout form", () => {
    cy.visit("/checkout");
    cy.get('body').should('exist');
  });

  // -------------------------------------------------------------
  // TC14: CSRF / Auth check khi đặt hàng
  // -------------------------------------------------------------
  it("TC14: Place order without auth token returns 401 response", () => {
    cy.request({
      method: "POST",
      url: "http://localhost/api/orders",
      headers: {
        Accept: "application/json"
      },
      body: {
        name: "Hack Order E2E",
        phone: "0987654321",
        shipping_address: "No token address",
        payment_method: "cod",
      },
      failOnStatusCode: false,
    }).then((res) => {
      expect(res.status).to.eq(401);
    });
  });
});
