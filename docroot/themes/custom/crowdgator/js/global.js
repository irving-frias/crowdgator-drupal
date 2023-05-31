/**
 * @file
 * Global utilities.
 *
 */
(function ($, Drupal, drupalSettings) {

  'use strict';
  var api_key;

  Drupal.behaviors.crowdgator = {
    attach: function (context, settings) {
      api_key = drupalSettings.open_ai.api_key ?? '';
      console.log(api_key);

      fetch('https://api.openai.com/v1/images/generations', {
          method: 'POST',
          headers: {
              'Content-Type': 'application/json',
              'Authorization': 'Bearer ' + api_key
          },
          // body: '{\n    "prompt": "a white siamese cat",\n    "n": 1,\n    "size": "1024x1024"\n  }',
          body: JSON.stringify({
              'prompt': 'a white siamese cat',
              'n': 1,
              'size': '1024x1024'
          })
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
