<?php

/**
 * @file
 * Preprocessing of theme_overrides.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_system_theme_settings_alter().
 */
function crowdgatorm_form_system_theme_settings_alter(&$form, FormStateInterface &$form_state, $form_id = NULL) {
  if ($form['var']['#value'] != 'theme_crowdgator_settings') {
    return;
  }
}
