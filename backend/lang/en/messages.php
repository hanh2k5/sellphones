<?php

return [
    // Auth
    'register_success'     => 'Registration successful!',
    'login_success'        => 'Login successful!',
    'logout_success'       => 'Logged out successfully.',
    'login_locked'         => 'Account locked due to too many failed attempts. Please try again after :seconds seconds.',
    'login_locked_max'     => 'Too many failed attempts. Account locked for 15 minutes.',
    'login_wrong'          => 'Wrong password. :remaining attempts left.',
    'login_invalid'        => 'Email or password is incorrect.',
    'user_not_found'       => 'No account found with this email.',
    'reset_email_sent'     => 'We have sent a password reset link to your email.',
    'token_invalid'        => 'Token is invalid or has expired.',
    'password_reset_ok'    => 'Password reset successfully!',
    'cannot_delete_self'   => 'Cannot delete yourself!',
    'user_unlocked'        => 'Unlocked :name',
    'user_created'         => 'User created successfully!',
    'user_updated'         => 'Updated successfully!',
    'user_deleted'         => 'User deleted successfully!',

    // Cart
    'cart_added'           => 'Added to cart!',
    'cart_updated'         => 'Cart updated!',
    'cart_removed'         => 'Item removed from cart!',
    'cart_cleared'         => 'Cart cleared!',
    'stock_not_enough'     => 'Insufficient stock.',
    'stock_remaining'      => 'Only :stock items remaining.',
    'stock_remaining_for_product' => 'Product \':name\' only has :stock left in stock!',
    'product_not_found'    => 'Product not found.',
    'no_permission'        => 'Permission denied!',
    'qty_min'              => 'Quantity must be at least 1.',

    // Product
    'product_created'      => 'Product created!',
    'product_updated'      => 'Updated successfully!',
    'product_trashed'      => 'Product moved to trash!',
    'product_restored'     => 'Product restored!',
    'product_force_deleted'=> 'Permanently deleted and storage freed!',
    'upload_success'       => 'Images uploaded!',
    'conflict_data'        => '⚠️ Data has been modified by another user. Please reload!',

    // Category
    'category_created'     => 'Category created!',
    'category_updated'     => 'Category updated!',
    'category_deleted'     => 'Category deleted!',
    'category_has_products'=> 'Cannot delete! Category still has products.',
    'category_has_children'=> 'Please remove or move child categories first.',
    'category_self_parent' => 'Category cannot be its own parent!',

    // Order
    'order_created'        => 'Order placed successfully!',
    'order_approved'       => 'Order approved!',
    'order_cancelled'      => 'Order cancelled!',
    'order_deleted'        => 'Order deleted!',
    'order_conflict'       => '⚠️ Conflict! Order already processed by someone else. Please reload!',
    'order_already_confirmed' => '⚠️ Conflict! Order already approved by admin. Cannot cancel!',
    'order_already_cancelled' => 'Order was already cancelled!',
    'order_not_pending'    => 'Can only approve pending orders!',
    'order_cant_delete_pending'   => 'Cannot delete a pending order!',
    'order_cant_delete_confirmed' => 'Cannot delete a confirmed order!',
    'order_paid'           => 'Payment confirmed!',
    'order_already_processed' => 'Order already processed!',
    'cart_empty'           => 'Cart is empty or products are no longer available!',

    // Voucher
    'voucher_created'      => 'Voucher created!',
    'voucher_updated'      => 'Voucher updated!',
    'voucher_deleted'      => 'Voucher deleted!',
    'voucher_not_found'    => 'Voucher code not found!',
    'voucher_expired'      => 'Voucher expired or fully used!',
    'voucher_applied'      => 'Applied! Discount: :amount',

    // Review
    'review_created'       => 'Thank you for your review!',
    'review_updated'       => 'Review updated!',
    'review_deleted'       => 'Review deleted!',
    'review_no_permission' => 'No permission to edit this review.',
    'review_must_purchase' => 'You can only review products from confirmed orders.',
    'review_already_exists'=> 'You already reviewed this product.',

    // Profile
    'profile_updated'      => 'Profile updated!',
    'profile_conflict'     => '⚠️ Profile updated elsewhere. Please reload!',
    'password_changed'     => 'Password changed successfully!',

    // AI
    'ai_no_api_key'        => '⚠️ Please configure GEMINI_API_KEY in .env',
    'ai_empty_reply'       => '⚠️ Error: AI returned no content.',
    'ai_history_cleared'   => 'Chat history cleared',

    // System
    'system_error'         => 'System error: :error',
    'data_not_found'       => 'Data not found or already deleted!',

    // Validation (custom attributes)
    'attributes' => [
        'name'        => 'Name',
        'email'       => 'Email',
        'password'    => 'Password',
        'price'       => 'Price',
        'stock'       => 'Stock',
        'category_id' => 'Category',
        'quantity'    => 'Quantity',
    ],
];
