<?php
if ( ! smartcrawl_subsite_setting_page_enabled( 'wds_checkup' ) ) {
	return;
}

$page_url = Smartcrawl_Settings_Admin::admin_url( Smartcrawl_Settings::TAB_CHECKUP );
$checkup_url = Smartcrawl_Settings_Dashboard::checkup_url();
/**
 * @var $service Smartcrawl_Checkup_Service
 */
$service = Smartcrawl_Service::get( Smartcrawl_Service::SERVICE_CHECKUP );
$options = $_view['options'];
$reporting_enabled = smartcrawl_get_array_value( $options, 'checkup-cron-enable' );
$last_checked = (boolean) $service->get_last_checked_timestamp();
$in_progress = $last_checked ? false : $service->in_progress();
$option_name = Smartcrawl_Settings::TAB_SETTINGS . '_options';
$checkup_enabled = smartcrawl_get_array_value( $options, 'checkup' );
$checkup_text = esc_html__( 'Get a comprehensive report on how optimized your website is for search engines and social media. We recommend running this checkup first to see what needs improving.', 'wds' );
$results = $in_progress ? array() : $service->result();
$counts = smartcrawl_get_array_value( $results, 'counts' );
$issue_count = intval( smartcrawl_get_array_value( $counts, 'warning' ) ) + intval( smartcrawl_get_array_value( $counts, 'critical' ) );
?>
<section id="<?php echo esc_attr( Smartcrawl_Settings_Dashboard::BOX_SEO_CHECKUP ); ?>"
         data-dependent="<?php echo esc_attr( Smartcrawl_Settings_Dashboard::BOX_TOP_STATS ); ?>"
         class="dev-box">
	<div class="box-title">
		<?php if ( $checkup_enabled ) : ?>
			<div class="buttons buttons-icon">
				<a href="<?php echo esc_attr( $page_url ); ?>">
					<i class="wds-icon-arrow-right-carats"></i>
				</a>
			</div>
		<?php endif; ?>
		<h3>
			<i class="wds-icon-icon-smart-crawl"></i> <?php esc_html_e( 'SEO Checkup', 'wds' ); ?>
			<?php if ( $issue_count > 0 && $checkup_enabled ) : ?>
				<span class="wds-issues wds-issues-warning wds-has-tooltip"
				      data-content="<?php printf( esc_attr__( 'You have %s outstanding SEO issues to fix up', 'wds' ), intval( $issue_count ) ); ?>">
					<?php echo intval( $issue_count ); ?>
				</span>
			<?php endif; ?>
		</h3>
	</div>
	<div class="box-content">
		<?php if ( $checkup_enabled ) : ?>
			<?php
			if ( ! $last_checked && ! $in_progress ) {
				?>
				<p><?php echo esc_html( $checkup_text ); ?></p>

				<div class="wds-box-footer">
					<a href="<?php echo esc_attr( $checkup_url ); ?>"
					   class="button button-small">
						<?php esc_html_e( 'Run checkup', 'wds' ); ?>
					</a>
				</div>
				<?php
			} elseif ( $service->in_progress() ) {
				$this->_render( 'dashboard/dashboard-checkup-progress' );
			} else {
				$this->_render( 'dashboard/dashboard-mini-checkup-report', array(
					'results'     => $results,
					'issue_count' => $issue_count,
				) );
			}
			?>
		<?php else : ?>
			<p><?php echo esc_html( $checkup_text ); ?></p>
			<button type="button"
			        data-option-id="<?php echo esc_attr( $option_name ); ?>"
			        data-flag="<?php echo esc_attr( 'checkup' ); ?>"
			        class="wds-activate-component button button-small wds-button-with-loader wds-button-with-right-loader wds-disabled-during-request">

				<?php esc_html_e( 'Activate', 'wds' ); ?>
			</button>
		<?php endif; ?>
	</div>
</section>
