<?php

  function createTransaction($request) {
    $currentUser = wp_get_current_user();
    $currentUserId = $currentUser->ID;

    $productSold = $request['product']['sold'] === 'false';

    if ($currentUserId > 0) {
      $productSlug = sanitize_text_field($request['product']['id']);
      $productName = sanitize_text_field($request['product']['title']);
      $buyerId = sanitize_text_field($request['buyerId']);
      $sellerId  = sanitize_text_field($request['sellerId']);
      $address = json_encode($request['address'], JSON_UNESCAPED_UNICODE);
      $product = json_encode($request['product'], JSON_UNESCAPED_UNICODE);

      $productId = getProductIdBySlug($productSlug);
      update_post_meta($productID, 'sold', 'true');

      $response = array(
        'post_author' => $currentUserId,
        'post_type' => 'transaction',
        'post_title' => $buyerId . ' - ' . $productName,
        'post_status' => 'publish',
        'meta_input' => array(
          'buyerId' => $buyerId,
          'sellerId' => $sellerId,
          'address' => $address,
          'product' => $product,
          'sold' => 'false'
        ),
      );

      $postId = wp_insert_post($response);

    } else {
      $response = new WP_Error('permission', 'User do not have permission.', array('status' => 401));
    }

    return rest_ensure_response($response);

  }

  // Registers
  function registerCreateTransaction() {
    register_rest_route('api/v1', '/transaction', array(
      array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'createTransaction'
      ),
    ));
  }

  // Hooks
  add_action('rest_api_init', 'registerCreateTransaction');
?>
