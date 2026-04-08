<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$post_id        = get_the_ID();
	$excerpt        = igw_spk_get_item_excerpt_text( $post_id );
	$portionsgroess = sanitize_text_field( (string) get_post_meta( $post_id, 'igw_spk_portionsgroesse', true ) );
	$prices         = igw_spk_get_item_price_output( $post_id );
	$ingredient_ids = igw_spk_get_item_ingredient_ids( $post_id );
	$labels         = igw_spk_get_item_labels( $post_id );
	$markings       = igw_spk_get_aggregated_markings( $post_id );
	$stand          = igw_spk_get_item_modified_date( $post_id );

	$ingredient_names = array();
	foreach ( $ingredient_ids as $ingredient_id ) {
		$title = get_the_title( $ingredient_id );
		if ( $title ) {
			$ingredient_names[] = $title;
		}
	}

	$categories = get_the_terms( $post_id, 'category' );
	$cat_names  = ( ! is_wp_error( $categories ) && ! empty( $categories ) ) ? wp_list_pluck( $categories, 'name' ) : array();

	$tags      = get_the_terms( $post_id, 'post_tag' );
	$tag_names = ( ! is_wp_error( $tags ) && ! empty( $tags ) ) ? wp_list_pluck( $tags, 'name' ) : array();
	?>
	<main id="primary" class="site-main">
		<article <?php post_class( 'igw-spk-single' ); ?>>
			<?php if ( has_post_thumbnail() ) : ?>
				<div class="igw-spk-single__image"><?php the_post_thumbnail( 'large', array( 'loading' => 'lazy' ) ); ?></div>
			<?php endif; ?>

			<h1 class="igw-spk-single__title"><?php the_title(); ?></h1>

			<?php if ( '' !== $excerpt ) : ?>
				<div class="igw-spk-single__field"><?php echo esc_html( $excerpt ); ?></div>
			<?php endif; ?>

			<?php if ( ! empty( $labels ) ) : ?>
				<div class="igw-spk-single__field"><?php echo esc_html( implode( ', ', $labels ) ); ?></div>
			<?php endif; ?>

			<?php if ( ! empty( $prices ) ) : ?>
				<div class="igw-spk-single__field"><strong><?php esc_html_e( 'Preis', 'igw_wp_speisekarte' ); ?>:</strong>
					<?php foreach ( $prices as $price_line ) : ?>
						<div class="igw-spk-single__price-line"><?php echo esc_html( $price_line ); ?></div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<div class="igw-spk-single__field igw-spk-single__content"><?php the_content(); ?></div>

			<?php if ( '' !== $portionsgroess ) : ?>
				<div class="igw-spk-single__field"><strong><?php esc_html_e( 'Portionsgröße', 'igw_wp_speisekarte' ); ?>:</strong> <?php echo esc_html( $portionsgroess ); ?></div>
			<?php endif; ?>

			<?php if ( ! empty( $ingredient_names ) ) : ?>
				<div class="igw-spk-single__field"><strong><?php esc_html_e( 'Auswahl', 'igw_wp_speisekarte' ); ?>:</strong> <?php echo esc_html( implode( ', ', $ingredient_names ) ); ?></div>
			<?php endif; ?>

			<div class="igw-spk-single__field">
				<strong><?php esc_html_e( 'Allergene', 'igw_wp_speisekarte' ); ?>:</strong>
				<?php echo esc_html( implode( ', ', $markings['allergene'] ) ); ?>
			</div>

			<div class="igw-spk-single__field">
				<strong><?php esc_html_e( 'Zusatzstoffe', 'igw_wp_speisekarte' ); ?>:</strong>
				<?php echo esc_html( implode( ', ', $markings['zusatzstoffe'] ) ); ?>
			</div>

			<?php if ( '' !== $stand ) : ?>
				<div class="igw-spk-single__field"><strong><?php esc_html_e( 'Stand', 'igw_wp_speisekarte' ); ?>:</strong> <?php echo esc_html( $stand ); ?></div>
			<?php endif; ?>

			<?php if ( ! empty( $cat_names ) ) : ?>
				<div class="igw-spk-single__field"><strong><?php esc_html_e( 'Kategorie', 'igw_wp_speisekarte' ); ?>:</strong> <?php echo esc_html( implode( ', ', $cat_names ) ); ?></div>
			<?php endif; ?>

			<?php if ( ! empty( $tag_names ) ) : ?>
				<div class="igw-spk-single__field"><strong><?php esc_html_e( 'Schlagwörter', 'igw_wp_speisekarte' ); ?>:</strong> <?php echo esc_html( implode( ', ', $tag_names ) ); ?></div>
			<?php endif; ?>
		</article>
	</main>
	<?php
endwhile;

get_footer();
