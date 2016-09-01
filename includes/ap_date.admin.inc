<?php

/**
 * @file
 * Publication date administration forms.
 */

/**
 * Date AP Style administration page for callback.
 */
function date_ap_style_admin_form($form, &$form_state) {
  $form['ap_style_config'] = array(
    '#type' => 'fieldset',
    '#title' => t('AP Style Date Display Settings'),
    '#description' => t('Configure AP date style default settings when using the AP date style format. (Some options are only available for entity date fields.)'),
    '#collapsible' => FALSE,
    '#collapsed' => FALSE,
  );
  $form['ap_style_config']['date_ap_style_defaults'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Display Options'),
    '#options' => array(
      'use_today' => t('Use today'),
      'cap_today' => t('Capitalize today'),
      'use_year' => t('Use year'),
      'cap_noon' => t('Capitalize noon and midnight'),
      'show_time' => t('Show time'),
      'use_all_day' => t('Show "All Day" instead of midnight'),
      'use_dow' => t('Use day of week for current week'),
    ),
    '#default_value' => variable_get('date_ap_style_defaults', array()),
  );

  return system_settings_form($form);
}
