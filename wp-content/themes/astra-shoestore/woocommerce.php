<?php
/** WooCommerce page wrapper for the MotionLab storefront. */
if ( is_singular( 'product' ) ) {
	include get_stylesheet_directory() . '/single-product.php';
	return;
}

get_header();
?>
<main class="shoe-store-page shoe-woocommerce-page">
	<?php woocommerce_content(); ?>
</main>
<?php get_footer(); ?>
