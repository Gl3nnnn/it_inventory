<?php
// Load the image
$image = imagecreatefrompng('4.png');

if (!$image) {
    die('Unable to load image');
}

// Increase brightness by 20
imagefilter($image, IMG_FILTER_BRIGHTNESS, 20);

// Increase contrast by 10
imagefilter($image, IMG_FILTER_CONTRAST, -10);

// Convert to palette for transparency
imagetruecolortopalette($image, false, 255);

// Find the closest color to black
$black = imagecolorclosest($image, 0, 0, 0);

// Make black transparent
imagecolortransparent($image, $black);

// Save the enhanced image with transparent background
imagepng($image, '4_enhanced.png');

// Free memory
imagedestroy($image);

echo "Image enhanced with transparent background and saved as 4_enhanced.png";
?>
