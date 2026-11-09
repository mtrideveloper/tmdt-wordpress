<?php
/**
 * Single Product Template for Astra Shoe Store (MotionLab).
 * Handles both WooCommerce single products and standalone demo preview mode.
 */
defined( 'ABSPATH' ) || exit;

get_header();

$shop_page     = get_page_by_path( 'shop', OBJECT, 'page' );
$cart_page     = get_page_by_path( 'cart', OBJECT, 'page' );
$checkout_page = get_page_by_path( 'checkout', OBJECT, 'page' );
$shop_url      = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : ( $shop_page ? get_permalink( $shop_page ) : home_url( '/' ) );
$cart_url      = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : ( $cart_page ? get_permalink( $cart_page ) : home_url( '/cart/' ) );
$checkout_url  = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : ( $checkout_page ? get_permalink( $checkout_page ) : home_url( '/checkout/' ) );

$is_wc          = class_exists( 'WooCommerce' );
$queried_id     = get_queried_object_id();
$has_wc_product = $is_wc && ( is_singular( 'product' ) || ( $queried_id && get_post_type( $queried_id ) === 'product' ) );
$product        = $has_wc_product && function_exists( 'wc_get_product' ) ? wc_get_product( $queried_id ) : null;

// Product Data defaults (Demo values when viewing without WooCommerce post)
$title       = 'Velocity Runner 01 Pro';
$sku         = 'ML-VR01-RED';
$category    = 'Giày chạy bộ · Hiệu năng cao';
$price_html  = '<span class="shoe-price-current">1.890.000₫</span><span class="shoe-price-old">2.350.000₫</span><span class="shoe-price-discount">-20%</span>';
$rating_num  = '4.9';
$review_cnt  = 128;
$is_in_stock = true;
$stock_text  = 'Còn 12 đôi trong kho · Sẵn sàng giao';
$short_desc  = 'Được thiết kế cho những bước chạy bứt phá, Velocity Runner 01 kết hợp bộ đế bọt Eva phản hồi lực kép và chất liệu dệt PrimeKnit 3D thoáng khí 360 độ, mang lại cảm giác êm ái, bám đường chắc chắn và tối ưu năng lượng trên mọi cự ly.';

$images = array(
	'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=1200&q=85',
	'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?auto=format&fit=crop&w=1200&q=85',
	'https://images.unsplash.com/photo-1608231387042-66d1773070a5?auto=format&fit=crop&w=1200&q=85',
	'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?auto=format&fit=crop&w=1200&q=85',
);

if ( $product ) {
	$title       = $product->get_name();
	$sku         = $product->get_sku() ? $product->get_sku() : 'ML-' . $product->get_id();
	$price_html  = $product->get_price_html();
	$is_in_stock = $product->is_in_stock();
	$stock_text  = $is_in_stock ? ( $product->get_stock_quantity() ? 'Còn ' . $product->get_stock_quantity() . ' sản phẩm' : 'Còn hàng' ) : 'Hết hàng tạm thời';

	$cats = get_the_terms( $product->get_id(), 'product_cat' );
	if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) {
		$category = $cats[0]->name;
	}

	if ( $product->get_short_description() ) {
		$short_desc = $product->get_short_description();
	}

	$rating_val = (float) $product->get_average_rating();
	if ( $rating_val > 0 ) {
		$rating_num = number_format( $rating_val, 1 );
		$review_cnt = $product->get_review_count();
	}

	$wc_images = array();
	$thumb_id  = $product->get_image_id();
	if ( $thumb_id ) {
		$wc_images[] = wp_get_attachment_image_url( $thumb_id, 'full' );
	}
	$gallery_ids = $product->get_gallery_image_ids();
	if ( ! empty( $gallery_ids ) ) {
		foreach ( $gallery_ids as $gid ) {
			$url = wp_get_attachment_image_url( $gid, 'full' );
			if ( $url ) {
				$wc_images[] = $url;
			}
		}
	}
	if ( ! empty( $wc_images ) ) {
		$images = array_values( array_filter( $wc_images ) );
	}
}

// --------------------------------------------------------------------------
// QUERY REAL RELATED PRODUCTS FROM WOOCOMMERCE / DATABASE
// --------------------------------------------------------------------------
$related_products_list = array();
$current_prod_id       = $product ? $product->get_id() : ( $queried_id ? $queried_id : 0 );

if ( $is_wc ) {
	// 1. First priority: Related products by category & tags (WooCommerce native)
	if ( $current_prod_id && function_exists( 'wc_get_related_products' ) ) {
		$rel_ids = wc_get_related_products( $current_prod_id, 4 );
		if ( ! empty( $rel_ids ) ) {
			foreach ( $rel_ids as $r_id ) {
				$r_prod = wc_get_product( $r_id );
				if ( $r_prod && $r_prod->is_visible() ) {
					$related_products_list[ $r_id ] = $r_prod;
				}
			}
		}
	}

	// 2. Second priority: If fewer than 4 related items, fetch other published products
	if ( count( $related_products_list ) < 4 && function_exists( 'wc_get_products' ) ) {
		$exclude  = array_merge( array( $current_prod_id ), array_keys( $related_products_list ) );
		$extra_wc = wc_get_products( array(
			'status'     => 'publish',
			'limit'      => 4 - count( $related_products_list ),
			'exclude'    => $exclude,
			'orderby'    => 'rand',
			'visibility' => 'catalog',
		) );
		foreach ( $extra_wc as $extra_prod ) {
			$related_products_list[ $extra_prod->get_id() ] = $extra_prod;
		}
	}

	// 3. Fallback WP_Query for post_type 'product'
	if ( count( $related_products_list ) < 4 ) {
		$exclude     = array_merge( array( $current_prod_id ), array_keys( $related_products_list ) );
		$extra_posts = get_posts( array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 4 - count( $related_products_list ),
			'post__not_in'   => $exclude,
			'orderby'        => 'rand',
		) );
		foreach ( $extra_posts as $ep ) {
			$p_obj = function_exists( 'wc_get_product' ) ? wc_get_product( $ep->ID ) : null;
			if ( $p_obj ) {
				$related_products_list[ $ep->ID ] = $p_obj;
			}
		}
	}
}

// Fetch real reviews from WordPress comments if available
$real_reviews = array();
if ( $product ) {
	$real_reviews = get_comments( array(
		'post_id' => $product->get_id(),
		'status'  => 'approve',
	) );
}
?>

<main class="shoe-store-page shoe-detail-page">
	<div class="shoe-detail-container">

		<!-- BREADCRUMBS -->
		<nav class="shoe-breadcrumbs" aria-label="Đường dẫn">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Trang chủ</a>
			<span class="sep">/</span>
			<a href="<?php echo esc_url( $shop_url ); ?>">Cửa hàng</a>
			<span class="sep">/</span>
			<span class="current"><?php echo esc_html( $title ); ?></span>
		</nav>

		<?php
		if ( $is_wc && function_exists( 'wc_print_notices' ) ) {
			echo '<div class="shoe-wc-notices">';
			wc_print_notices();
			echo '</div>';
		}
		?>

		<!-- MAIN PRODUCT CARD -->
		<div class="shoe-detail-card">
			<div class="shoe-detail-grid">

				<!-- LEFT COLUMN: IMAGE GALLERY -->
				<div class="shoe-detail-gallery">
					<div class="shoe-gallery-main">
						<span class="shoe-badge">Bán chạy · 2026</span>
						<img id="shoeMainImage" src="<?php echo esc_url( $images[0] ); ?>" alt="<?php echo esc_attr( $title ); ?>">
						<span class="shoe-gallery-hint">⌕ Rê chuột hoặc chạm để xem chi tiết</span>
					</div>
					<?php if ( count( $images ) > 1 ) : ?>
						<div class="shoe-gallery-thumbs" id="shoeThumbs">
							<?php foreach ( $images as $idx => $img_url ) : ?>
								<button type="button" class="shoe-thumb-btn <?php echo 0 === $idx ? 'is-active' : ''; ?>" data-full="<?php echo esc_url( $img_url ); ?>" aria-label="Xem ảnh <?php echo esc_attr( $idx + 1 ); ?>">
									<img src="<?php echo esc_url( $img_url ); ?>" alt="Góc chụp <?php echo esc_attr( $idx + 1 ); ?>">
								</button>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>

				<!-- RIGHT COLUMN: PRODUCT SUMMARY & PURCHASE FORM -->
				<div class="shoe-detail-summary">
					<p class="shoe-eyebrow">MOTIONLAB · <?php echo esc_html( $category ); ?></p>
					<h1 class="shoe-detail-title"><?php echo esc_html( $title ); ?></h1>

					<div class="shoe-detail-meta-row">
						<div class="shoe-rating-stars" title="<?php echo esc_attr( $rating_num ); ?>/5 sao">
							<span class="stars">★★★★★</span>
							<strong class="score"><?php echo esc_html( $rating_num ); ?></strong>
							<a href="#shoeTabReviews" class="review-link">(<?php echo esc_html( $review_cnt ); ?> đánh giá)</a>
						</div>
						<span class="meta-dot">·</span>
						<div class="shoe-sku-tag">Mã: <strong><?php echo esc_html( $sku ); ?></strong></div>
						<span class="meta-dot">·</span>
						<div class="shoe-stock-badge <?php echo $is_in_stock ? 'in-stock' : 'out-of-stock'; ?>">
							● <?php echo esc_html( $stock_text ); ?>
						</div>
					</div>

					<div class="shoe-detail-price-wrap">
						<?php echo wp_kses_post( $price_html ); ?>
					</div>

					<div class="shoe-detail-desc">
						<p><?php echo wp_kses_post( $short_desc ); ?></p>
					</div>

					<!-- SIZE SELECTION -->
					<div class="shoe-selector-block">
						<div class="shoe-selector-head">
							<label class="shoe-selector-label">Chọn kích cỡ (EU / VN):</label>
							<button type="button" class="shoe-size-guide-link" id="shoeOpenSizeGuide">Bảng đo size chuẩn ↗</button>
						</div>
						<div class="shoe-size-options" id="shoeSizeOptions">
							<?php foreach ( array( '38', '39', '40', '41', '42', '43', '44' ) as $s_idx => $size_val ) : ?>
								<button type="button" class="shoe-size-pill <?php echo '41' === $size_val ? 'is-selected' : ''; ?>" data-size="<?php echo esc_attr( $size_val ); ?>">
									<?php echo esc_html( $size_val ); ?>
								</button>
							<?php endforeach; ?>
						</div>
					</div>

					<!-- COLOR SELECTION -->
					<div class="shoe-selector-block">
						<label class="shoe-selector-label">Phối màu: <strong id="shoeSelectedColor">Đỏ Carmine / Sunset</strong></label>
						<div class="shoe-color-options" id="shoeColorOptions">
							<button type="button" class="shoe-color-pill is-selected" data-color="Đỏ Carmine / Sunset" title="Đỏ Carmine / Sunset">
								<span class="swatch" style="background:#d92525;"></span>
								<span class="name">Đỏ Sunset</span>
							</button>
							<button type="button" class="shoe-color-pill" data-color="Trắng Pearl / Clean" title="Trắng Pearl / Clean">
								<span class="swatch" style="background:#f4f4f4; border:1px solid #ccc;"></span>
								<span class="name">Trắng Pearl</span>
							</button>
							<button type="button" class="shoe-color-pill" data-color="Đen Obsidian / Stealth" title="Đen Obsidian / Stealth">
								<span class="swatch" style="background:#1a1a1a;"></span>
								<span class="name">Đen Obsidian</span>
							</button>
						</div>
					</div>

					<!-- PURCHASE ACTIONS -->
					<?php if ( $product && function_exists( 'woocommerce_template_single_add_to_cart' ) ) : ?>
						<div class="shoe-wc-add-to-cart-wrapper">
							<?php woocommerce_template_single_add_to_cart(); ?>
						</div>
					<?php else : ?>
						<form class="shoe-detail-actions-form" id="shoeDetailDemoForm" onsubmit="return false;">
							<div class="shoe-action-controls">
								<div class="shoe-quantity-stepper">
									<button type="button" class="qty-btn" id="qtyMinus" aria-label="Giảm">-</button>
									<input type="number" id="shoeQtyInput" value="1" min="1" max="99" aria-label="Số lượng">
									<button type="button" class="qty-btn" id="qtyPlus" aria-label="Tăng">+</button>
								</div>
								<button type="button" class="shoe-button shoe-button-cart" id="shoeBtnAddToCart">
									Thêm vào giỏ hàng <span>+</span>
								</button>
							</div>
							<a href="<?php echo esc_url( $checkout_url ); ?>" class="shoe-button shoe-button-buy-now">
								Mua ngay (Hỗ trợ SePay QR) <span>→</span>
							</a>
						</form>
					<?php endif; ?>

					<!-- STORE COMMITMENTS / SEPAY PERKS -->
					<div class="shoe-detail-perks">
						<div class="shoe-perk-item">
							
							<div>
								<strong>Miễn phí giao hàng</strong>
								<span>Đơn từ 1.500.000₫ toàn quốc, nhận hàng sau 2-4 ngày</span>
							</div>
						</div>
						<div class="shoe-perk-item">
						
							<div>
								<strong>Đổi trả 30 ngày dễ dàng</strong>
								<span>Miễn phí đổi kích thước tận nơi nếu không vừa chân</span>
							</div>
						</div>
						<div class="shoe-perk-item">
						
							<div>
								<strong>Cam kết chính hãng 100%</strong>
								<span>Bảo hành keo chỉ 12 tháng tại hệ thống MotionLab</span>
							</div>
						</div>
						<div class="shoe-perk-item">
						
							<div>
								<strong>Thanh toán VietQR / SePay tức thì</strong>
								<span>Quét mã QR chuyển khoản tự động, xác nhận đơn trong 3 giây</span>
							</div>
						</div>
					</div>

				</div>
			</div>
		</div>

		<!-- TABS SECTION -->
		<div class="shoe-detail-tabs-section" id="shoeDetailTabs">
			<div class="shoe-tabs-nav">
				<button type="button" class="shoe-tab-btn is-active" data-tab="tabDesc">Mô tả sản phẩm</button>
				<button type="button" class="shoe-tab-btn" data-tab="tabSpecs">Thông số kỹ thuật</button>
				<button type="button" class="shoe-tab-btn" data-tab="tabSize">Bảng chọn size</button>
				<button type="button" class="shoe-tab-btn" data-tab="tabReviews" id="shoeTabReviews">Đánh giá (<?php echo esc_html( $review_cnt ); ?>)</button>
			</div>

			<!-- TAB 1: DESCRIPTION -->
			<div class="shoe-tab-panel is-active" id="tabDesc">
				<div class="shoe-desc-content">
					<h3>Công nghệ đột phá trên từng bước chân</h3>
					<p>Dòng giày <strong><?php echo esc_html( $title ); ?></strong> được phát triển nhằm tái định nghĩa trải nghiệm vận động hằng ngày của bạn. Bằng việc kết hợp hệ thống đệm hoàn năng lượng thế hệ mới cùng form ôm chân chuẩn công thái học, đôi giày giúp phân tán lực tác động từ mặt đường, giải tỏa áp lực lên khớp gối và cổ chân.</p>
					
					<div class="shoe-features-grid">
						<div class="shoe-feature-card">
							<div class="feature-icon">⚡</div>
							<h4>Bộ đệm bọt Eva Nitro</h4>
							<p>Được nén dưới áp suất cao giúp tăng 75% khả năng đàn hồi và giảm 30% trọng lượng so với đế cao su thông thường.</p>
						</div>
						<div class="shoe-feature-card">
							<div class="feature-icon">🌬️</div>
							<h4>Upper dệt PrimeKnit 3D</h4>
							<p>Cấu trúc sợi dệt liền mạch thoáng khí 360 độ, co giãn linh hoạt theo cử động tự nhiên của mu bàn chân.</p>
						</div>
						<div class="shoe-feature-card">
							<div class="feature-icon">🛡️</div>
							<h4>Đế ngoài bám đường mọi thời tiết</h4>
							<p>Vân rãnh định hướng hình học bằng cao su lưu hóa chống mài mòn, bám chắc kể cả khi chạy trên bề mặt ướt.</p>
						</div>
					</div>

					<p>Dù bạn sử dụng để chinh phục những cung chạy bộ 5km - 21km, tập gym cường độ cao hay dạo phố phong cách thường nhật, MotionLab luôn đồng hành cùng bạn với cảm giác tự tin và thanh thoát nhất.</p>
				</div>
			</div>

			<!-- TAB 2: SPECS -->
			<div class="shoe-tab-panel" id="tabSpecs">
				<table class="shoe-specs-table">
					<tbody>
						<tr><th>Dòng sản phẩm</th><td>Performance Running & Athleisure Lifestyle</td></tr>
						<tr><th>Trọng lượng</th><td>245 gram (Cỡ chuẩn EU 41)</td></tr>
						<tr><th>Độ dốc gót - mũi (Drop)</th><td>8 mm (Gót 32mm / Mũi 24mm)</td></tr>
						<tr><th>Kiểu vòm bàn chân</th><td>Neutral (Bình thường / Vòm cao trung bình)</td></tr>
						<tr><th>Mặt đường khuyên dùng</th><td>Đường nhựa, đường chạy công viên, máy chạy bộ</td></tr>
						<tr><th>Chất liệu thân trên (Upper)</th><td>Vải dệt tái chế kỹ thuật cao (Engineered Mesh)</td></tr>
						<tr><th>Chất liệu đế lót</th><td>Ortholite kháng khuẩn khử mùi, tháo rời vệ sinh được</td></tr>
						<tr><th>Hỗ trợ thanh toán</th><td>COD, Thẻ ngân hàng, Quét mã QR SePay tự động</td></tr>
					</tbody>
				</table>
			</div>

			<!-- TAB 3: SIZE GUIDE -->
			<div class="shoe-tab-panel" id="tabSize">
				<div class="shoe-size-guide-content">
					<h3>Bảng quy đổi kích thước giày chuẩn quốc tế</h3>
					<p>Để chọn được đôi giày vừa vặn nhất, bạn nên đo chiều dài bàn chân vào buổi chiều hoặc tối vì bàn chân có xu hướng giãn nở nhẹ sau ngày dài vận động.</p>
					
					<div class="shoe-table-responsive">
						<table class="shoe-size-table">
							<thead>
								<tr>
									<th>Chiều dài chân (cm)</th>
									<th>Size EU / VN</th>
									<th>Size US Nam</th>
									<th>Size US Nữ</th>
									<th>Size UK</th>
								</tr>
							</thead>
							<tbody>
								<tr><td>23.5 - 24.0 cm</td><td><strong>38</strong></td><td>6.0</td><td>7.5</td><td>5.5</td></tr>
								<tr><td>24.1 - 24.5 cm</td><td><strong>39</strong></td><td>6.5</td><td>8.0</td><td>6.0</td></tr>
								<tr><td>24.6 - 25.0 cm</td><td><strong>40</strong></td><td>7.0</td><td>8.5</td><td>6.5</td></tr>
								<tr class="highlight"><td>25.1 - 25.5 cm</td><td><strong>41</strong></td><td>8.0</td><td>9.5</td><td>7.5</td></tr>
								<tr><td>25.6 - 26.0 cm</td><td><strong>42</strong></td><td>8.5</td><td>10.0</td><td>8.0</td></tr>
								<tr><td>26.1 - 26.5 cm</td><td><strong>43</strong></td><td>9.5</td><td>11.0</td><td>9.0</td></tr>
								<tr><td>26.6 - 27.0 cm</td><td><strong>44</strong></td><td>10.0</td><td>11.5</td><td>9.5</td></tr>
							</tbody>
						</table>
					</div>

					<div class="shoe-measure-steps">
						<h4>3 Bước đo chân tại nhà:</h4>
						<ol>
							<li><strong>Đặt chân lên giấy:</strong> Đặt tờ giấy trắng A4 áp sát chân tường, đứng thẳng tự nhiên và chạm gót vào tường.</li>
							<li><strong>Vạch dấu:</strong> Dùng bút chì dựng đứng vuông góc, đánh dấu vị trí dài nhất của đầu ngón chân.</li>
							<li><strong>Đo khoảng cách:</strong> Dùng thước đo từ cạnh giấy chạm tường đến vạch đã kẻ, cộng thêm 0.5cm nếu bạn thích mang tất dày.</li>
						</ol>
					</div>
				</div>
			</div>

			<!-- TAB 4: REVIEWS -->
			<div class="shoe-tab-panel" id="tabReviews">
				<div class="shoe-reviews-wrap">
					<div class="shoe-reviews-summary">
						<div class="score-box">
							<span class="big-score"><?php echo esc_html( $rating_num ); ?></span>
							<span class="stars">★★★★★</span>
							<span class="count">Dựa trên <?php echo esc_html( $review_cnt ); ?> đánh giá xác thực</span>
						</div>
						<div class="score-bars">
							<div class="bar-row"><span>5 sao</span><div class="bar-bg"><div class="bar-fill" style="width: 86%;"></div></div><span>86%</span></div>
							<div class="bar-row"><span>4 sao</span><div class="bar-bg"><div class="bar-fill" style="width: 11%;"></div></div><span>11%</span></div>
							<div class="bar-row"><span>3 sao</span><div class="bar-bg"><div class="bar-fill" style="width: 3%;"></div></div><span>3%</span></div>
							<div class="bar-row"><span>2 sao</span><div class="bar-bg"><div class="bar-fill" style="width: 0%;"></div></div><span>0%</span></div>
							<div class="bar-row"><span>1 sao</span><div class="bar-bg"><div class="bar-fill" style="width: 0%;"></div></div><span>0%</span></div>
						</div>
					</div>

					<div class="shoe-reviews-list">
						<?php if ( ! empty( $real_reviews ) ) : ?>
							<?php foreach ( $real_reviews as $rev ) :
								$rev_id     = $rev->comment_ID;
								$author     = get_comment_author( $rev_id );
								$rating_val = (int) get_comment_meta( $rev_id, 'rating', true );
								if ( $rating_val < 1 ) { $rating_val = 5; }
								$stars_str  = str_repeat( '★', $rating_val ) . str_repeat( '☆', 5 - $rating_val );
								$date_str   = get_comment_date( 'd/m/Y', $rev_id );
								$content    = get_comment_text( $rev_id );
								$verified   = function_exists( 'wc_customer_bought_product' ) && $product ? wc_customer_bought_product( $rev->comment_author_email, $rev->user_id, $product->get_id() ) : false;
								?>
								<div class="shoe-review-item">
									<div class="review-head">
										<strong><?php echo esc_html( $author ); ?></strong>
										<?php if ( $verified ) : ?>
											<span class="verified">✓ Đã mua hàng</span>
										<?php endif; ?>
										<span class="stars"><?php echo esc_html( $stars_str ); ?></span>
										<span class="date"><?php echo esc_html( $date_str ); ?></span>
									</div>
									<p><?php echo wp_kses_post( $content ); ?></p>
								</div>
							<?php endforeach; ?>
						<?php else : ?>
							<div class="shoe-review-item">
								<div class="review-head">
									<strong>Trần Quang Minh</strong>
									<span class="verified">✓ Đã mua hàng (Size 41)</span>
									<span class="stars">★★★★★</span>
									<span class="date">3 ngày trước</span>
								</div>
								<p>Giày đệm rất êm và nhẹ hơn mình nghĩ. Đã chạy thử 8km vòng hồ Tây buổi sáng, không bị cọ xát gót hay mỏi gan bàn chân. Phối màu đỏ bên ngoài đẹp hơn ảnh chụp.</p>
							</div>
							<div class="shoe-review-item">
								<div class="review-head">
									<strong>Lê Thuỳ Dung</strong>
									<span class="verified">✓ Đã mua hàng (Size 39)</span>
									<span class="stars">★★★★★</span>
									<span class="date">1 tuần trước</span>
								</div>
								<p>Form giày ôm vừa vặn, đế bám sàn tốt. Nhân viên tư vấn nhiệt tình, giao hàng từ TP.HCM ra Hà Nội chỉ mất 2 ngày. Rất hài lòng!</p>
							</div>
							<div class="shoe-review-item">
								<div class="review-head">
									<strong>Nguyễn Hoàng Nam</strong>
									<span class="verified">✓ Đã mua hàng (Size 42)</span>
									<span class="stars">★★★★★</span>
									<span class="date">2 tuần trước</span>
								</div>
								<p>Quét mã VietQR SePay thanh toán tự động trong 3 giây là đơn hàng xác nhận ngay, rất tiện lợi và an toàn. Giày đi bộ cả ngày làm việc vẫn thoải mái.</p>
							</div>
						<?php endif; ?>
					</div>

					<!-- REVIEW FORM -->
					<div class="shoe-review-form-box">
						<h4>Gửi đánh giá của bạn</h4>
						<?php if ( $product && comments_open( $product->get_id() ) ) : ?>
							<form action="<?php echo esc_url( site_url( '/wp-comments-post.php' ) ); ?>" method="post">
								<input type="hidden" name="comment_post_ID" value="<?php echo esc_attr( $product->get_id() ); ?>">
								<div class="form-row">
									<input type="text" name="author" placeholder="Họ và tên của bạn *" required>
									<input type="email" name="email" placeholder="Địa chỉ email *" required>
								</div>
								<div class="form-row">
									<select name="rating" aria-label="Đánh giá số sao" required>
										<option value="5">★★★★★ - Tuyệt vời (5/5 sao)</option>
										<option value="4">★★★★☆ - Hài lòng (4/5 sao)</option>
										<option value="3">★★★☆☆ - Bình thường (3/5 sao)</option>
										<option value="2">★★☆☆☆ - Chưa tốt (2/5 sao)</option>
										<option value="1">★☆☆☆☆ - Thất vọng (1/5 sao)</option>
									</select>
								</div>
								<div class="form-row">
									<textarea name="comment" rows="4" placeholder="Chia sẻ cảm nhận chi tiết của bạn về kích thước, độ êm, phong cách và dịch vụ giao hàng..." required></textarea>
								</div>
								<button type="submit" class="shoe-button shoe-button-dark">Gửi nhận xét ngay　→</button>
							</form>
						<?php else : ?>
							<form onsubmit="event.preventDefault(); alert('Cảm ơn bạn đã gửi đánh giá! Nhận xét sẽ được kiểm duyệt và hiển thị sớm nhất.'); this.reset();">
								<div class="form-row">
									<input type="text" placeholder="Họ và tên của bạn *" required>
									<input type="email" placeholder="Địa chỉ email *" required>
								</div>
								<div class="form-row">
									<select aria-label="Đánh giá số sao" required>
										<option value="5">★★★★★ - Tuyệt vời (5/5 sao)</option>
										<option value="4">★★★★☆ - Hài lòng (4/5 sao)</option>
										<option value="3">★★★☆☆ - Bình thường (3/5 sao)</option>
										<option value="2">★★☆☆☆ - Chưa tốt (2/5 sao)</option>
										<option value="1">★☆☆☆☆ - Thất vọng (1/5 sao)</option>
									</select>
								</div>
								<div class="form-row">
									<textarea rows="4" placeholder="Chia sẻ cảm nhận chi tiết của bạn về kích thước, độ êm, phong cách và dịch vụ giao hàng..." required></textarea>
								</div>
								<button type="submit" class="shoe-button shoe-button-dark">Gửi nhận xét ngay　→</button>
							</form>
						<?php endif; ?>
					</div>

				</div>
			</div>
		</div>

		<!-- RELATED PRODUCTS (DỮ LIỆU THẬT TỪ WOOCOMMERCE) -->
		<section class="shoe-section shoe-detail-related">
			<div class="shoe-section-head">
				<h2>Có thể bạn cũng thích</h2>
				<a class="shoe-text-link" href="<?php echo esc_url( $shop_url ); ?>">Xem tất cả sản phẩm　→</a>
			</div>
			<div class="shoe-product-grid">
				<?php if ( ! empty( $related_products_list ) ) : ?>
					<?php foreach ( $related_products_list as $rel_p ) :
						$r_id      = $rel_p->get_id();
						$r_title   = $rel_p->get_name();
						$r_url     = $rel_p->get_permalink();
						$r_price   = $rel_p->get_price_html();
						$r_img_id  = $rel_p->get_image_id();
						$r_img_url = $r_img_id ? wp_get_attachment_image_url( $r_img_id, 'medium_large' ) : 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=650&q=80';

						$r_terms    = get_the_terms( $r_id, 'product_cat' );
						$r_cat_name = ( ! empty( $r_terms ) && ! is_wp_error( $r_terms ) ) ? $r_terms[0]->name : 'Giày thể thao · Nam/Nữ';

						$badge_html = '';
						if ( $rel_p->is_on_sale() ) {
							$reg  = (float) $rel_p->get_regular_price();
							$sale = (float) $rel_p->get_sale_price();
							if ( $reg > 0 && $sale > 0 && $reg > $sale ) {
								$pct        = round( ( ( $reg - $sale ) / $reg ) * 100 );
								$badge_html = '<span class="shoe-badge">-' . esc_html( $pct ) . '%</span>';
							} else {
								$badge_html = '<span class="shoe-badge">Giảm giá</span>';
							}
						} elseif ( $rel_p->is_featured() ) {
							$badge_html = '<span class="shoe-badge">Nổi bật</span>';
						} elseif ( ( time() - get_the_time( 'U', $r_id ) ) < ( 30 * DAY_IN_SECONDS ) ) {
							$badge_html = '<span class="shoe-badge">Mới</span>';
						}
						?>
						<a class="shoe-product" href="<?php echo esc_url( $r_url ); ?>">
							<div class="shoe-product-image">
								<?php echo $badge_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<img src="<?php echo esc_url( $r_img_url ); ?>" alt="<?php echo esc_attr( $r_title ); ?>">
							</div>
							<div class="shoe-product-info">
								<div class="shoe-product-title"><?php echo esc_html( $r_title ); ?></div>
								<div class="shoe-product-type"><?php echo esc_html( $r_cat_name ); ?></div>
								<div class="shoe-price"><?php echo wp_kses_post( $r_price ); ?></div>
							</div>
						</a>
					<?php endforeach; ?>
				<?php else : ?>
					<a class="shoe-product" href="<?php echo esc_url( $shop_url ); ?>">
						<div class="shoe-product-image">
							<span class="shoe-badge">Bán chạy</span>
							<img src="https://images.unsplash.com/photo-1495555961986-6d4c1ecb7be3?auto=format&fit=crop&w=650&q=80" alt="Daily Court Classic">
						</div>
						<div class="shoe-product-info">
							<div class="shoe-product-title">Daily Court Classic</div>
							<div class="shoe-product-type">Giày lifestyle · Unisex</div>
							<div class="shoe-price">1.590.000₫</div>
						</div>
					</a>
					<a class="shoe-product" href="<?php echo esc_url( $shop_url ); ?>">
						<div class="shoe-product-image">
							<img src="https://images.unsplash.com/photo-1460353581641-37baddab0fa2?auto=format&fit=crop&w=650&q=80" alt="Aero Pace Pro">
						</div>
						<div class="shoe-product-info">
							<div class="shoe-product-title">Aero Pace Pro</div>
							<div class="shoe-product-type">Giày chạy marathon · Nam/Nữ</div>
							<div class="shoe-price">2.490.000₫</div>
						</div>
					</a>
					<a class="shoe-product" href="<?php echo esc_url( $shop_url ); ?>">
						<div class="shoe-product-image">
							<span class="shoe-badge">Bộ sưu tập mới</span>
							<img src="https://images.unsplash.com/photo-1552346154-21d32810aba3?auto=format&fit=crop&w=650&q=80" alt="Street Form 02">
						</div>
						<div class="shoe-product-info">
							<div class="shoe-product-title">Street Form 02</div>
							<div class="shoe-product-type">Giày lifestyle đường phố</div>
							<div class="shoe-price">1.790.000₫</div>
						</div>
					</a>
					<a class="shoe-product" href="<?php echo esc_url( $shop_url ); ?>">
						<div class="shoe-product-image">
							<img src="https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?auto=format&fit=crop&w=650&q=80" alt="Glide Flow Neon">
						</div>
						<div class="shoe-product-info">
							<div class="shoe-product-title">Glide Flow Neon</div>
							<div class="shoe-product-type">Giày tập luyện đa năng</div>
							<div class="shoe-price">1.650.000₫</div>
						</div>
					</a>
				<?php endif; ?>
			</div>
		</section>

	</div>
</main>

<!-- SIZE GUIDE MODAL POPUP -->
<div class="shoe-modal" id="shoeSizeModal" role="dialog" aria-modal="true" aria-labelledby="shoeModalTitle">
	<div class="shoe-modal-backdrop" id="shoeModalBackdrop"></div>
	<div class="shoe-modal-dialog">
		<div class="shoe-modal-header">
			<h3 id="shoeModalTitle">Bảng kích thước & Hướng dẫn đo size</h3>
			<button type="button" class="shoe-modal-close" id="shoeModalClose" aria-label="Đóng bảng size">✕</button>
		</div>
		<div class="shoe-modal-body">
			<p>Nếu bạn phân vân giữa 2 kích cỡ liền kề, hãy chọn size lớn hơn 0.5 cỡ để các ngón chân có không gian cử động thoải mái nhất khi chạy.</p>
			<table class="shoe-size-table">
				<thead>
					<tr>
						<th>Chiều dài chân (cm)</th>
						<th>Size EU/VN</th>
						<th>Size US Nam</th>
						<th>Size US Nữ</th>
					</tr>
				</thead>
				<tbody>
					<tr><td>23.5 - 24.0 cm</td><td><strong>38</strong></td><td>6.0</td><td>7.5</td></tr>
					<tr><td>24.1 - 24.5 cm</td><td><strong>39</strong></td><td>6.5</td><td>8.0</td></tr>
					<tr><td>24.6 - 25.0 cm</td><td><strong>40</strong></td><td>7.0</td><td>8.5</td></tr>
					<tr class="highlight"><td>25.1 - 25.5 cm</td><td><strong>41</strong> (Phổ biến)</td><td>8.0</td><td>9.5</td></tr>
					<tr><td>25.6 - 26.0 cm</td><td><strong>42</strong></td><td>8.5</td><td>10.0</td></tr>
					<tr><td>26.1 - 26.5 cm</td><td><strong>43</strong></td><td>9.5</td><td>11.0</td></tr>
					<tr><td>26.6 - 27.0 cm</td><td><strong>44</strong></td><td>10.0</td><td>11.5</td></tr>
				</tbody>
			</table>
		</div>
	</div>
</div>

<!-- TOAST ALERT -->
<div class="shoe-toast" id="shoeToast" role="alert" aria-live="polite">
	<div class="shoe-toast-body">
		<span class="shoe-toast-icon">✓</span>
		<div class="shoe-toast-text">
			<strong id="shoeToastTitle">Đã thêm vào giỏ hàng!</strong>
			<span id="shoeToastDesc">1 x <?php echo esc_html( $title ); ?></span>
		</div>
		<a href="<?php echo esc_url( $cart_url ); ?>" class="shoe-toast-btn">Xem giỏ hàng →</a>
	</div>
</div>

<!-- INTERACTIVE SCRIPTS -->
<script>
document.addEventListener('DOMContentLoaded', function () {
	// 1. Gallery Thumbnail Switcher
	const mainImg = document.getElementById('shoeMainImage');
	const thumbs = document.querySelectorAll('.shoe-thumb-btn');
	thumbs.forEach(btn => {
		btn.addEventListener('click', function () {
			thumbs.forEach(b => b.classList.remove('is-active'));
			this.classList.add('is-active');
			const targetUrl = this.getAttribute('data-full');
			if (mainImg && targetUrl) {
				mainImg.style.opacity = '0.35';
				setTimeout(() => {
					mainImg.src = targetUrl;
					mainImg.style.opacity = '1';
				}, 120);
			}
		});
	});

	// 2. Size Picker
	let selectedSize = '41';
	const sizePills = document.querySelectorAll('.shoe-size-pill');
	sizePills.forEach(pill => {
		pill.addEventListener('click', function () {
			sizePills.forEach(p => p.classList.remove('is-selected'));
			this.classList.add('is-selected');
			selectedSize = this.getAttribute('data-size');
		});
	});

	// 3. Color Picker
	const colorPills = document.querySelectorAll('.shoe-color-pill');
	const selectedColorLabel = document.getElementById('shoeSelectedColor');
	colorPills.forEach(pill => {
		pill.addEventListener('click', function () {
			colorPills.forEach(p => p.classList.remove('is-selected'));
			this.classList.add('is-selected');
			const colorName = this.getAttribute('data-color');
			if (selectedColorLabel && colorName) {
				selectedColorLabel.textContent = colorName;
			}
		});
	});

	// 4. Quantity Stepper
	const qtyInput = document.getElementById('shoeQtyInput');
	const qtyMinus = document.getElementById('qtyMinus');
	const qtyPlus = document.getElementById('qtyPlus');
	if (qtyMinus && qtyInput) {
		qtyMinus.addEventListener('click', function () {
			let val = parseInt(qtyInput.value, 10) || 1;
			if (val > 1) qtyInput.value = val - 1;
		});
	}
	if (qtyPlus && qtyInput) {
		qtyPlus.addEventListener('click', function () {
			let val = parseInt(qtyInput.value, 10) || 1;
			if (val < 99) qtyInput.value = val + 1;
		});
	}

	// 5. Tabs
	const tabBtns = document.querySelectorAll('.shoe-tab-btn');
	const tabPanels = document.querySelectorAll('.shoe-tab-panel');
	tabBtns.forEach(btn => {
		btn.addEventListener('click', function () {
			const targetId = this.getAttribute('data-tab');
			tabBtns.forEach(b => b.classList.remove('is-active'));
			tabPanels.forEach(p => p.classList.remove('is-active'));
			this.classList.add('is-active');
			const panel = document.getElementById(targetId);
			if (panel) panel.classList.add('is-active');
		});
	});

	// 6. Size Guide Modal
	const openModalBtn = document.getElementById('shoeOpenSizeGuide');
	const modal = document.getElementById('shoeSizeModal');
	const closeModalBtn = document.getElementById('shoeModalClose');
	const modalBackdrop = document.getElementById('shoeModalBackdrop');
	function openModal() { if (modal) modal.classList.add('is-open'); }
	function closeModal() { if (modal) modal.classList.remove('is-open'); }
	if (openModalBtn) openModalBtn.addEventListener('click', openModal);
	if (closeModalBtn) closeModalBtn.addEventListener('click', closeModal);
	if (modalBackdrop) modalBackdrop.addEventListener('click', closeModal);
	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') closeModal();
	});

	// 7. Toast Add to Cart Demo
	const addCartBtn = document.getElementById('shoeBtnAddToCart');
	const toast = document.getElementById('shoeToast');
	const toastDesc = document.getElementById('shoeToastDesc');
	let toastTimer = null;
	if (addCartBtn && toast) {
		addCartBtn.addEventListener('click', function () {
			const qty = qtyInput ? qtyInput.value : 1;
			if (toastDesc) {
				toastDesc.textContent = qty + ' x <?php echo esc_js( $title ); ?> (Size ' + selectedSize + ')';
			}
			toast.classList.add('is-visible');
			clearTimeout(toastTimer);
			toastTimer = setTimeout(() => {
				toast.classList.remove('is-visible');
			}, 4200);
		});
	}
});
</script>

<?php
get_footer();

