<?php
$smartcrawl_options = Smartcrawl_Settings::get_options();
$link = ! isset( $link ) ? home_url() : $link;
$title = ! isset( $title ) ? smartcrawl_replace_vars( $smartcrawl_options['title-home'] ) : $title;
$description = ! isset( $description ) ? smartcrawl_replace_vars( $smartcrawl_options['metadesc-home'] ) : $description;
?>
<div class="wds-preview-container">
	<div class="wds-preview">
		<div class="wds-preview-title">
			<h3>
				<a href="<?php echo esc_url( $link ); ?>">
					<?php echo esc_html( $title ); ?>
				</a>
			</h3>
		</div>
		<div class="wds-preview-url">
			<a href="<?php echo esc_url( $link ); ?>">
				<?php echo esc_url( $link ); ?>
			</a>
		</div>
		<div class="wds-preview-meta">
			<?php echo esc_html( $description ); ?>
		</div>
	</div>
	<p class="wds-preview-description"><?php esc_html_e( 'A preview of how your title and meta will appear in Google Search.', 'wds' ); ?></p>
</div>
