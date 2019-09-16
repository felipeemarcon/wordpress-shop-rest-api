<?php

  $templateDirectory = get_template_directory();

  require_once($templateDirectory . '/customPostType/productPostType.php');
  require_once($templateDirectory . '/customPostType/transactionPostType.php');

  require_once($templateDirectory . '/endpoints/User.php');
  require_once($templateDirectory . '/endpoints/Product.php');
  require_once($templateDirectory . '/endpoints/Transaction.php');

  function getProductIdBySlug($slug) {
   $query = new WP_Query(array(
      'name' => $slug,
      'post_type' => 'product',
      'numberposts' => 1,
      'fields' => 'ids'
    ));
    $posts = $query->get_posts();
    return array_shift($posts);
  }

  add_action('rest_pre_serve_request', function() {
    header('Access-Control-Expose-Headers: X-Total-Count');
  });

  function expireToken() {
    return time() + (60 * 60 * 24);
  }

  add_action('jwt_auth_expire', 'expireToken');

?>
