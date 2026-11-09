<?php
/**
 * Store pages backed by WooCommerce when the plugin is available.
 * Provides rich fallbacks and catalog presentation for MotionLab.
 */
get_header();

$slug       = get_post_field( 'post_name', get_queried_object_id() );
$shop_page  = get_page_by_path( 'shop', OBJECT, 'page' );
$cart_page  = get_page_by_path( 'cart', OBJECT, 'page' );
$detail_page = get_page_by_path( 'chi-tiet', OBJECT, 'page' );
$shop_url   = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : ( $shop_page ? get_permalink( $shop_page ) : home_url( '/' ) );
$cart_url   = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : ( $cart_page ? get_permalink( $cart_page ) : home_url( '/cart/' ) );
$detail_url = $detail_page ? get_permalink( $detail_page ) : home_url( '/chi-tiet/' );

$labels     = array( 'shop' => 'Cửa hàng', 'cart' => 'Giỏ hàng', 'checkout' => 'Thanh toán' );
$shortcodes = array( 'shop' => '[products limit="12" columns="3" paginate="true"]', 'cart' => '[woocommerce_cart]', 'checkout' => '[woocommerce_checkout]' );

$is_wc = class_exists( 'WooCommerce' );
$wc_products_count = 0;
if ( $is_wc && function_exists( 'wp_count_posts' ) ) {
	$count_obj = wp_count_posts( 'product' );
	$wc_products_count = isset( $count_obj->publish ) ? (int) $count_obj->publish : 0;
}
?>

<main class="shoe-store-page">
	<div class="shoe-store-heading">
		<p class="shoe-eyebrow">MOTIONLAB · OFFICIAL STORE</p>
		<h1><?php echo esc_html( $labels[ $slug ] ?? 'Cửa hàng' ); ?></h1>
	</div>

	<div class="shoe-store-content">
		<?php if ( 'shop' === $slug || empty( $slug ) ) : ?>

			<!-- FILTER TABS FOR CATALOG -->
			<div class="shoe-catalog-filters">
				<div class="shoe-filter-pills">
					<button type="button" class="shoe-filter-pill is-active" data-cat="all">Tất cả sản phẩm</button>
					<button type="button" class="shoe-filter-pill" data-cat="running">Chạy bộ</button>
					<button type="button" class="shoe-filter-pill" data-cat="lifestyle">Lifestyle</button>
					<button type="button" class="shoe-filter-pill" data-cat="training">Thể thao / Gym</button>
				</div>
				<div class="shoe-catalog-meta">
					<span>Hiển thị 6 sản phẩm nổi bật</span>
				</div>
			</div>

			<?php if ( $is_wc && $wc_products_count > 0 ) : ?>
				<?php echo do_shortcode( '[products limit="12" columns="3" paginate="true"]' ); ?>
			<?php else : ?>
				<!-- SHOWCASE PRODUCTS GRID (WITH DIRECT LINKS TO DETAIL PAGE) -->
				<div class="shoe-product-grid shoe-catalog-grid" id="shoeCatalogGrid">
					
					<a class="shoe-product" href="<?php echo esc_url( $detail_url ); ?>" data-cat="running">
						<div class="shoe-product-image">
							<span class="shoe-badge">Bán chạy · -20%</span>
							<img src="https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=700&q=85" alt="Velocity Runner 01">
						</div>
						<div class="shoe-product-info">
							<div class="shoe-product-title">Velocity Runner 01 Pro</div>
							<div class="shoe-product-type">Giày chạy bộ · Đệm khí phản hồi lực</div>
							<div class="shoe-price">
								<span class="curr">1.890.000₫</span>
								<del class="old">2.350.000₫</del>
							</div>
						</div>
					</a>

					<a class="shoe-product" href="<?php echo esc_url( $detail_url ); ?>" data-cat="lifestyle">
						<div class="shoe-product-image">
							<span class="shoe-badge">Mới</span>
							<img src="https://images.unsplash.com/photo-1495555961986-6d4c1ecb7be3?auto=format&fit=crop&w=700&q=85" alt="Daily Court Classic">
						</div>
						<div class="shoe-product-info">
							<div class="shoe-product-title">Daily Court Classic</div>
							<div class="shoe-product-type">Giày lifestyle · Da cao cấp Unisex</div>
							<div class="shoe-price">
								<span class="curr">1.590.000₫</span>
							</div>
						</div>
					</a>

					<a class="shoe-product" href="<?php echo esc_url( $detail_url ); ?>" data-cat="running">
						<div class="shoe-product-image">
							<span class="shoe-badge">Hiệu năng cao</span>
							<img src="https://images.unsplash.com/photo-1460353581641-37baddab0fa2?auto=format&fit=crop&w=700&q=85" alt="Aero Pace Pro">
						</div>
						<div class="shoe-product-info">
							<div class="shoe-product-title">Aero Pace Pro Marathon</div>
							<div class="shoe-product-type">Giày chạy đường dài · Carbon Plate</div>
							<div class="shoe-price">
								<span class="curr">2.490.000₫</span>
							</div>
						</div>
					</a>

					<a class="shoe-product" href="<?php echo esc_url( $detail_url ); ?>" data-cat="lifestyle">
						<div class="shoe-product-image">
							<span class="shoe-badge">Bộ sưu tập mới</span>
							<img src="https://images.unsplash.com/photo-1552346154-21d32810aba3?auto=format&fit=crop&w=700&q=85" alt="Street Form 02">
						</div>
						<div class="shoe-product-info">
							<div class="shoe-product-title">Street Form 02 Retro</div>
							<div class="shoe-product-type">Giày thời trang đường phố</div>
							<div class="shoe-price">
								<span class="curr">1.790.000₫</span>
							</div>
						</div>
					</a>

					<a class="shoe-product" href="<?php echo esc_url( $detail_url ); ?>" data-cat="training">
						<div class="shoe-product-image">
							<img src="https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?auto=format&fit=crop&w=700&q=85" alt="Glide Flow Neon">
						</div>
						<div class="shoe-product-info">
							<div class="shoe-product-title">Glide Flow Neon Gym</div>
							<div class="shoe-product-type">Giày tập luyện đa năng · Bám sàn</div>
							<div class="shoe-price">
								<span class="curr">1.650.000₫</span>
							</div>
						</div>
					</a>

					<a class="shoe-product" href="<?php echo esc_url( $detail_url ); ?>" data-cat="running">
						<div class="shoe-product-image">
							<span class="shoe-badge">Chống nước</span>
							<img src="https://images.unsplash.com/photo-1608231387042-66d1773070a5?auto=format&fit=crop&w=700&q=85" alt="Trail Breaker All-Terrain">
						</div>
						<div class="shoe-product-info">
							<div class="shoe-product-title">Trail Breaker All-Terrain</div>
							<div class="shoe-product-type">Giày chạy địa hình · Gai chống trượt</div>
							<div class="shoe-price">
								<span class="curr">2.190.000₫</span>
							</div>
						</div>
					</a>

				</div>

				<script>
				document.addEventListener('DOMContentLoaded', function () {
					const pills = document.querySelectorAll('.shoe-filter-pill');
					const cards = document.querySelectorAll('#shoeCatalogGrid .shoe-product');
					pills.forEach(pill => {
						pill.addEventListener('click', function () {
							pills.forEach(p => p.classList.remove('is-active'));
							this.classList.add('is-active');
							const cat = this.getAttribute('data-cat');
							cards.forEach(card => {
								if (cat === 'all' || card.getAttribute('data-cat') === cat) {
									card.style.display = 'block';
								} else {
									card.style.display = 'none';
								}
							});
						});
					});
				});
				</script>
			<?php endif; ?>

		<?php elseif ( $is_wc ) : ?>
			<?php echo do_shortcode( $shortcodes[ $slug ] ?? $shortcodes['shop'] ); ?>
		<?php else : ?>
			<div class="shoe-store-empty">
				<h2>Chưa thể <?php echo 'cart' === $slug ? 'xem giỏ hàng' : 'thanh toán'; ?></h2>
				<p>Hãy kết nối WooCommerce để bật chức năng giỏ hàng và thanh toán trực tuyến bảo mật.</p>
				<a class="shoe-button shoe-button-dark" href="<?php echo esc_url( $shop_url ); ?>">Tiếp tục mua sắm　→</a>
			</div>
		<?php endif; ?>
	</div>
</main>

<?php get_footer(); ?>
