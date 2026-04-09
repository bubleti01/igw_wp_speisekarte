<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main id="primary" class="site-main">
	<div class="igw-spk-archive">
		<header class="page-header">
			<h1 class="page-title"><?php post_type_archive_title(); ?></h1>
		</header>

		<?php if ( have_posts() ) : ?>
			<div class="igw-spk-list igw-spk-list-archive">
				<?php
				while ( have_posts() ) :
					the_post();
					$post_id = get_the_ID();
					?>
					<article <?php post_class( 'igw-spk-item igw-spk-archive-item' ); ?>>
						<div class="igw-spk-item__image">
							<?php if ( has_post_thumbnail() ) : ?>
								<a class="igw-spk-item__image-link" href="<?php echo esc_url( get_permalink() ); ?>">
									<?php the_post_thumbnail( 'large', array( 'loading' => 'lazy' ) ); ?>
								</a>
							<?php endif; ?>
						</div>

						<div class="igw-spk-item__content">
							<h2 class="igw-spk-item__title">
								<a href="<?php echo esc_url( get_permalink() ); ?>"><?php the_title(); ?></a>
							</h2>
							<?php $hauptzutaten = sanitize_text_field( (string) get_post_meta( $post_id, 'igw_spk_hauptzutaten', true ) ); ?>
						<?php if ( '' !== $hauptzutaten ) : ?>
							<div class="igw-spk-item__excerpt"><?php echo esc_html( $hauptzutaten ); ?></div>
						<?php endif; ?>
						</div>

						<div class="igw-spk-item__price">
							<?php
							$prices = igw_spk_get_item_price_output( $post_id );
							foreach ( $prices as $price_line ) {
								echo '<div class="igw-spk-item__price-line">' . esc_html( $price_line ) . '</div>';
							}
							?>
						</div>
					</article>
				<?php endwhile; ?>
			</div>

			<?php the_posts_pagination(); ?>
		<?php else : ?>
			<p><?php esc_html_e( 'Keine Speisen gefunden.', 'igw_wp_speisekarte' ); ?></p>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
