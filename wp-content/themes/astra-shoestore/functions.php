<?php
/** Astra child theme setup. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'astra-shoestore', get_stylesheet_uri(), array( 'astra-theme-css' ), '1.0.0' );
}, 20 );

add_filter( 'body_class', function ( $classes ) {
	if ( is_front_page() ) { $classes[] = 'shoe-home-page'; }
	return $classes;
} );

// Use the storefront shell in place of Astra's default header and footer.
add_action( 'wp', function () {
	remove_action( 'astra_header', 'astra_header_markup' );
	remove_action( 'astra_footer', 'astra_footer_markup' );
} );

/** Storewide storefront shell, rendered through Astra's parent theme hooks. */
function astra_shoestore_render_header() {
	$shop_page = get_page_by_path( 'shop', OBJECT, 'page' );
	$cart_page = get_page_by_path( 'cart', OBJECT, 'page' );
	$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : ( $shop_page ? get_permalink( $shop_page ) : home_url( '/' ) );
	$cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : ( $cart_page ? get_permalink( $cart_page ) : home_url( '/' ) );
	?>
	<div class="shoe-topline">Miễn phí giao hàng cho đơn hàng từ 1.500.000₫ · Đổi trả trong 30 ngày</div>
	<nav class="shoe-nav" aria-label="Điều hướng chính">
		<a class="shoe-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">MOTION<span>LAB</span></a>
		<div class="shoe-links"><a href="<?php echo esc_url( $shop_url ); ?>">Nam</a><a href="<?php echo esc_url( $shop_url ); ?>">Nữ</a><a href="<?php echo esc_url( $shop_url ); ?>">Chạy bộ</a><a href="<?php echo esc_url( $shop_url ); ?>">Lifestyle</a><a href="<?php echo esc_url( $shop_url ); ?>">Outlet</a></div>
		<div class="shoe-actions"><a href="<?php echo esc_url( $shop_url ); ?>">Tìm kiếm　⌕</a><a href="<?php echo esc_url( $cart_url ); ?>" aria-label="Giỏ hàng">Giỏ hàng　♡</a><button class="shoe-menu-toggle" aria-label="Mở menu">☰</button></div>
	</nav>
	<?php
}
add_action( 'astra_header', 'astra_shoestore_render_header', 1 );

function astra_shoestore_render_footer() {
	$shop_page = get_page_by_path( 'shop', OBJECT, 'page' );
	$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : ( $shop_page ? get_permalink( $shop_page ) : home_url( '/' ) );
	?>
	<footer class="shoe-footer"><a class="shoe-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">MOTION<span>LAB</span></a><span>© <?php echo esc_html( gmdate( 'Y' ) ); ?> MotionLab · Chuyển động theo cách của bạn</span><a href="<?php echo esc_url( $shop_url ); ?>">Cửa hàng　↗</a></footer>
	<?php
}
add_action( 'astra_footer', 'astra_shoestore_render_footer', 1 );

/** Create the storefront pages once; WooCommerce can take them over when installed. */
add_action( 'admin_init', function () {
	if ( get_option( 'astra_shoestore_pages_created' ) ) { return; }
	$page_ids = array();
	foreach ( array( 'shop' => 'Shop', 'cart' => 'Cart', 'checkout' => 'Checkout' ) as $slug => $title ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );
		if ( ! $page ) {
			$page_id = wp_insert_post( array(
				'post_title'  => $title,
				'post_name'   => $slug,
				'post_status' => 'publish',
				'post_type'  => 'page',
				'post_content' => '',
			) );
		} else {
			$page_id = $page->ID;
		}
		if ( ! is_wp_error( $page_id ) ) { $page_ids[ $slug ] = (int) $page_id; }
	}
	if ( class_exists( 'WooCommerce' ) ) {
		if ( isset( $page_ids['shop'] ) ) { update_option( 'woocommerce_shop_page_id', $page_ids['shop'] ); }
		if ( isset( $page_ids['cart'] ) ) { update_option( 'woocommerce_cart_page_id', $page_ids['cart'] ); }
		if ( isset( $page_ids['checkout'] ) ) { update_option( 'woocommerce_checkout_page_id', $page_ids['checkout'] ); }
	}
	update_option( 'astra_shoestore_pages_created', 1 );
} );

add_filter( 'template_include', function ( $template ) {
	if ( is_page( array( 'shop', 'cart', 'checkout' ) ) ) {
		$store_template = get_stylesheet_directory() . '/store-page.php';
		if ( file_exists( $store_template ) ) { return $store_template; }
	}
	return $template;
}, 99 );
