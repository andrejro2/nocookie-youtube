<?php

namespace Drupal\wysiwyg_youtube_nocookie\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to replace YouTube domain to youtube-nocookie.
 *
 * @Filter(
 *   id = "filter_nocookie_youtube",
 *   title = @Translation("Youtube nocookie domain"),
 *   description = @Translation("Automatically replaces youtube.com embed's with youtube-nocookie.com domain."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE
 * )
 */
class FilterYoutubenocookie extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $urls = [];
    $result = new FilterProcessResult($text);

    // Extract all the iFrames in the text.
    preg_match_all('/<iframe.*src=\"(.*)\".*><\/iframe>/isU', $text, $iframes);

    if (count($iframes) > 0) {
      if (isset($iframes[1])) {
        foreach ($iframes[1] as $url) {
          // Look for the youtube id.
          $id = $this->extractYoutubeIdfromUrl($url);
          if ($id) {
            $urls[] = ['url' => $url, 'id' => $id];
          }
        }
      }
    }

    // If non empty urls array loop through and replace existing youtube.com
    // url with youtube-nocookie.com.
    if (!empty($urls)) {
      foreach ($urls as $data) {
        if ($data['id'] && $data['url']) {
          $privacy_url = sprintf('https://www.youtube-nocookie.com/embed/%s', $data['id']);
          $text = str_replace($data['url'], $privacy_url, $text);
        }
      }
    }
    $result->setProcessedText($text);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('Replace iFrames refrencings youtube.com with the youtube-nocookie.com for privacy.');
  }

  /**
   * Extracts the id from a URL.
   *
   * @param string $url
   *   Url string.
   *
   * @return string|null
   *   Extracted ID.
   */
  private function extractYoutubeIdfromUrl(string $url): ?string {
    // Supports protocols "http://", "https://" or "//".
    $prefix = '(?:https?://|//)?';

    $url_regexes = [
      // Embed url. The id comes after the 'embed' keyword.
      $prefix . '(?!.*list=)(?:www\.)?youtube\.com/embed/(?<id>[^\#\&\?]+)(?:/.*)?',
      // No cookie domain. Only supports embedded urls. Does not support the m
      // subdomain.
      $prefix . '(?:www\.)?youtube\-nocookie\.com/embed/(?<id>[^\#\&\?]+)(?:/.*)?',
    ];

    foreach ($url_regexes as $regex) {
      preg_match('#^' . $regex . '#i', $url, $matches);
      if (isset($matches['id'])) {
        return $matches['id'];
      }
    }

    return NULL;
  }

}
