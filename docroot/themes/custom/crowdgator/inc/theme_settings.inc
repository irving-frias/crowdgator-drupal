<?php

/**
 * @file
 * Preprocessing of theme_overrides.
 */

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;


/**
 * Implements hook_form_system_theme_settings_alter().
 */
function crowdgator_form_system_theme_settings_alter(&$form, FormStateInterface &$form_state, $form_id = NULL) {
  if (isset($form_id)) {
    return;
  }

  $theme = 'crowdgator';

  $form['open_ai'] = [
    '#type' => 'details',
    '#title' => t('Open AI Settings'),
    '#group' => 'global',
    '#weight' => 11
  ];

  $form['open_ai']['api_key'] = [
    '#type' => 'textfield',
    '#title' => t('Open AI Api Key'),
    '#description' => t('Enter Open AI Api key.'),
    '#default_value' => theme_get_setting('api_key', $theme),
  ];

  $form['#submit'][] = 'crowdgator_theme_settings_form_submit';
}

/**
 * Submits additional fields for crowdgator theme settings form.
 */
function crowdgator_theme_settings_form_submit(array &$form, FormStateInterface $form_state, $form_id = NULL) {
  // Work-around for a core bug affecting admin themes. See issue #943212.
  if (isset($form_id)) {
    return;
  }

  $values = $form_state->getValues();

  $config_key = 'crowdgator.settings';
  $config = \Drupal::service('config.factory')->getEditable($config_key);

  if (isset($values['api_key']) && !empty($values['api_key'])) {
    $config->set('api_key', $values['api_key']);
  }
}
