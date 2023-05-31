<?php

function crowdgator_preprocess_html(&$variables) {
  $config_key = 'crowdgator.settings';
  $config = \Drupal::service('config.factory')->getEditable($config_key);

  if (!empty($config->get('api_key'))) {
    $variables['#attached']['drupalSettings']['open_ai']['api_key'] = $config->get('api_key');
  }
}
