<?php

  function productScheme($slug) {
    $postId = getProductIdBySlug($slug);

    if($postId) {
      $postMeta = get_post_meta($postId);

      $images = get_attached_media('image', $postId);
      $imagesArray = null;

      if ($images) {
        $imagesArray = array();
        foreach ($images as $key => $value) {
          $imagesArray[] = array(
            'title' => $value->post_name,
            'url' => $value->guid
          );
        }
      }

      $response = array(
        'id' => $slug,
        'photos' => $imagesArray,
        'title' => $postMeta['title'][0],
        'price' => $postMeta['price'][0],
        'description' => $postMeta['description'][0],
        'sold' => $postMeta['sold'][0],
        'userId' => $postMeta['userId'][0],
      );
    } else {
      $response = new WP_Error('not found', "Product don't found", array('status' => 404));
    }

    return $response;
  }

  // Create a Product
  function createProduct($request) {
    $currentUser = wp_get_current_user();
    $currentUserId = $currentUser->ID;

    if ($currentUserId > 0) {
      $title = sanitize_text_field($request['title']);
      $price = sanitize_text_field($request['price']);
      $description = sanitize_text_field($request['description']);
      $userId = $currentUser->user_login;

      $response = array(
        'post_author' => $currentUserId,
        'post_type' => 'product',
        'post_title' => $title,
        'post_status' => 'publish',
        'meta_input' => array(
          'title' => $title,
          'price' => $price,
          'description' => $description,
          'userId' => $userId,
          'sold' => 'false'
        ),
      );

      $productId = wp_insert_post($response);
      $response['id'] = get_post_field('post_name', $productId);

      $files = $request->get_file_params();

      if ($files) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        foreach ($files as $file => $array) {
          media_handle_upload($file, $productId);
        }
      }

    } else {
      $response = new WP_Error('permission', 'User do not have permission.', array('status' => 401));
    }

    return rest_ensure_response($response);

  }

  // Show a single Product
  function showProduct($request) {
    $response = productScheme($request['slug']);
    return rest_ensure_response($response);
  }

  // Show all Products
  function showProducts($request) {

    $q = sanitize_text_field($request['q']) ?: '';
    $_page = sanitize_text_field($request['_page']) ?: 0;
    $_limit = sanitize_text_field($request['_limit']) ?: 9;
    $userId = sanitize_text_field($request['userId']);

    $userIdQuery = null;

    if ($userId) {
      $userIdQuery = array(
        'key' => 'userId',
        'value' => $userId,
        'compair' => '='
      );
    }

    $sold = array(
      'key' => 'sold',
      'value' => 'false',
      'compair' => '='
    );

    $query = array(
      'post_type' => 'product',
      'posts_per_page' => $_limit,
      'paged' => $_page,
      's' => $q,
      'meta_query' => array(
        $userIdQuery,
        $sold
      ),
    );

    $loop = new WP_Query($query);
    $posts = $loop->posts;
    $totalPosts = $loop->found_posts;

    $products = array();

    foreach ($posts as $key => $value) {
      $products[] = productScheme($value->post_name);
    }

    $response = rest_ensure_response($products);
    $response->header('X-Total-Count', $totalPosts);

    return $response;
  }

  // Delete a Product
  function deleteProduct($request) {

    $currentUser = wp_get_current_user();
    $currentUserId = $currentUser->ID;

    $slug = $request['slug'];
    $productId = getProductIdBySlug($slug);

    $authorId = (int) get_post_field('post_author', $productId);

    if ($currentUserId === $authorId) {
      $images = get_attached_media('image', $productId);

      if ($images) {
        foreach ($images as $key => $value) {
          wp_delete_attachment($value->ID, true);
        }
      }

      $response = wp_delete_post($productId, true);

    } else {
      $response = new WP_Error('permission', 'User do not have permission.', array('status' => 401));
    }

    return rest_ensure_response($response);

  }

  function registerCreateProduct() {
    register_rest_route('api/v1', '/product', array(
      array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'createProduct'
      ),
    ));
  }

  function registerShowProduct() {
    register_rest_route('api/v1', '/product/(?P<slug>[-\w]+)', array(
      array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'showProduct'
      ),
    ));
  }

  function registerShowProducts() {
    register_rest_route('api/v1', '/product', array(
      array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'showProducts'
      ),
    ));
  }

  function registerDeleteProduct() {
    register_rest_route('api/v1', '/product/(?P<slug>[-\w]+)', array(
      array(
        'methods' => WP_REST_Server::DELETABLE,
        'callback' => 'deleteProduct`',
      ),
    ));
  }

  // Hooks
  add_action('rest_api_init', 'registerCreateProduct');
  add_action('rest_api_init', 'registerShowProduct');
  add_action('rest_api_init', 'registerShowProducts');
  add_action('rest_api_init', 'registerDeleteProduct');

?>
