<?php
/**
 * The only front-end template. PHP prints the <head> (SEO, fonts, custom code),
 * the React mount point and the data for the current URL. React renders the body.
 *
 * @package jameskellie
 */

use JK\Boot;

defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html <?php language_attributes(); ?> class="no-js">
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div id="jk-root"></div>
<?php Boot::print_data(); ?>
<?php Boot::print_noscript(); ?>
<?php wp_footer(); ?>
</body>
</html>
