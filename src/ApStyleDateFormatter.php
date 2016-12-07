<?php

namespace Drupal\date_ap_style;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class ApStyleDateFormatter.
 */
class ApStyleDateFormatter {

  use StringTranslationTrait;

  /**
   * Language manager for retrieving default langcode when none is specified.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(LanguageManagerInterface $language_manager) {
    $this->languageManager = $language_manager;
  }

  /**
   * Format a timestamp to an AP style date format.
   *
   * @param int $timestamp
   *   The timestamp to convert.
   * @param array $options
   *   An array of options that affect how the date string is formatted.
   * @param mixed $timezone
   *   \DateTimeZone object, time zone string or NULL. NULL uses the
   *   default system time zone. Defaults to NULL.
   * @param string $langcode
   *   The language code.
   *
   * @return string
   *   The formatted date string.
   */
  public function formatTimestamp($timestamp, array $options = [], $timezone = NULL, $langcode = NULL) {
    if (empty($langcode)) {
      $langcode = $this->languageManager->getCurrentLanguage()->getId();
    }

    // If no timezone is specified, use the user's if available, or the site
    // or system default.
    if (empty($timezone)) {
      $timezone = drupal_get_user_timezone();
    }

    // Create a DrupalDateTime object from the timestamp and timezone.
    $datetime_settings = array(
      'langcode' => $langcode,
    );

    // Create a DrupalDateTime object from the timestamp and timezone.
    $date = DrupalDateTime::createFromTimestamp($timestamp, $timezone, $datetime_settings);
    $now = new DrupalDateTime('now', $timezone, $datetime_settings);

    if (isset($options['display_day']) && $options['display_day']) {
      // When displaying the day, we need to abbreviate the month.
      switch ($date->format('m')) {
        case '03':
        case '04':
        case '05':
        case '06':
        case '07':
          // Short months get the full print out of their name.
          $ap_date_format = 'F';
          break;

        case '09':
          // September is abbreviated to 'Sep' by PHP but we want 'Sept'.
          $ap_date_format = 'M\t.';
          break;

        default:
          // Other months get an abbreviated print out followed by a period.
          $ap_date_format = 'M.';
          break;
      }

      $ap_date_format .= ' j';
    }
    else {
      // If we're not displaying the day, display the entire month name.
      $ap_date_format = 'F';
    }

    // Optionally add year.
    if ((isset($options['always_display_year']) && $options['always_display_year']) || $date->format('Y') != $now->format('Y')) {
      // Add a comma before the year if the day is displayed.
      if (isset($options['display_day']) && $options['display_day']) {
        $ap_date_format .= ',';
      }
      $ap_date_format .= ' Y';
    }

    $ap_date_string = $date->format($ap_date_format);

    if (isset($options['display_time']) && $options['display_time']) {
      $minutes_and_hours = $date->format('H:i');
      $minutes = $date->format('i');
      $capital = isset($options['capitalize_noon_and_midnight']) && $options['capitalize_noon_and_midnight'];
      if ($minutes_and_hours == '00:00') {
        $ap_time_string = $this->t('midnight');
        if ($capital) {
          $ap_time_string = ucfirst($ap_time_string);
        }
      }
      elseif ($minutes_and_hours == '12:00') {
        $ap_time_string = $this->t('noon');
        if ($capital) {
          $ap_time_string = ucfirst($ap_time_string);
        }
      }
      elseif ($minutes == '00') {
        // Don't display the minutes if it's the top of the hour.
        $ap_time_string = $date->format('g a');
      }
      else {
        $ap_time_string = $date->format('g:i a');
      }

      // Format the modmeridian if it's there.
      $ap_time_string = str_replace(['am', 'pm'], ['a.m.', 'p.m.'], $ap_time_string);

      if (isset($options['time_before_date']) && $options['time_before_date']) {
        $output = $ap_time_string . ' ' . $ap_date_string;
      }
      else {
        $output = $ap_date_string . ' ' . $ap_time_string;
      }
    }
    else {
      $output = $ap_date_string;
    }

    return $output;
  }

}
