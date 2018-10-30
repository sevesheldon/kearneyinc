<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/template-files-section/partial-and-miscellaneous-template-files/#footer-php
 *
 * @package Primer
 * @since   1.0.0
 */

?>

		</div><!-- #content -->

		<?php

		/**
		 * Fires before the `<footer>` element.
		 *
		 * @since 1.0.0
		 */
		do_action( 'primer_before_footer' );

		?>

		<footer id="colophon" class="site-footer">

			<div class="site-footer-inner">

				<?php

				/**
				 * Fires inside the `<footer>` element.
				 *
				 * @hooked primer_add_footer_widgets - 10
				 *
				 * @since 1.0.0
				 */
				do_action( 'primer_footer' );

				?>

			</div><!-- .site-footer-inner -->

		</footer><!-- #colophon -->

		<?php

		/**
		 * Fires after the `<footer>` element.
		 *
		 * @hooked primer_add_site_info - 10
		 *
		 * @since 1.0.0
		 */
		do_action( 'primer_after_footer' );

		?>

	</div><!-- #page -->

	<?php wp_footer(); ?>

<script type="text/javascript">
var $zoho=$zoho || {};$zoho.salesiq = $zoho.salesiq ||
{widgetcode:"764592a9abbe4ec4594aee3818dc0f1bd922cf83b2a0764e3851bd4005ac3a7e", values:{},ready:function(){}};
var d=document;s=d.createElement("script");s.type="text/javascript";s.id="zsiqscript";s.defer=true;
s.src="https://salesiq.zoho.com/widget";t=d.getElementsByTagName("script")[0];t.parentNode.insertBefore(s,t);d.write("<div id='zsiqwidget'></div>");
</script>

</body>

</html>
