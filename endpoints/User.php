<?php

  function createUser($request) {

    $name = sanitize_text_field($request['name']);
	  $email = sanitize_email($request['email']);
	  $password = $request['password'];
    $address = sanitize_text_field($request['address']);
	  $number = sanitize_text_field($request['number']);
	  $cep = sanitize_text_field($request['cep']);
	  $city = sanitize_text_field($request['cep']);
	  $state = sanitize_text_field($request['state']);
	  $neighborhood = sanitize_text_field($request['neighborhood']);

    $userExists = username_exists($email);
    $emailExists = email_exists($email);

    if (!$userExists && !$emailExists && $email && $password) {
      $userId = wp_create_user($email, $password, $email);

      $response = array(
        'ID' => $userId,
        'display_name' => $name,
        'first_name' => $name,
        'role' => 'subscriber'
      );

      wp_update_user($response);

      update_user_meta($userId, 'address', $address);
      update_user_meta($userId, 'cep', $cep);
      update_user_meta($userId, 'number', $number);
      update_user_meta($userId, 'neighborhood', $neighborhood);
      update_user_meta($userId, 'city', $city);
      update_user_meta($userId, 'state', $state);

    } else {
      $response = new WP_Error('email', 'Email já cadastrado', array('status' => 403));
    }

    return rest_ensure_response($response);
  }

  function showUser($request) {
    $user = wp_get_current_user();
    $userId = $user->ID;

    if($userId > 0) {
      $userMeta = get_user_meta($userId);

      $response = array(
        'id' => $user->user_login,
        'name' => $user->display_name,
        'email' => $user->user_email,
        'address' => $userMeta['address'][0],
        'cep' => $userMeta['cep'][0],
        'number' => $userMeta['number'][0],
        'city' => $userMeta['city'][0],
        'neighborhood' => $userMeta['neighborhood'][0],
        'state' => $userMeta['state'][0]
      );
    } else {
      $response = new WP_Error('permission', 'User do not have permission', array('status' => 401));
    }

    return rest_ensure_response($response);
  }

  function updateUser($request) {

    $user = wp_get_current_user();
    $userId = $user->ID;

    if ($userId > 0) {
      $name = sanitize_text_field($request['name']);
      $email = sanitize_email($request['email']);
      $password = $request['password'];
      $address = sanitize_text_field($request['address']);
      $number = sanitize_text_field($request['number']);
      $cep = sanitize_text_field($request['cep']);
      $city = sanitize_text_field($request['cep']);
      $state = sanitize_text_field($request['state']);
      $neighborhood = sanitize_text_field($request['neighborhood']);

      $emailExists = email_exists($email);

      if (!$emailExists || $emailExists === $userId) {

        $response = array(
          'ID' => $userId ,
          'user_pass' => $password,
          'user_email' => $email,
          'display_name' => $name,
          'first_name' => $name
        );

        wp_update_user($response);

        update_user_meta($userId, 'address', $address);
        update_user_meta($userId, 'cep', $cep);
        update_user_meta($userId, 'number', $number);
        update_user_meta($userId, 'neighborhood', $neighborhood);
        update_user_meta($userId, 'city', $city);
        update_user_meta($userId, 'state', $state);

      } else {
        $response = new WP_Error('email', 'Email já cadastrado', array('status' => 403));
      }
    } else {
      $response = new WP_Error('permission', 'User do not have permission.', array('status' => 401));
    }

    return rest_ensure_response($response);

  }

  function deleteUser() {}

  function registerCreateUser() {
    register_rest_route('api/v1', '/user', array(
      array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'createUser'
      ),
    ));
  }

  function registerShowUser() {
    register_rest_route('api/v1', '/user', array(
      array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'showUser'
      ),
    ));
  }

  function registerUpdateUser() {
    register_rest_route('api/v1', '/user', array(
      array(
        'methods' => WP_REST_Server::EDITABLE,
        'callback' => 'updateUser'
      ),
    ));
  }

  add_action('rest_api_init', 'registerCreateUser');
  add_action('rest_api_init', 'registerShowUser');
  add_action('rest_api_init', 'registerUpdateUser');

?>
