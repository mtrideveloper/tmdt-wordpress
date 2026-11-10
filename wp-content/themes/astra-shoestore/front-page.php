<?php
/** Front page template for Astra Shoe Store. */
get_header();
$shop_page = get_page_by_path( 'shop', OBJECT, 'page' );
$cart_page = get_page_by_path( 'cart', OBJECT, 'page' );
$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : ( $shop_page ? get_permalink( $shop_page ) : home_url( '/' ) );
$cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : ( $cart_page ? get_permalink( $cart_page ) : home_url( '/' ) );
?>
<main class="shoe-home">
	<section class="shoe-hero"><div class="shoe-hero-copy"><p class="shoe-eyebrow">Bộ sưu tập mới · 2026</p><h1>Bước đi<br>khác biệt.</h1><p>Thiết kế cho nhịp sống của bạn. Khám phá những đôi giày kết hợp hiệu năng, sự thoải mái và phong cách mỗi ngày.</p><a class="shoe-button" href="<?php echo esc_url( $shop_url ); ?>">Khám phá bộ sưu tập <span>→</span></a></div></section>
	<section class="shoe-section"><div class="shoe-section-head"><h2>Chọn phong cách của bạn</h2><a class="shoe-text-link" href="<?php echo esc_url( $shop_url ); ?>">Xem tất cả　→</a></div><div class="shoe-categories">
		<a class="shoe-category" href="<?php echo esc_url( $shop_url ); ?>"><img src="https://images.unsplash.com/photo-1538805060514-97d9cc17730c?auto=format&fit=crop&w=900&q=80" alt="Giày chạy bộ"><span>Chạy bộ<i>Nhẹ hơn trên mọi cung đường　↗</i></span></a>
		<a class="shoe-category" href="<?php echo esc_url( $shop_url ); ?>"><img src="https://images.unsplash.com/photo-1525966222134-fcfa99b8ae77?auto=format&fit=crop&w=900&q=80" alt="Giày phong cách hằng ngày"><span>Hằng ngày<i>Thoải mái từ sáng đến tối　↗</i></span></a>
		<a class="shoe-category" href="<?php echo esc_url( $shop_url ); ?>"><img src="https://images.unsplash.com/photo-1552346154-21d32810aba3?auto=format&fit=crop&w=900&q=80" alt="Giày thể thao"><span>Thể thao<i>Sẵn sàng cho thử thách mới　↗</i></span></a>
	</div></section>
	<section class="shoe-promo"><div class="shoe-promo-image" role="img" aria-label="Giày thể thao màu sắc nổi bật"></div><div class="shoe-promo-copy"><p class="shoe-eyebrow">Chuyển động tự do</p><h2>Không giới hạn<br>đường chạy.</h2><p>Đệm êm, độ bám chắc và thiết kế linh hoạt giúp bạn tập trung vào điều quan trọng nhất: bước tiếp theo.</p><a class="shoe-button shoe-button-dark" href="<?php echo esc_url( $shop_url ); ?>">Tìm đôi giày phù hợp　→</a></div></section>
	<section class="shoe-section shoe-products"><div class="shoe-section-head"><h2>Được yêu thích</h2><a class="shoe-text-link" href="<?php echo esc_url( $shop_url ); ?>">Mua tất cả　→</a></div>
		<?php if ( class_exists( 'WooCommerce' ) ) : ?>
			<?php echo do_shortcode( '[products limit="4" columns="4" best_selling="true" orderby="popularity"]' ); ?>
		<?php else : ?>
		<div class="shoe-product-grid">
			<a class="shoe-product" href="<?php echo esc_url( $shop_url ); ?>"><div class="shoe-product-image"><span class="shoe-badge">Bán chạy</span><img src="https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=650&q=80" alt="Giày thể thao đỏ"></div><div class="shoe-product-info"><div class="shoe-product-title">Velocity Runner 01</div><div class="shoe-product-type">Giày chạy bộ · Nam/Nữ</div><div class="shoe-price">1.890.000₫</div></div></a>
			<a class="shoe-product" href="<?php echo esc_url( $shop_url ); ?>"><div class="shoe-product-image"><span class="shoe-badge">Mới</span><img src="https://images.unsplash.com/photo-1495555961986-6d4c1ecb7be3?auto=format&fit=crop&w=650&q=80" alt="Giày thể thao trắng"></div><div class="shoe-product-info"><div class="shoe-product-title">Daily Court Classic</div><div class="shoe-product-type">Giày lifestyle · Unisex</div><div class="shoe-price">1.590.000₫</div></div></a>
			<a class="shoe-product" href="<?php echo esc_url( $shop_url ); ?>"><div class="shoe-product-image"><img src="https://images.unsplash.com/photo-1460353581641-37baddab0fa2?auto=format&fit=crop&w=650&q=80" alt="Giày chạy bộ hiệu năng"></div><div class="shoe-product-info"><div class="shoe-product-title">Aero Pace Pro</div><div class="shoe-product-type">Giày chạy bộ · Nam/Nữ</div><div class="shoe-price">2.490.000₫</div></div></a>
			<a class="shoe-product" href="<?php echo esc_url( $shop_url ); ?>"><div class="shoe-product-image"><span class="shoe-badge">Bộ sưu tập mới</span><img src="https://images.unsplash.com/photo-1552346154-21d32810aba3?auto=format&fit=crop&w=650&q=80" alt="Giày thể thao thời trang"></div><div class="shoe-product-info"><div class="shoe-product-title">Street Form 02</div><div class="shoe-product-type">Giày lifestyle · Unisex</div><div class="shoe-price">1.790.000₫</div></div></a>
		</div><?php endif; ?>
	</section>
	<div class="shoe-benefits"><div class="shoe-benefit"><strong>↗</strong> Giao hàng miễn phí từ 1.500.000₫</div><div class="shoe-benefit"><strong>⟳</strong> Đổi trả dễ dàng trong 30 ngày</div><div class="shoe-benefit"><strong>✓</strong> Cam kết sản phẩm chính hãng</div></div>
	<section class="shoe-newsletter"><h2>Chuyển động cùng chúng tôi</h2><p>Đăng ký nhận tin để cập nhật bộ sưu tập mới và ưu đãi dành riêng cho bạn.</p><form action="#" method="post"><input type="email" placeholder="Địa chỉ email của bạn" aria-label="Địa chỉ email" required><button type="submit">Đăng ký　→</button></form></section>
</main>
<?php get_footer(); ?>
