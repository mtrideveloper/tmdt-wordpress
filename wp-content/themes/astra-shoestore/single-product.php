<?php
/**
 * Single Product Template — MotionLab / Astra Shoe Store
 * Mọi dữ liệu lấy từ WooCommerce / WordPress DB, không hardcode.
 */
defined( 'ABSPATH' ) || exit;

// Bắt buộc phải có WooCommerce và sản phẩm thật
if ( ! class_exists( 'WooCommerce' ) || ! is_singular( 'product' ) ) {
	wp_redirect( home_url( '/' ) );
	exit;
}

// =========================================================================
// 1. LẤY SẢN PHẨM VÀ DỮ LIỆU CƠ BẢN
// =========================================================================
global $product;
$product = wc_get_product( get_queried_object_id() );

if ( ! $product || ! $product->is_visible() ) {
	wp_redirect( wc_get_page_permalink( 'shop' ) );
	exit;
}

$prod_id     = $product->get_id();
$title       = $product->get_name();
$sku         = $product->get_sku();
$price_html  = $product->get_price_html();
$is_in_stock = $product->is_in_stock();
$short_desc  = $product->get_short_description();
$long_desc   = $product->get_description();
$permalink   = $product->get_permalink();

// Tồn kho
$stock_qty  = $product->get_stock_quantity();
$stock_text = $is_in_stock
	? ( $stock_qty ? sprintf( 'Còn %d sản phẩm trong kho', (int) $stock_qty ) : 'Còn hàng · Sẵn sàng giao' )
	: 'Hết hàng tạm thời';

// =========================================================================
// 2. DANH MỤC & TAG
// =========================================================================
$cat_terms = get_the_terms( $prod_id, 'product_cat' );
$cat_terms = ( ! empty( $cat_terms ) && ! is_wp_error( $cat_terms ) ) ? $cat_terms : array();
// Lọc bỏ Uncategorized
$cat_terms = array_filter( $cat_terms, function( $t ) {
	return 'uncategorized' !== $t->slug;
} );
$cat_term    = ! empty( $cat_terms ) ? reset( $cat_terms ) : null;
$cat_name    = $cat_term ? $cat_term->name : '';
$cat_url     = $cat_term ? get_term_link( $cat_term ) : wc_get_page_permalink( 'shop' );

$tag_terms = get_the_terms( $prod_id, 'product_tag' );
$tag_terms = ( ! empty( $tag_terms ) && ! is_wp_error( $tag_terms ) ) ? $tag_terms : array();

// =========================================================================
// 3. HÌNH ẢNH SẢN PHẨM
// =========================================================================
$images   = array();
$thumb_id = $product->get_image_id();
if ( $thumb_id ) {
	$images[] = array(
		'full'   => wp_get_attachment_image_url( $thumb_id, 'full' ),
		'medium' => wp_get_attachment_image_url( $thumb_id, 'woocommerce_single' ),
		'alt'    => trim( wp_strip_all_tags( get_post_meta( $thumb_id, '_wp_attachment_image_alt', true ) ) ) ?: $title,
	);
}
foreach ( $product->get_gallery_image_ids() as $gid ) {
	$url = wp_get_attachment_image_url( $gid, 'full' );
	if ( $url ) {
		$images[] = array(
			'full'   => $url,
			'medium' => wp_get_attachment_image_url( $gid, 'woocommerce_single' ),
			'alt'    => trim( wp_strip_all_tags( get_post_meta( $gid, '_wp_attachment_image_alt', true ) ) ) ?: $title,
		);
	}
}

// =========================================================================
// 4. ĐÁNH GIÁ / REVIEWS
// =========================================================================
$avg_rating  = (float) $product->get_average_rating();
$review_cnt  = (int) $product->get_review_count();

// Tính phân bổ sao từ DB
$rating_counts = array( 5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0 );
$real_reviews  = array();
if ( $review_cnt > 0 ) {
	$real_reviews = get_comments( array(
		'post_id' => $prod_id,
		'status'  => 'approve',
		'type'    => 'review',
		'number'  => 20,
		'orderby' => 'comment_date',
		'order'   => 'DESC',
	) );
	foreach ( $real_reviews as $rv ) {
		$r = (int) get_comment_meta( $rv->comment_ID, 'rating', true );
		if ( $r >= 1 && $r <= 5 ) {
			$rating_counts[ $r ]++;
		}
	}
}

// =========================================================================
// 5. THUỘC TÍNH SẢN PHẨM (SIZE, MÀU SẮC, v.v.)
// =========================================================================
$attributes = $product->get_attributes();

// Tìm size attribute
$size_options = array();
foreach ( $attributes as $attr ) {
	$attr_name_lower = strtolower( wc_attribute_label( $attr->get_name() ) );
	if ( false !== strpos( $attr_name_lower, 'size' ) || false !== strpos( $attr_name_lower, 'kích cỡ' ) || false !== strpos( $attr_name_lower, 'size' ) || $attr->get_name() === 'pa_size' ) {
		$size_options = $attr->is_taxonomy()
			? wc_get_product_terms( $prod_id, $attr->get_name(), array( 'fields' => 'names' ) )
			: $attr->get_options();
		break;
	}
}

// Tìm màu attribute
$color_options = array();
foreach ( $attributes as $attr ) {
	$attr_name_lower = strtolower( wc_attribute_label( $attr->get_name() ) );
	if ( false !== strpos( $attr_name_lower, 'color' ) || false !== strpos( $attr_name_lower, 'màu' ) || false !== strpos( $attr_name_lower, 'colour' ) || $attr->get_name() === 'pa_color' || $attr->get_name() === 'pa_colour' || $attr->get_name() === 'pa_mau' ) {
		$color_options = $attr->is_taxonomy()
			? wc_get_product_terms( $prod_id, $attr->get_name(), array( 'fields' => 'names' ) )
			: $attr->get_options();
		break;
	}
}

// =========================================================================
// 6. THÔNG SỐ KỸ THUẬT — Từ custom fields / product attributes
// =========================================================================
// Lấy tất cả attributes để hiển thị trong tab Thông số
$spec_rows = array();
foreach ( $attributes as $attr ) {
	if ( ! $attr->get_visible() ) continue; // chỉ hiển thị attribute được đánh dấu visible
	$label = wc_attribute_label( $attr->get_name() );
	$vals  = $attr->is_taxonomy()
		? implode( ', ', wc_get_product_terms( $prod_id, $attr->get_name(), array( 'fields' => 'names' ) ) )
		: implode( ', ', $attr->get_options() );
	if ( $vals ) {
		$spec_rows[ $label ] = $vals;
	}
}
// Thêm trọng lượng & kích thước nếu có
if ( $product->get_weight() ) {
	$spec_rows[ 'Trọng lượng' ] = wc_format_weight( $product->get_weight() );
}
if ( $product->has_dimensions() ) {
	$spec_rows[ 'Kích thước (D×R×C)' ] = wc_format_dimensions( $product->get_dimensions( false ) );
}
if ( $sku ) {
	$spec_rows[ 'Mã sản phẩm (SKU)' ] = $sku;
}

// =========================================================================
// 7. SẢN PHẨM LIÊN QUAN — WooCommerce native + fallback
// =========================================================================
$related_ids = wc_get_related_products( $prod_id, 4 );
$related_products = array();
foreach ( $related_ids as $rid ) {
	$rp = wc_get_product( $rid );
	if ( $rp && $rp->is_visible() ) {
		$related_products[] = $rp;
	}
}
// Nếu chưa đủ 4, lấy thêm sản phẩm bất kỳ
if ( count( $related_products ) < 4 ) {
	$exclude_ids = array_merge( array( $prod_id ), $related_ids );
	$extra = wc_get_products( array(
		'status'     => 'publish',
		'limit'      => 4 - count( $related_products ),
		'exclude'    => $exclude_ids,
		'orderby'    => 'rand',
		'visibility' => 'catalog',
	) );
	$related_products = array_merge( $related_products, $extra );
}

// =========================================================================
// 8. URL ĐIỀU HƯỚNG
// =========================================================================
$shop_url     = wc_get_page_permalink( 'shop' );
$cart_url     = wc_get_cart_url();
$checkout_url = wc_get_checkout_url();

// =========================================================================
// 9. BẢNG SIZE — Lấy từ trang WordPress có slug 'bang-size'
// =========================================================================
$size_guide_page = get_page_by_path( 'bang-size', OBJECT, 'page' );

// =========================================================================
// 9. BADGE SẢN PHẨM — Từ dữ liệu thật
// =========================================================================
$badge_text = '';
if ( $product->is_on_sale() ) {
	$reg  = (float) $product->get_regular_price();
	$sale = (float) $product->get_sale_price();
	if ( $reg > 0 && $sale > 0 ) {
		$pct        = round( ( ( $reg - $sale ) / $reg ) * 100 );
		$badge_text = '-' . $pct . '%';
	} else {
		$badge_text = 'Sale';
	}
} elseif ( $product->is_featured() ) {
	$badge_text = 'Nổi bật';
} elseif ( ( time() - get_the_time( 'U', $prod_id ) ) < 30 * DAY_IN_SECONDS ) {
	$badge_text = 'Mới';
}

get_header();
?>

<main class="shoe-store-page shoe-detail-page">
<div class="shoe-detail-container">

	<?php wc_print_notices(); ?>

	<!-- BREADCRUMBS -->
	<nav class="shoe-breadcrumbs" aria-label="Đường dẫn">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Trang chủ</a>
		<span class="sep">/</span>
		<a href="<?php echo esc_url( $shop_url ); ?>">Cửa hàng</a>
		<?php if ( $cat_term ) : ?>
			<span class="sep">/</span>
			<a href="<?php echo esc_url( is_wp_error( $cat_url ) ? $shop_url : $cat_url ); ?>"><?php echo esc_html( $cat_term->name ); ?></a>
		<?php endif; ?>
		<span class="sep">/</span>
		<span class="current"><?php echo esc_html( $title ); ?></span>
	</nav>

	<!-- MAIN PRODUCT CARD -->
	<div class="shoe-detail-card">
		<div class="shoe-detail-grid">

			<!-- LEFT: IMAGE GALLERY -->
			<div class="shoe-detail-gallery">
				<div class="shoe-gallery-main">
					<?php if ( $badge_text ) : ?>
						<span class="shoe-badge"><?php echo esc_html( $badge_text ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $images ) ) : ?>
						<img id="shoeMainImage"
							src="<?php echo esc_url( $images[0]['full'] ); ?>"
							alt="<?php echo esc_attr( $images[0]['alt'] ); ?>">
					<?php else : ?>
						<img id="shoeMainImage"
							src="<?php echo esc_url( wc_placeholder_img_src( 'woocommerce_single' ) ); ?>"
							alt="<?php echo esc_attr( $title ); ?>">
					<?php endif; ?>
					<span class="shoe-gallery-hint">⌕ Rê chuột hoặc chạm để xem chi tiết</span>
				</div>

				<?php if ( count( $images ) > 1 ) : ?>
					<div class="shoe-gallery-thumbs" id="shoeThumbs">
						<?php foreach ( $images as $idx => $img ) : ?>
							<button type="button"
								class="shoe-thumb-btn <?php echo 0 === $idx ? 'is-active' : ''; ?>"
								data-full="<?php echo esc_url( $img['full'] ); ?>"
								aria-label="Xem ảnh <?php echo esc_attr( $idx + 1 ); ?>">
								<img src="<?php echo esc_url( $img['medium'] ?: $img['full'] ); ?>" alt="<?php echo esc_attr( $img['alt'] ); ?>">
							</button>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<!-- RIGHT: PRODUCT SUMMARY -->
			<div class="shoe-detail-summary">

				<p class="shoe-eyebrow">
					<?php echo esc_html( get_bloginfo( 'name' ) ); ?>
					<?php if ( $cat_name ) : ?>
						· <?php echo esc_html( $cat_name ); ?>
					<?php endif; ?>
				</p>

				<h1 class="shoe-detail-title"><?php echo esc_html( $title ); ?></h1>

				<div class="shoe-detail-meta-row">
					<?php if ( $avg_rating > 0 ) : ?>
						<div class="shoe-rating-stars" title="<?php echo esc_attr( number_format( $avg_rating, 1 ) ); ?>/5 sao">
							<?php
							$full  = floor( $avg_rating );
							$half  = ( $avg_rating - $full ) >= 0.5 ? 1 : 0;
							$empty = 5 - $full - $half;
							echo str_repeat( '★', $full );
							if ( $half ) echo '½';
							echo str_repeat( '☆', $empty );
							?>
							<strong class="score"><?php echo esc_html( number_format( $avg_rating, 1 ) ); ?></strong>
							<a href="#tabReviews" class="review-link" id="shoeTabReviewsLink">
								(<?php echo esc_html( $review_cnt ); ?> đánh giá)
							</a>
						</div>
						<span class="meta-dot">·</span>
					<?php endif; ?>

					<?php if ( $sku ) : ?>
						<div class="shoe-sku-tag">Mã: <strong><?php echo esc_html( $sku ); ?></strong></div>
						<span class="meta-dot">·</span>
					<?php endif; ?>

					<div class="shoe-stock-badge <?php echo $is_in_stock ? 'in-stock' : 'out-of-stock'; ?>">
						● <?php echo esc_html( $stock_text ); ?>
					</div>
				</div>

				<div class="shoe-detail-price-wrap">
					<?php echo wp_kses_post( $price_html ); ?>
				</div>

				<?php if ( $short_desc ) : ?>
					<div class="shoe-detail-desc">
						<?php echo wp_kses_post( $short_desc ); ?>
					</div>
				<?php endif; ?>

				<!-- SIZE SELECTOR — từ attributes -->
				<?php if ( ! empty( $size_options ) ) : ?>
					<div class="shoe-selector-block">
						<div class="shoe-selector-head">
							<label class="shoe-selector-label">Chọn kích cỡ:</label>
							<button type="button" class="shoe-size-guide-link" id="shoeOpenSizeGuide">Bảng đo size ↗</button>
						</div>
						<div class="shoe-size-options" id="shoeSizeOptions">
							<?php foreach ( $size_options as $i => $sz ) : ?>
								<button type="button"
									class="shoe-size-pill <?php echo 0 === $i ? 'is-selected' : ''; ?>"
									data-size="<?php echo esc_attr( $sz ); ?>">
									<?php echo esc_html( $sz ); ?>
								</button>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>

				<!-- COLOR SELECTOR — từ attributes -->
				<?php if ( ! empty( $color_options ) ) : ?>
					<div class="shoe-selector-block">
						<label class="shoe-selector-label">
							Phối màu: <strong id="shoeSelectedColor"><?php echo esc_html( $color_options[0] ); ?></strong>
						</label>
						<div class="shoe-color-options" id="shoeColorOptions">
							<?php foreach ( $color_options as $i => $col ) : ?>
								<button type="button"
									class="shoe-color-pill <?php echo 0 === $i ? 'is-selected' : ''; ?>"
									data-color="<?php echo esc_attr( $col ); ?>"
									title="<?php echo esc_attr( $col ); ?>">
									<span class="name"><?php echo esc_html( $col ); ?></span>
								</button>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>

				<!-- ADD TO CART — WooCommerce native -->
				<div class="shoe-wc-add-to-cart-wrapper">
					<?php
					// Setup global post for WooCommerce hooks
					global $post;
					$post = get_post( $prod_id );
					setup_postdata( $post );
					woocommerce_template_single_add_to_cart();
					wp_reset_postdata();
					?>
				</div>

				<!-- MUA NGAY — Buy Now shortcut -->
				<?php if ( $is_in_stock ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'buy_now', $prod_id, $checkout_url ) ); ?>"
					   class="shoe-button shoe-button-buy-now">
						Mua ngay · Thanh toán SePay QR <span>→</span>
					</a>
				<?php endif; ?>

				<!-- TRUST PERKS — cố định, thông tin chính sách cửa hàng -->
				<div class="shoe-detail-perks">
					<div class="shoe-perk-item">
						<span class="icon">🚚</span>
						<div>
							<strong>Miễn phí giao hàng</strong>
							<span>Đơn từ 1.500.000₫ toàn quốc, nhận hàng 2–4 ngày</span>
						</div>
					</div>
					<div class="shoe-perk-item">
						<span class="icon">🔄</span>
						<div>
							<strong>Đổi trả 30 ngày</strong>
							<span>Miễn phí đổi size nếu không vừa chân</span>
						</div>
					</div>
					<div class="shoe-perk-item">
						<span class="icon">🛡️</span>
						<div>
							<strong>Chính hãng 100%</strong>
							<span>Bảo hành keo chỉ 12 tháng toàn hệ thống</span>
						</div>
					</div>
					<div class="shoe-perk-item">
						<span class="icon">⚡</span>
						<div>
							<strong>VietQR / SePay tức thì</strong>
							<span>Xác nhận đơn hàng tự động trong vài giây</span>
						</div>
					</div>
				</div>

			</div><!-- /.shoe-detail-summary -->
		</div><!-- /.shoe-detail-grid -->
	</div><!-- /.shoe-detail-card -->

	<!-- TABS -->
	<div class="shoe-detail-tabs-section" id="shoeDetailTabs">
		<div class="shoe-tabs-nav">
			<button type="button" class="shoe-tab-btn is-active" data-tab="tabDesc">Mô tả sản phẩm</button>
			<?php if ( ! empty( $spec_rows ) ) : ?>
				<button type="button" class="shoe-tab-btn" data-tab="tabSpecs">Thông số kỹ thuật</button>
			<?php endif; ?>
			<button type="button" class="shoe-tab-btn" data-tab="tabSize">Bảng chọn size</button>
			<button type="button" class="shoe-tab-btn" data-tab="tabReviews" id="shoeTabReviews">
				Đánh giá<?php if ( $review_cnt > 0 ) : ?> (<?php echo esc_html( $review_cnt ); ?>)<?php endif; ?>
			</button>
		</div>

		<!-- TAB: Mô tả -->
		<div class="shoe-tab-panel is-active" id="tabDesc">
			<div class="shoe-desc-content">
				<?php if ( $long_desc ) : ?>
					<?php echo wp_kses_post( apply_filters( 'the_content', $long_desc ) ); ?>
				<?php elseif ( $short_desc ) : ?>
					<?php echo wp_kses_post( $short_desc ); ?>
				<?php else : ?>
					<p>Chưa có mô tả chi tiết cho sản phẩm này.</p>
				<?php endif; ?>
			</div>
		</div>

		<!-- TAB: Thông số -->
		<?php if ( ! empty( $spec_rows ) ) : ?>
			<div class="shoe-tab-panel" id="tabSpecs">
				<table class="shoe-specs-table">
					<tbody>
						<?php foreach ( $spec_rows as $label => $value ) : ?>
							<tr>
								<th><?php echo esc_html( $label ); ?></th>
								<td><?php echo wp_kses_post( $value ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>

		<!-- TAB: Bảng size -->
		<div class="shoe-tab-panel" id="tabSize">
			<div class="shoe-size-guide-content">
				<?php
				// $size_guide_page đã được fetch ở đầu file (section 9)
				if ( $size_guide_page && $size_guide_page->post_content ) :
					echo wp_kses_post( apply_filters( 'the_content', $size_guide_page->post_content ) );
				else :
				?>
				<h3>Bảng quy đổi kích thước giày chuẩn quốc tế</h3>
				<p>Để chọn được đôi giày vừa vặn, hãy đo chiều dài bàn chân vào buổi chiều hoặc tối.</p>
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
							<tr><td>23.5 – 24.0 cm</td><td><strong>38</strong></td><td>6.0</td><td>7.5</td><td>5.5</td></tr>
							<tr><td>24.1 – 24.5 cm</td><td><strong>39</strong></td><td>6.5</td><td>8.0</td><td>6.0</td></tr>
							<tr><td>24.6 – 25.0 cm</td><td><strong>40</strong></td><td>7.0</td><td>8.5</td><td>6.5</td></tr>
							<tr class="highlight"><td>25.1 – 25.5 cm</td><td><strong>41</strong></td><td>8.0</td><td>9.5</td><td>7.5</td></tr>
							<tr><td>25.6 – 26.0 cm</td><td><strong>42</strong></td><td>8.5</td><td>10.0</td><td>8.0</td></tr>
							<tr><td>26.1 – 26.5 cm</td><td><strong>43</strong></td><td>9.5</td><td>11.0</td><td>9.0</td></tr>
							<tr><td>26.6 – 27.0 cm</td><td><strong>44</strong></td><td>10.0</td><td>11.5</td><td>9.5</td></tr>
						</tbody>
					</table>
				</div>
				<div class="shoe-measure-steps">
					<h4>Cách đo chân tại nhà:</h4>
					<ol>
						<li>Đặt chân lên tờ giấy A4 áp sát tường, đứng thẳng tự nhiên.</li>
						<li>Dùng bút đánh dấu điểm dài nhất của đầu ngón chân.</li>
						<li>Đo từ mép giấy đến vạch, cộng thêm 0.5 cm nếu mang tất dày.</li>
					</ol>
				</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- TAB: Đánh giá -->
		<div class="shoe-tab-panel" id="tabReviews">
			<div class="shoe-reviews-wrap">

				<!-- Tổng quan đánh giá -->
				<?php if ( $avg_rating > 0 ) : ?>
					<div class="shoe-reviews-summary">
						<div class="score-box">
							<span class="big-score"><?php echo esc_html( number_format( $avg_rating, 1 ) ); ?></span>
							<span class="stars">
								<?php
								$f = floor( $avg_rating );
								$h = ( $avg_rating - $f ) >= 0.5 ? 1 : 0;
								$e = 5 - $f - $h;
								echo str_repeat( '★', $f ) . ( $h ? '½' : '' ) . str_repeat( '☆', $e );
								?>
							</span>
							<span class="count">Dựa trên <?php echo esc_html( $review_cnt ); ?> đánh giá</span>
						</div>
						<div class="score-bars">
							<?php foreach ( array( 5, 4, 3, 2, 1 ) as $star ) :
								$cnt = $rating_counts[ $star ];
								$pct = $review_cnt > 0 ? round( ( $cnt / $review_cnt ) * 100 ) : 0;
							?>
								<div class="bar-row">
									<span><?php echo esc_html( $star ); ?> sao</span>
									<div class="bar-bg"><div class="bar-fill" style="width: <?php echo esc_attr( $pct ); ?>%;"></div></div>
									<span><?php echo esc_html( $pct ); ?>%</span>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>

				<!-- Danh sách bình luận thật -->
				<?php if ( ! empty( $real_reviews ) ) : ?>
					<div class="shoe-reviews-list">
						<?php foreach ( $real_reviews as $rv ) :
							$rv_id      = $rv->comment_ID;
							$rv_author  = get_comment_author( $rv_id );
							$rv_rating  = (int) get_comment_meta( $rv_id, 'rating', true );
							$rv_stars   = $rv_rating > 0 ? ( str_repeat( '★', $rv_rating ) . str_repeat( '☆', 5 - $rv_rating ) ) : '';
							$rv_date    = get_comment_date( 'd/m/Y', $rv_id );
							$rv_content = get_comment_text( $rv_id );
							$rv_verified = function_exists( 'wc_customer_bought_product' )
								? wc_customer_bought_product( $rv->comment_author_email, (int) $rv->user_id, $prod_id )
								: false;
						?>
							<div class="shoe-review-item">
								<div class="review-head">
									<strong><?php echo esc_html( $rv_author ); ?></strong>
									<?php if ( $rv_verified ) : ?>
										<span class="verified">✓ Đã mua hàng</span>
									<?php endif; ?>
									<?php if ( $rv_stars ) : ?>
										<span class="stars"><?php echo esc_html( $rv_stars ); ?></span>
									<?php endif; ?>
									<span class="date"><?php echo esc_html( $rv_date ); ?></span>
								</div>
								<p><?php echo wp_kses_post( $rv_content ); ?></p>
							</div>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<p class="shoe-no-reviews">Chưa có đánh giá nào. Hãy là người đầu tiên chia sẻ cảm nhận!</p>
				<?php endif; ?>

				<!-- Form gửi đánh giá — WooCommerce native -->
				<?php if ( comments_open( $prod_id ) ) : ?>
					<div class="shoe-review-form-box">
						<h4>Gửi đánh giá của bạn</h4>
						<?php
						$commenter = wp_get_current_commenter();
						$user      = wp_get_current_user();
						?>
						<form action="<?php echo esc_url( site_url( '/wp-comments-post.php' ) ); ?>" method="post" id="shoeReviewForm">
							<input type="hidden" name="comment_post_ID" value="<?php echo esc_attr( $prod_id ); ?>">
							<input type="hidden" name="comment_type" value="review">
							<?php wp_nonce_field( 'woocommerce-comment-rating', 'woo_comment_nonce', false ); ?>

							<div class="form-row">
								<?php if ( ! $user->exists() ) : ?>
									<input type="text" name="author" value="<?php echo esc_attr( $commenter['comment_author'] ); ?>" placeholder="Họ và tên *" required>
									<input type="email" name="email" value="<?php echo esc_attr( $commenter['comment_author_email'] ); ?>" placeholder="Email *" required>
								<?php else : ?>
									<p class="shoe-logged-in-as">
										<?php printf( 'Đang đánh giá với tên <strong>%s</strong>', esc_html( $user->display_name ) ); ?>
									</p>
								<?php endif; ?>
							</div>

							<div class="form-row">
								<select name="rating" aria-label="Đánh giá số sao" required>
									<option value="">— Chọn số sao —</option>
									<option value="5">★★★★★ Tuyệt vời (5 sao)</option>
									<option value="4">★★★★☆ Hài lòng (4 sao)</option>
									<option value="3">★★★☆☆ Bình thường (3 sao)</option>
									<option value="2">★★☆☆☆ Chưa tốt (2 sao)</option>
									<option value="1">★☆☆☆☆ Thất vọng (1 sao)</option>
								</select>
							</div>

							<div class="form-row">
								<textarea name="comment" rows="4" placeholder="Chia sẻ cảm nhận về sản phẩm..." required></textarea>
							</div>

							<button type="submit" class="shoe-button shoe-button-dark">Gửi đánh giá →</button>
						</form>
					</div>
				<?php endif; ?>

			</div>
		</div><!-- /#tabReviews -->
	</div><!-- /.shoe-detail-tabs-section -->

	<!-- SẢN PHẨM LIÊN QUAN — dữ liệu thật -->
	<?php if ( ! empty( $related_products ) ) : ?>
		<section class="shoe-section shoe-detail-related">
			<div class="shoe-section-head">
				<h2>Có thể bạn cũng thích</h2>
				<a class="shoe-text-link" href="<?php echo esc_url( $shop_url ); ?>">Xem tất cả →</a>
			</div>
			<div class="shoe-product-grid">
				<?php foreach ( $related_products as $rel_p ) :
					$r_id      = $rel_p->get_id();
					$r_title   = $rel_p->get_name();
					$r_url     = $rel_p->get_permalink();
					$r_price   = $rel_p->get_price_html();
					$r_img_id  = $rel_p->get_image_id();
					$r_img_url = $r_img_id
						? wp_get_attachment_image_url( $r_img_id, 'woocommerce_thumbnail' )
						: wc_placeholder_img_src( 'woocommerce_thumbnail' );
					$r_img_alt = $r_img_id
						? trim( wp_strip_all_tags( get_post_meta( $r_img_id, '_wp_attachment_image_alt', true ) ) )
						: $r_title;

					$r_cats    = get_the_terms( $r_id, 'product_cat' );
					$r_cats    = ( ! empty( $r_cats ) && ! is_wp_error( $r_cats ) ) ? array_filter( $r_cats, fn( $t ) => $t->slug !== 'uncategorized' ) : array();
					$r_cat     = ! empty( $r_cats ) ? reset( $r_cats ) : null;

					$r_badge = '';
					if ( $rel_p->is_on_sale() ) {
						$reg  = (float) $rel_p->get_regular_price();
						$sale = (float) $rel_p->get_sale_price();
						$pct  = ( $reg > 0 && $sale > 0 ) ? round( ( ( $reg - $sale ) / $reg ) * 100 ) : 0;
						$r_badge = $pct ? '<span class="shoe-badge">-' . esc_html( $pct ) . '%</span>' : '<span class="shoe-badge">Sale</span>';
					} elseif ( $rel_p->is_featured() ) {
						$r_badge = '<span class="shoe-badge">Nổi bật</span>';
					} elseif ( ( time() - get_the_time( 'U', $r_id ) ) < 30 * DAY_IN_SECONDS ) {
						$r_badge = '<span class="shoe-badge">Mới</span>';
					}
				?>
					<a class="shoe-product" href="<?php echo esc_url( $r_url ); ?>">
						<div class="shoe-product-image">
							<?php echo $r_badge; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<img src="<?php echo esc_url( $r_img_url ); ?>" alt="<?php echo esc_attr( $r_img_alt ); ?>">
						</div>
						<div class="shoe-product-info">
							<div class="shoe-product-title"><?php echo esc_html( $r_title ); ?></div>
							<?php if ( $r_cat ) : ?>
								<div class="shoe-product-type"><?php echo esc_html( $r_cat->name ); ?></div>
							<?php endif; ?>
							<div class="shoe-price"><?php echo wp_kses_post( $r_price ); ?></div>
						</div>
					</a>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

</div><!-- /.shoe-detail-container -->
</main>

<!-- MODAL BẢNG SIZE -->
<div class="shoe-modal" id="shoeSizeModal" role="dialog" aria-modal="true" aria-labelledby="shoeModalTitle">
	<div class="shoe-modal-backdrop" id="shoeModalBackdrop"></div>
	<div class="shoe-modal-dialog">
		<div class="shoe-modal-header">
			<h3 id="shoeModalTitle">Bảng kích thước</h3>
			<button type="button" class="shoe-modal-close" id="shoeModalClose" aria-label="Đóng">✕</button>
		</div>
		<div class="shoe-modal-body">
			<?php
			if ( $size_guide_page && $size_guide_page->post_content ) {
				echo wp_kses_post( apply_filters( 'the_content', $size_guide_page->post_content ) );
			} else {
			?>
			<table class="shoe-size-table">
				<thead><tr><th>Chiều dài (cm)</th><th>EU/VN</th><th>US Nam</th><th>US Nữ</th></tr></thead>
				<tbody>
					<tr><td>23.5–24.0</td><td><strong>38</strong></td><td>6.0</td><td>7.5</td></tr>
					<tr><td>24.1–24.5</td><td><strong>39</strong></td><td>6.5</td><td>8.0</td></tr>
					<tr><td>24.6–25.0</td><td><strong>40</strong></td><td>7.0</td><td>8.5</td></tr>
					<tr class="highlight"><td>25.1–25.5</td><td><strong>41</strong></td><td>8.0</td><td>9.5</td></tr>
					<tr><td>25.6–26.0</td><td><strong>42</strong></td><td>8.5</td><td>10.0</td></tr>
					<tr><td>26.1–26.5</td><td><strong>43</strong></td><td>9.5</td><td>11.0</td></tr>
					<tr><td>26.6–27.0</td><td><strong>44</strong></td><td>10.0</td><td>11.5</td></tr>
				</tbody>
			</table>
			<?php } ?>
		</div>
	</div>
</div>

<!-- TOAST -->
<div class="shoe-toast" id="shoeToast" role="alert" aria-live="polite">
	<div class="shoe-toast-body">
		<span class="shoe-toast-icon">✓</span>
		<div class="shoe-toast-text">
			<strong>Đã thêm vào giỏ hàng!</strong>
			<span id="shoeToastDesc"><?php echo esc_html( $title ); ?></span>
		</div>
		<a href="<?php echo esc_url( $cart_url ); ?>" class="shoe-toast-btn">Xem giỏ →</a>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

	// Gallery
	const mainImg = document.getElementById('shoeMainImage');
	document.querySelectorAll('.shoe-thumb-btn').forEach(btn => {
		btn.addEventListener('click', function () {
			document.querySelectorAll('.shoe-thumb-btn').forEach(b => b.classList.remove('is-active'));
			this.classList.add('is-active');
			if (mainImg) {
				mainImg.style.opacity = '0.3';
				setTimeout(() => { mainImg.src = this.dataset.full; mainImg.style.opacity = '1'; }, 110);
			}
		});
	});

	// Size picker
	document.querySelectorAll('.shoe-size-pill').forEach(pill => {
		pill.addEventListener('click', function () {
			document.querySelectorAll('.shoe-size-pill').forEach(p => p.classList.remove('is-selected'));
			this.classList.add('is-selected');
		});
	});

	// Color picker
	const colorLabel = document.getElementById('shoeSelectedColor');
	document.querySelectorAll('.shoe-color-pill').forEach(pill => {
		pill.addEventListener('click', function () {
			document.querySelectorAll('.shoe-color-pill').forEach(p => p.classList.remove('is-selected'));
			this.classList.add('is-selected');
			if (colorLabel) colorLabel.textContent = this.dataset.color;
		});
	});

	// Tabs
	const tabBtns   = document.querySelectorAll('.shoe-tab-btn');
	const tabPanels = document.querySelectorAll('.shoe-tab-panel');
	function activateTab(id) {
		tabBtns.forEach(b => b.classList.remove('is-active'));
		tabPanels.forEach(p => p.classList.remove('is-active'));
		const btn = document.querySelector('[data-tab="' + id + '"]');
		const panel = document.getElementById(id);
		if (btn)   btn.classList.add('is-active');
		if (panel) panel.classList.add('is-active');
	}
	tabBtns.forEach(btn => {
		btn.addEventListener('click', function () { activateTab(this.dataset.tab); });
	});
	// Scroll to reviews tab khi click link đánh giá
	const reviewLink = document.getElementById('shoeTabReviewsLink');
	if (reviewLink) {
		reviewLink.addEventListener('click', function (e) {
			e.preventDefault();
			activateTab('tabReviews');
			document.getElementById('shoeDetailTabs')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
		});
	}
	// Tabs từ nút Review trong nav
	const reviewTabBtn = document.getElementById('shoeTabReviews');
	if (reviewTabBtn) {
		reviewTabBtn.addEventListener('click', function () { activateTab('tabReviews'); });
	}

	// Modal
	const modal = document.getElementById('shoeSizeModal');
	const openBtn  = document.getElementById('shoeOpenSizeGuide');
	const closeBtn = document.getElementById('shoeModalClose');
	const backdrop = document.getElementById('shoeModalBackdrop');
	function openModal()  { modal && modal.classList.add('is-open'); }
	function closeModal() { modal && modal.classList.remove('is-open'); }
	if (openBtn)  openBtn.addEventListener('click', openModal);
	if (closeBtn) closeBtn.addEventListener('click', closeModal);
	if (backdrop) backdrop.addEventListener('click', closeModal);
	document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

	// Toast khi WooCommerce thêm vào giỏ
	const toast = document.getElementById('shoeToast');
	const toastDesc = document.getElementById('shoeToastDesc');
	let toastTimer;
	function showToast(msg) {
		if (!toast) return;
		if (toastDesc && msg) toastDesc.textContent = msg;
		toast.classList.add('is-visible');
		clearTimeout(toastTimer);
		toastTimer = setTimeout(() => toast.classList.remove('is-visible'), 4500);
	}
	// Lắng nghe sự kiện WooCommerce AJAX add_to_cart
	jQuery(document).on('added_to_cart', function (e, fragments, cart_hash, $btn) {
		const name = $btn ? $btn.closest('.product').find('.woocommerce-loop-product__title, h1').first().text() : '';
		showToast(name);
	});

});
</script>

<?php get_footer(); ?>
