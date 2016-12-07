<?php

namespace Drupal\date_ap_style\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\date_ap_style\ApStyleDateFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'timestamp' formatter as time ago.
 *
 * @FieldFormatter(
 *   id = "timestamp_ap_style",
 *   label = @Translation("AP Style"),
 *   field_types = {
 *     "datetime",
 *     "timestamp",
 *     "created",
 *     "changed",
 *   }
 * )
 */
class ApStyleDateFieldFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The date formatter.
   *
   * @var \Drupal\date_ap_style\ApStyleDateFormatter
   */
  protected $apStyleDateFormatter;

  /**
   * Constructs a TimestampAgoFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\date_ap_style\ApStyleDateFormatter $date_formatter
   *   The date formatter.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ApStyleDateFormatter $date_formatter) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->apStyleDateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // @see \Drupal\Core\Field\FormatterPluginManager::createInstance().
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('date_ap_style.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'always_display_year' => TRUE,
      'display_day' => TRUE,
      'display_time' => TRUE,
      'time_before_date' => TRUE,
      'display_noon_and_midnight' => FALSE,
      'capitalize_noon_and_midnight' => FALSE,
      'timezone' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['always_display_year'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Always display year'),
      '#description' => $this->t('When unchecked, the year will not be displayed if the date is in the same year as the current date.'),
      '#default_value' => $this->getSetting('always_display_year'),
    ];

    $elements['display_day'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display day'),
      '#default_value' => $this->getSetting('display_day'),
    ];

    $elements['display_time'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display time'),
      '#default_value' => $this->getSetting('display_time'),
    ];

    $elements['time_before_date'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display time before date'),
      '#description' => $this->t('When checked, the time will be displayed before the date. Otherwise it will be displayed after the date.'),
      '#default_value' => $this->getSetting('time_before_date'),
    ];

    $elements['display_noon_and_midnight'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display noon and midnight'),
      '#default_value' => $this->getSetting('display_noon_and_midnight'),
      '#description' => $this->t('Converts 12:00 p.m. to "noon" and 12:00 a.m. to "midnight".'),
    ];

    $elements['capitalize_noon_and_midnight'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Capitalize noon and midnight'),
      '#default_value' => $this->getSetting('capitalize_noon_and_midnight'),
    ];

    $elements['timezone'] = array(
      '#type' => 'select',
      '#title' => $this->t('Time zone'),
      '#options' => array('' => $this->t('- Default site/user time zone -')) + system_time_zones(FALSE),
      '#default_value' => $this->getSetting('timezone'),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if ($this->getSetting('always_display_year')) {
      $summary[] = $this->t('Always displaying year');
    }

    if ($this->getSetting('display_day')) {
      $summary[] = $this->t('Displaying day');
    }

    if ($this->getSetting('display_time')) {
      $display_time = $this->t('Displaying time');
      if ($this->getSetting('time_before_date')) {
        $display_time .= ' (before date)';
      }
      else {
        $display_time .= ' (after date)';
      }
      $summary[] = $display_time;

      if ($this->getSetting('display_noon_and_midnight')) {
        $noon_and_midnight = $this->t('Displaying noon and midnight');
        if ($this->getSetting('capitalize_noon_and_midnight')) {
          $noon_and_midnight .= ' (capitalized)';
        }
        $summary[] = $noon_and_midnight;
      }
    }

    if ($timezone = $this->getSetting('timezone')) {
      $summary[] = $this->t('Time zone: @timezone', array('@timezone' => $timezone));
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    $opts = [
      'always_display_year',
      'display_day',
      'display_time',
      'time_before_date',
      'display_noon_and_midnight',
      'capitalize_noon_and_midnight',
    ];

    $options = [];
    foreach ($opts as $opt) {
      if ($this->getSetting($opt)) {
        $options[$opt] = TRUE;
      }
    }

    $timezone = $this->getSetting('timezone') ?: NULL;

    $field_type = $items->getFieldDefinition()->getType();

    foreach ($items as $delta => $item) {
      if ($field_type == 'datetime') {
        $timestamp = $item->date->getTimestamp();
      }
      else {
        $timestamp = $item->value;
      }

      $elements[$delta] = [
        '#cache' => [
          'contexts' => [
            'timezone',
          ],
        ],
        '#markup' => $this->apStyleDateFormatter->formatTimestamp($timestamp, $options, $timezone, $langcode),
      ];
    }

    return $elements;
  }

}
