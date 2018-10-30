<?php
$sitemap_tab_id = 'tab_sitemap';
$sitemap_tab_name = __( 'Sitemap', 'wds' );
$url_crawler_tab_id = 'tab_url_crawler';
$url_crawler_tab_name = __( 'URL Crawler', 'wds' );
$crawl_url = Smartcrawl_Sitemap_Settings::crawl_url();
?>

<div id="container" class="wrap wrap-wds wds-page wds-sitemap-settings">

	<section id="header">
		<?php $this->_render( 'settings-message-top' ); ?>
		<div class="actions">
			<?php if ( Smartcrawl_Settings::get_setting( 'sitemap' ) ) { ?>
				<?php
				$service = Smartcrawl_Service::get( Smartcrawl_Service::SERVICE_SEO );
				$end = $service->get_last_run_timestamp();
				$end = ! empty( $end ) && is_numeric( $end )
					? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $end )
					: __( 'Never', 'wds' );

				printf(
					esc_html__( 'Last crawl: %s', 'wds' ),
					esc_html( $end )
				);
				?>
				<?php if ( ! $service->in_progress() ) { ?>
					<a href="<?php echo esc_attr( $crawl_url ); ?>" class="button button-small">
						<?php esc_html_e( 'New crawl', 'wds' ); ?>
					</a>
				<?php } ?>
			<?php } ?>
			<a target="_blank" class="button button-small button-light actions-button"
			   href="https://premium.wpmudev.org/docs/wpmu-dev-plugins/smartcrawl/#chapter-4">
				<i class="wds-icon-academy"></i>
				<?php esc_html_e( 'View Documentation', 'wds' ); ?>
			</a>
		</div>
		<h1>
			<?php esc_html_e( 'Sitemaps', 'wds' ); ?>
			<span class="toggle wds-toggle wds-has-tooltip"
			      data-content="<?php echo Smartcrawl_Settings::get_setting( 'sitemap' ) ? esc_html__( 'Turn off automated sitemap' ) : esc_html__( 'Turn on automated sitemap' ); ?>"
			      data-position='{"my":"left center","at":"right center"}'>
				<input class="toggle-checkbox sitemap-status-toggle"
				       value="1"
				       id="sitemap-status-toggle"
				       autocomplete="off"
				       type="checkbox" <?php checked( Smartcrawl_Settings::get_setting( 'sitemap' ) ); ?>>
				<label class="toggle-label" for="sitemap-status-toggle"></label>
			</span>
		</h1>
	</section><!-- end header -->

	<div class="wds-url-crawler-stats-container"></div>

	<?php
	$smartcrawl_options = Smartcrawl_Settings::get_options();
	if ( 'settings' === $_view['name'] || ( ! empty( $smartcrawl_options[ $_view['name'] ] ) ) ) {

		?>
		<form action='<?php echo esc_attr( $_view['action_url'] ); ?>' method='post' class="wds-form">
			<?php settings_fields( $_view['option_name'] ); ?>

			<input type="hidden"
			       name='<?php echo esc_attr( $_view['option_name'] ); ?>[<?php echo esc_attr( $_view['slug'] ); ?>-setup]'
			       value="1">

			<div class="vertical-tabs" id="sitemap-settings-tabs">
				<?php
				$this->_render( 'vertical-tab', array(
					'tab_id'       => $sitemap_tab_id,
					'tab_name'     => $sitemap_tab_name,
					'is_active'    => $sitemap_tab_id === $active_tab,
					'tab_sections' => array(
						array(
							'section_description' => __( 'Automatically generate a sitemap and regularly send updates to Google.', 'wds' ),
							'section_template'    => 'sitemap/sitemap-section-settings',
							'section_args'        => array(
								'post_types'      => $post_types,
								'taxonomies'      => $taxonomies,
								'wds_buddypress'  => $wds_buddypress,
								'extra_urls'      => ! empty( $extra_urls ) ? $extra_urls : '',
								'ignore_urls'     => ! empty( $ignore_urls ) ? $ignore_urls : '',
								'ignore_post_ids' => ! empty( $ignore_post_ids ) ? $ignore_post_ids : '',
							),
						),
					),
				) );
				?>

				<?php

				$is_member = $service->is_member();
				?>

				<?php
				$this->_render(
					$is_member ? 'vertical-tab' : 'report-vertical-tab',
					array(
						'tab_id'       => $url_crawler_tab_id,
						'tab_name'     => $url_crawler_tab_name,
						'is_active'    => $url_crawler_tab_id === $active_tab,
						'button_text'  => false,
						'title_button' => 'upgrade',
						'tab_sections' => array(
							array(
								'section_template' => 'sitemap/sitemap-section-url-crawler',
							),
						),
					)
				);
				?>

				<?php
				$this->_render(
					$is_member ? 'vertical-tab' : 'report-vertical-tab',
					array(
						'tab_id'       => 'tab_url_crawler_reporting',
						'tab_name'     => __( 'Reporting', 'wds' ),
						'is_active'    => 'tab_url_crawler_reporting' === $active_tab,
						'title_button' => 'upgrade',
						'tab_sections' => array(
							array(
								'section_description' => esc_html__( 'Set up SmartCrawl to automatically crawl your URLs daily, weekly or monthly and send an email report to as many recipients as you like.', 'wds' ),
								'section_template'    => 'sitemap/sitemap-section-reporting',
							),
						),
					)
				);
				?>

				<?php
				$this->_render( 'vertical-tab', array(
					'tab_id'       => 'tab_advanced',
					'tab_name'     => __( 'Advanced', 'wds' ),
					'is_active'    => 'tab_advanced' === $active_tab,
					'tab_sections' => array(
						array(
							'section_template' => 'sitemap/sitemap-section-advanced',
							'section_args'     => array(
								'engines' => $engines,
							),
						),
					),
				) );
				?>
			</div>
		</form>
	<?php } else { ?>
		<form method="post" class="wds-form">
			<div class="vertical-tabs" id="sitemap-settings-tabs">
				<?php
				$this->_render( 'vertical-tab', array(
					'tab_id'       => $sitemap_tab_id,
					'tab_name'     => $sitemap_tab_name,
					'is_active'    => $active_tab === $sitemap_tab_id,
					'button_text'  => false,
					'tab_sections' => array(
						array(
							'section_template' => 'disabled-component-inner',
							'section_args'     => array(
								'content'     => sprintf(
									'%s<br/>%s',
									__( 'Automatically generate a full sitemap, regularly send updates to search engines and set up', 'wds' ),
									__( 'SmartCrawl to automatically check URLs are discoverable by search engines.', 'wds' )
								),
								'image'       => 'sitemap-disabled.png',
								'component'   => 'sitemap',
								'button_text' => __( 'Activate Sitemap', 'wds' ),
							),
						),
					),
				) );

				$this->_render( 'vertical-tab', array(
					'tab_id'       => $url_crawler_tab_id,
					'tab_name'     => $url_crawler_tab_name,
					'is_active'    => $active_tab === $url_crawler_tab_id,
					'button_text'  => false,
					'tab_sections' => array(
						array(
							'section_template' => 'disabled-component-inner',
							'section_args'     => array(
								'content'     => sprintf(
									'%s<br/>%s',
									__( 'Have SmartCrawl check for broken URLs, 404s, multiple redirections and other harmful', 'wds' ),
									__( 'issues that can reduce your ability to rank in search engines.', 'wds' )
								),
								'image'       => 'url-crawler-disabled.png',
								'component'   => 'sitemap',
								'button_text' => __( 'Activate Sitemap', 'wds' ),
								'notice'      => esc_html__( 'You must activate the Sitemap feature to use the URL crawler.', 'wds' ),
							),
						),
					),
				) );
				?>
			</div>
		</form>
	<?php } ?>
	<?php $this->_render( 'upsell-modal' ); ?>

</div><!-- end wds-sitemap-settings -->
