<?php
/** Store pages backed by WooCommerce when the plugin is available. */
get_header();
$slug = get_post_field( 'post_name', get_queried_object_id() );
$shop_page = get_page_by_path( 'shop', OBJECT, 'page' );
$shop_url = $shop_page ? get_permalink( $shop_page ) : home_url( '/' );
$labels = array( 'shop' => 'Cửa hàng', 'cart' => 'Giỏ hàng', 'checkout' => 'Thanh toán' );
$shortcodes = array( 'shop' => '[products limit="12" columns="3" paginate="true"]', 'cart' => '[woocommerce_cart]', 'checkout' => '[woocommerce_checkout]' );
?>
<main class="shoe-store-page">
	<div class="shoe-store-heading"><p class="shoe-eyebrow">MOTIONLAB · SHOP ONLINE</p><h1><?php echo esc_html( $labels[ $slug ] ?? 'Cửa hàng' ); ?></h1></div>
	<div class="shoe-store-content">
		<?php if ( class_exists( 'WooCommerce' ) ) : ?>
			<?php echo do_shortcode( $shortcodes[ $slug ] ?? $shortcodes['shop'] ); ?>
		<?php elseif ( 'shop' === $slug ) : ?>
			<div class="shoe-store-empty"><h2>Sẵn sàng chọn đôi giày tiếp theo?</h2><p>Danh mục sản phẩm sẽ xuất hiện tại đây sau khi kết nối WooCommerce.</p><a class="shoe-button shoe-button-dark" href="<?php echo esc_url( home_url( '/' ) ); ?>">Về trang chủ　→</a></div>
		<?php else : ?>
			<div class="shoe-store-empty"><h2>Chưa thể <?php echo 'cart' === $slug ? 'xem giỏ hàng' : 'thanh toán'; ?></h2><p>Hãy cài và kích hoạt WooCommerce để bật chức năng giỏ hàng và thanh toán an toàn.</p><a class="shoe-button shoe-button-dark" href="<?php echo esc_url( $shop_url ); ?>">Tiếp tục mua sắm　→</a></div>
		<?php endif; ?>
	</div>
</main>
<?php get_footer(); ?>
