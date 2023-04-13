<?php

//echo (PHP_VERSION);

//$argv[1] = "800x600 50%";
preg_match("/(\d+x\d+)/i", $argv[1], $width_height_input);
//echo ("<pre>" . print_r($width_height_input, 1) . "</pre>");
preg_match("/ +(\d+%?)/i", $argv[1], $change_input);
//echo ("<pre>" . print_r($change_input, 1) . "</pre>");

$json_output = '{
  "skipknowledge": true,
  "items": [
    {
        "uid": "invalid_command",
        "title": "Keep typing...",
        "subtitle": "Input format: [width]x[height] [new width/change in percent]. E.g. 800x600 50%",
        "arg": "",
        "autocomplete": ""
    }
  ]}';
$json_output_info = "";
$json_output_change = "";

// Check input, query e.g. "800x600 150%";
if (count($width_height_input) > 0) {
  // A match was found for width and height.

  // Extract the width and height from the first argument by splitting it on the "x" character.
  $width_height = $width_height_input[1];
  $width_height = explode("x", $width_height);

  // Create an associative array to store the original width and height as integers.
  $original_width_height = array("width" => (int)$width_height[0], "height" => (int)$width_height[1]);

  $aspect_ratio = get_aspect_ratio($original_width_height['width'], $original_width_height['height']);

  $json_output = '{
    "skipknowledge": true,
    "items": [
    {
      "uid": "aspect_ratio",
      "title": "' . $aspect_ratio[0] . ':' . $aspect_ratio[1] . '",
      "subtitle": "Aspect ratio",
      "arg": "' . $aspect_ratio[0] . ':' . $aspect_ratio[1] . '"
    }';

  $json_output_info = ',
    {
      "uid": "invalid_command",
      "title": "Keep typing...",
      "subtitle": "Input format: [width]x[height] [new width/change in percent]. E.g. 800x600 50%",
      "arg": "",
      "autocomplete": ""
  }';

  preg_match("/ +(\d+%?)/i", $argv[1], $change_input);
  if (count($change_input) > 0) {
    $json_output_info = "";
    // Get the second argument, which specifies how the image should be resized.
    $change = $change_input[1];

    // If the second argument contains a percentage sign, resize the image by a percentage.
    if (strpos($change, "%") !== false) {
      $change = (int)str_replace('%', '', $change);
      $new_size = get_resized_size($original_width_height['width'], $original_width_height['height'], $change);
    }
    // Otherwise, resize the image to a specific height.
    else {
      $new_size = determine_new_height($original_width_height['width'], $original_width_height['height'], $change);
    }

    $json_output_change = ',
      {
          "uid": "width_and_height",
          "title": "' . $new_size["width"] . 'x' . $new_size["height"] . '",
          "subtitle": "New width and height",
          "arg": "' . $new_size["width"] . 'x' . $new_size["height"] . '"
      },
      {
        "uid": "width_and_height_html",
        "title": "width=\"' . $new_size["width"] . 'px\" height=\"' . $new_size["height"] . 'px\"",
        "subtitle": "New width and height in HTML",
        "arg": "width=\"' . $new_size["width"] . '\" height=\"' . $new_size["height"] . '\""
    },
      {
        "uid": "width",
        "title": "' . $new_size["width"] . '",
        "subtitle": "New width",
        "arg": "' . $new_size["width"] . '"
      },
      {
        "uid": "height",
        "title": "' . $new_size["height"] . '",
        "subtitle": "New height",
        "arg": "' . $new_size["height"] . '"
      }';
  }
  $json_output_change .= ']}';
}

echo ($json_output . $json_output_info . $json_output_change);

/**
 * Calculates the new height of an image based on the original width and height, and a new width.
 *
 * @param int $original_width The original width of the image.
 * @param int $original_height The original height of the image.
 * @param int $new_width The desired new width of the image.
 * @return array An associative array containing the final width and height of the image.
 */
function determine_new_height($original_width, $original_height, $new_width)
{
  $final_width = (int)$new_width;
  $final_height = (int)(($original_height / $original_width) * $new_width); // Calculate the new height based on the original aspect ratio and the new width.
  return array("width" => $final_width, "height" => $final_height); // Return an associative array containing the final width and height of the image.
}

/**
 * Calculates the new width and height of an image based on the original dimensions and a percentage to resize by.
 *
 * @param int $original_width The original width of the image.
 * @param int $original_height The original height of the image.
 * @param int $percent The percentage to resize the image by.
 * @return array An associative array containing the final width and height of the image.
 */
function get_resized_size($original_width, $original_height, $percent)
{
  // Calculate the resize ratio based on the percentage.
  $ratio = $percent / 100;

  // Calculate the new width and height based on the resize ratio.
  $new_width = $original_width * $ratio;
  $new_height = $original_height * $ratio;

  // Calculate the ratios between the new dimensions and the original dimensions.
  $width_ratio = $new_width / $original_width;
  $height_ratio = $new_height / $original_height;

  // Choose the smaller of the two ratios to ensure that the image fits within the desired dimensions.
  $final_ratio = min($width_ratio, $height_ratio);

  // Calculate the final width and height based on the chosen ratio.
  $final_width = (int)round($original_width * $final_ratio);
  $final_height = (int)round($original_height * $final_ratio);

  // Return an associative array containing the final width and height of the image.
  return array("width" => $final_width, "height" => $final_height);
}

/**
 * Calculates the aspect ratio of the a resolution, which is the ratio of its width to its height.
 *
 * @param int $width The width of the image.
 * @param int $height The height of the image.
 * @return array An associative array containing the final aspect ratio.
 */
function get_aspect_ratio($width, $height)
{
  // Use the gmp_gcd function to find the greatest common divisor of $width and $height
  $divisor = gmp_intval(gmp_gcd($width, $height));

  // Divide $width and $height by the greatest common divisor to get the reduced aspect ratio
  //$aspectRatio = $width / $divisor . ':' . $height / $divisor;

  // Return the reduced aspect ratio as an array
  return array($width / $divisor, $height / $divisor);
}
