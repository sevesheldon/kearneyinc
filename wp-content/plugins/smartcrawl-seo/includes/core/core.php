<?php
/**
 * Core helper functions
 *
 * Procedures smartcrawl_get_value(), smartcrawl_replace_vars(), smartcrawl_get_term_meta()
 * inspired by WordPress SEO by Joost de Valk (http://yoast.com/wordpress/seo/).
 *
 * @package wpmu-dev-seo
 */

/**
 * Gets post meta value
 *
 * @param string $val Key root to check.
 * @param int $post_id Optional post ID.
 *
 * @return mixed
 */
function smartcrawl_get_value( $val, $post_id = false ) {
	if ( ! $post_id ) {
		global $post;
		$post_id = isset( $post ) ? $post->ID : false;
	}
	if ( ! $post_id ) {
		return false;
	}

	$custom = get_post_custom( $post_id );

	return ( ! empty( $custom[ '_wds_' . $val ][0] ) )
		? maybe_unserialize( $custom[ '_wds_' . $val ][0] )
		: false;
}

/**
 * Sets post meta value
 *
 * @param string $meta Key root to check.
 * @param mixed $val Value to set.
 * @param int $post_id Optional post ID.
 *
 * @return void
 */
function smartcrawl_set_value( $meta, $val, $post_id ) {
	update_post_meta( $post_id, "_wds_{$meta}", $val );
}

/**
 * Macro expansion helper
 *
 * @param string $string String to process.
 * @param array $args Expansion vars.
 *
 * @return string
 */
function smartcrawl_replace_vars( $string, $args = array() ) {
	global $wp_query;

	$defaults = array(
		'ID'            => '',
		'name'          => '',
		'post_author'   => '',
		'post_content'  => '',
		'post_date'     => '',
		'post_excerpt'  => '',
		'post_modified' => '',
		'post_title'    => '',
		'post_type'     => '',
		'taxonomy'      => '',
		'description'   => '',
		'username'      => '',
		'full_name'     => '',
	);

	$pagenum = get_query_var( 'paged' );
	if ( 0 === $pagenum ) {
		$pagenum = ( $wp_query->max_num_pages > 1 ) ? 1 : '';
	}

	$r = wp_parse_args( $args, $defaults );

	$smartcrawl_options = Smartcrawl_Settings::get_options();
	$preset_sep = ! empty( $smartcrawl_options['preset-separator'] ) ? $smartcrawl_options['preset-separator'] : 'pipe';
	$separator = ! empty( $smartcrawl_options['separator'] ) ? $smartcrawl_options['separator'] : smartcrawl_get_separators( $preset_sep );

	$replacements = array(
		'%%date%%'                 => smartcrawl_get_current_date( $r ),
		'%%title%%'                => stripslashes( $r['post_title'] ),
		'%%sitename%%'             => get_bloginfo( 'name' ),
		'%%sitedesc%%'             => get_bloginfo( 'description' ),
		'%%excerpt%%'              => smartcrawl_get_trimmed_excerpt( $r['post_excerpt'], $r['post_content'] ),
		'%%excerpt_only%%'         => $r['post_excerpt'],
		'%%category%%'             => get_the_category_list( ', ', '', $r['ID'] ) !== '' ? strip_tags( get_the_category_list( ', ', '', $r['ID'] ) ) : $r['name'],
		'%%category_description%%' => ! empty( $r['taxonomy'] ) ? trim( strip_tags( get_term_field( 'description', $r['term_id'], $r['taxonomy'] ) ) ) : '',
		'%%tag_description%%'      => ! empty( $r['taxonomy'] ) ? trim( strip_tags( get_term_field( 'description', $r['term_id'], $r['taxonomy'] ) ) ) : '',
		'%%term_description%%'     => ! empty( $r['taxonomy'] ) ? trim( strip_tags( get_term_field( 'description', $r['term_id'], $r['taxonomy'] ) ) ) : '',
		'%%term_title%%'           => $r['name'],
		'%%tag%%'                  => $r['name'],
		'%%modified%%'             => $r['post_modified'],
		'%%id%%'                   => $r['ID'],
		'%%name%%'                 => get_the_author_meta( 'display_name', ! empty( $r['post_author'] ) ? $r['post_author'] : get_query_var( 'author' ) ),
		'%%userid%%'               => ! empty( $r['post_author'] ) ? $r['post_author'] : get_query_var( 'author' ),
		'%%searchphrase%%'         => esc_html( get_query_var( 's' ) ),
		'%%currenttime%%'          => date( 'H:i' ),
		'%%currentdate%%'          => date( 'M jS Y' ),
		'%%currentmonth%%'         => date( 'F' ),
		'%%currentyear%%'          => date( 'Y' ),
		'%%page%%'                 => ( intval( get_query_var( 'paged' ) ) !== 0 ) ? 'Page ' . get_query_var( 'paged' ) . ' of ' . $wp_query->max_num_pages : '',
		'%%spell_page%%'           => ( intval( get_query_var( 'paged' ) ) !== 0 ) ? 'Page ' . smartcrawl_spell_number( get_query_var( 'paged' ) ) . ' of ' . smartcrawl_spell_number( $wp_query->max_num_pages ) : '',
		'%%pagetotal%%'            => ( $wp_query->max_num_pages > 1 ) ? $wp_query->max_num_pages : '',
		'%%spell_pagetotal%%'      => ( $wp_query->max_num_pages > 1 ) ? smartcrawl_spell_number( $wp_query->max_num_pages ) : '',
		'%%pagenumber%%'           => $pagenum,
		'%%spell_pagenumber%%'     => smartcrawl_spell_number( $pagenum ),
		'%%caption%%'              => $r['post_excerpt'],
		'%%bp_group_name%%'        => $r['name'],
		'%%bp_group_description%%' => smartcrawl_get_trimmed_excerpt( '', $r['description'] ),
		'%%bp_user_username%%'     => $r['username'],
		'%%bp_user_full_name%%'    => $r['full_name'],
		'%%sep%%'                  => $separator,
		'%%pt_plural%%'            => is_a( $args, 'WP_Post_Type' ) ? $args->labels->name : '',
		'%%pt_single%%'            => is_a( $args, 'WP_Post_Type' ) ? $args->labels->singular_name : '',
	);

	foreach ( $replacements as $var => $repl ) {
		$repl = apply_filters( 'wds-macro-variable_replacement', $repl, $var );
		$string = str_replace( $var, $repl, $string );
	}

	return $string;
}

function smartcrawl_get_current_date( $args ) {
	$date = null;

	if ( '' !== $args['post_date'] ) {
		$date = mysql2date( get_option( 'date_format' ), $args['post_date'], true );
	} else {
		if ( get_query_var( 'day' ) && get_query_var( 'day' ) !== '' ) {
			$date = get_the_date();
		} else {
			if ( single_month_title( ' ', false ) && single_month_title( ' ', false ) !== '' ) {
				$date = single_month_title( ' ', false );
			} elseif ( get_query_var( 'year' ) !== '' ) {
				$date = get_query_var( 'year' );
			}
		}
	}

	return trim( $date );
}

/**
 * Gets separator, or separators list
 *
 * @param string $key Optional separator key.
 *
 * @return string|array
 */
function smartcrawl_get_separators( $key = null ) {
	$separators = array(
		'dot'           => '·',
		'dot-l'         => '•',
		'dash'          => '-',
		'dash-l'        => '—',
		'pipe'          => '|',
		'forward-slash' => '/',
		'back-slash'    => '\\',
		'tilde'         => '~',
		'greater-than'  => '>',
		'less-than'     => '<',
		'caret-right'   => '›',
		'caret-left'    => '‹',
		'arrow-right'   => '→',
		'arrow-left'    => '←',
	);

	if ( null === $key || empty( $separators[ $key ] ) ) {
		return $separators;
	} else {
		return $separators[ $key ];
	}
}

/**
 * Returns the post title meta using options set by user on smartcrawl_onpage
 *
 * @param WP_Post $post Optional post.
 *
 * @return string|false
 */
function smartcrawl_get_seo_title( $post = false ) {
	$smartcrawl_options = Smartcrawl_Settings::get_options();

	if ( ! $post ) {
		global $post;
	}
	if ( ! $post ) {
		return false;
	}

	if ( ! empty( $post->post_type ) && isset( $smartcrawl_options[ 'title-' . $post->post_type ] ) && ! empty( $smartcrawl_options[ 'title-' . $post->post_type ] ) ) {
		return smartcrawl_replace_vars( $smartcrawl_options[ 'title-' . $post->post_type ], (array) $post );
	}

	return 'false';
}

/**
 * Returns the post desc meta using options set by user on smartcrawl_onpage
 *
 * @param WP_Post $post Optional post.
 *
 * @return string|false
 */
function smartcrawl_get_seo_desc( $post = false ) {
	$smartcrawl_options = Smartcrawl_Settings::get_options();

	if ( ! $post ) {
		global $post;
	}
	if ( ! $post ) {
		return false;
	}

	if ( ! empty( $post->post_type ) && isset( $smartcrawl_options[ 'metadesc-' . $post->post_type ] ) && ! empty( $smartcrawl_options[ 'metadesc-' . $post->post_type ] ) ) {
		return smartcrawl_replace_vars( $smartcrawl_options[ 'metadesc-' . $post->post_type ], (array) $post );
	}

	return false;
}


/**
 * Returns the number as an anglicized string
 *
 * Adapted from original code by Hugh Bothwell (hugh_bothwell@hotmail.com)
 *
 * @param int $num Number to convert.
 *
 * @return string
 */
function smartcrawl_spell_number( $num ) {
	$num = (int) $num;    // make sure it's an integer.

	if ( $num < 0 ) {
		return 'negative' . _wds_hb_convert_tri( - $num, 0 );
	}
	if ( 0 === $num ) {
		return 'zero';
	}

	return _wds_hb_convert_tri( $num, 0 );
}

/**
 * Recursive fn, converts three digits per pass
 *
 * Adapted from original code by Hugh Bothwell (hugh_bothwell@hotmail.com)
 *
 * @param int $num Number to convert.
 * @param int $tri Triplet to check.
 *
 * @return string
 */
function _wds_hb_convert_tri( $num, $tri ) {
	$ones = array(
		'',
		' one',
		' two',
		' three',
		' four',
		' five',
		' six',
		' seven',
		' eight',
		' nine',
		' ten',
		' eleven',
		' twelve',
		' thirteen',
		' fourteen',
		' fifteen',
		' sixteen',
		' seventeen',
		' eighteen',
		' nineteen',
	);

	$tens = array(
		'',
		'',
		' twenty',
		' thirty',
		' forty',
		' fifty',
		' sixty',
		' seventy',
		' eighty',
		' ninety',
	);

	$triplets = array(
		'',
		' thousand',
		' million',
		' billion',
		' trillion',
		' quadrillion',
		' quintillion',
		' sextillion',
		' septillion',
		' octillion',
		' nonillion',
	);

	// chunk the number, ...rxyy.
	$r = (int) ( $num / 1000 );
	$x = ( $num / 100 ) % 10;
	$y = $num % 100;

	// init the output string.
	$str = '';

	// do hundreds.
	if ( $x > 0 ) {
		$str = $ones[ $x ] . ' hundred';
	}

	// do ones and tens.
	if ( $y < 20 ) {
		$str .= $ones[ $y ];
	} else {
		$str .= $tens[ (int) ( $y / 10 ) ] . $ones[ $y % 10 ];
	}

	// add triplet modifier only if there is some output to be modified...
	if ( '' !== $str ) {
		$str .= $triplets[ $tri ];
	}

	// continue recursing?
	if ( $r > 0 ) {
		return _wds_hb_convert_tri( $r, $tri + 1 ) . $str;
	} else {
		return $str;
	}
}

/**
 * Gets excerpt trimmed to length
 *
 * @param string $excerpt Optional excerpt.
 * @param string $contents Contents.
 *
 * @return string
 */
function smartcrawl_get_trimmed_excerpt( $excerpt, $contents ) {
	$string = $excerpt ? $excerpt : $contents;
	$string = trim( preg_replace( '/\r|\n/', ' ', strip_shortcodes( htmlspecialchars( wp_strip_all_tags( strip_shortcodes( $string ) ), ENT_QUOTES ) ) ) );

	return ( preg_match( '/.{156,}/um', $string ) )
		? preg_replace( '/(.{0,152}).*/um', '$1', $string ) . '...'
		: $string;
}

/**
 * Gets taxonomy term meta value
 *
 * @param object $term Term object.
 * @param string $taxonomy Taxonomy the term belongs to.
 * @param string $meta Meta key to check.
 *
 * @return mixed
 */
function smartcrawl_get_term_meta( $term, $taxonomy, $meta ) {
	$term = ( is_object( $term ) ) ? $term->term_id : get_term_by( 'slug', $term, $taxonomy );
	$tax_meta = get_option( 'wds_taxonomy_meta' );

	return ( isset( $tax_meta[ $taxonomy ][ $term ][ $meta ] ) ) ? $tax_meta[ $taxonomy ][ $term ][ $meta ] : false;
}

/**
 * Blog template settings handler
 *
 * @param string $and Query gathered this far.
 *
 * @return string
 */
function smartcrawl_blog_template_settings( $and ) {
	// $and .= " AND `option_name` != 'wds_sitemaps_options'"; // Removed plural
	$and .= " AND `option_name` != 'wds_sitemap_options'"; // Added singular
	return $and;
}

add_filter( 'blog_template_exclude_settings', 'smartcrawl_blog_template_settings' );


/**
 * Checks user persmission level against minumum requirement
 * for displaying SEO metabox.
 *
 * @return bool
 */
function user_can_see_seo_metabox() {
	$smartcrawl_options = Smartcrawl_Settings::get_options();
	$capability = ( defined( 'SMARTCRAWL_SEO_METABOX_ROLE' ) && SMARTCRAWL_SEO_METABOX_ROLE )
		? SMARTCRAWL_SEO_METABOX_ROLE
		: ( ! empty( $smartcrawl_options['seo_metabox_permission_level'] ) ? $smartcrawl_options['seo_metabox_permission_level'] : false );
	$capability = apply_filters( 'wds-capabilities-seo_metabox', $capability );
	$able = false;

	if ( is_array( $capability ) ) {
		foreach ( $capability as $cap ) {
			$able = current_user_can( $cap );
			if ( $able ) {
				break;
			}
		}
	} else {
		$able = current_user_can( $capability );
	}

	return $able;
}

/**
 * Checks user persmission level against minumum requirement
 * for displaying Moz urlmetrics metabox.
 *
 * @return bool
 */
function user_can_see_urlmetrics_metabox() {
	$smartcrawl_options = Smartcrawl_Settings::get_options();
	$capability = ( defined( 'SMARTCRAWL_URLMETRICS_METABOX_ROLE' ) && SMARTCRAWL_URLMETRICS_METABOX_ROLE )
		? SMARTCRAWL_URLMETRICS_METABOX_ROLE
		: ( ! empty( $smartcrawl_options['urlmetrics_metabox_permission_level'] ) ? $smartcrawl_options['urlmetrics_metabox_permission_level'] : false );
	$capability = apply_filters( 'wds-capabilities-urlmetrics_metabox', $capability );
	$able = false;

	if ( is_array( $capability ) ) {
		foreach ( $capability as $cap ) {
			$able = current_user_can( $cap );
			if ( $able ) {
				break;
			}
		}
	} else {
		$able = current_user_can( $capability );
	}

	return $able;
}

/**
 * Checks user persmission level against minumum requirement
 * for displaying the 301 redirection field within SEO metabox.
 *
 * @return bool
 */
function user_can_see_seo_metabox_301_redirect() {
	$smartcrawl_options = Smartcrawl_Settings::get_options();
	$capability = ( defined( 'SMARTCRAWL_SEO_METABOX_301_ROLE' ) && SMARTCRAWL_SEO_METABOX_301_ROLE )
		? SMARTCRAWL_SEO_METABOX_301_ROLE
		: ( ! empty( $smartcrawl_options['seo_metabox_301_permission_level'] ) ? $smartcrawl_options['seo_metabox_301_permission_level'] : false );
	$capability = apply_filters( 'wds-capabilities-seo_metabox_301_redirect', $capability );
	$able = false;

	if ( is_array( $capability ) ) {
		foreach ( $capability as $cap ) {
			$able = current_user_can( $cap );
			if ( $able ) {
				break;
			}
		}
	} else {
		$able = current_user_can( $capability );
	}

	return $able;
}

/**
 * Attempt to hide metaboxes by default by adding them to "hidden" array.
 *
 * Metaboxes are still added to "Screen Options".
 * If user chooses to show/hide them, respect her decision.
 *
 * @deprecated as of version 1.0.9
 *
 * @param array $arg Whatever's been already hidden.
 *
 * @return array
 */
function smartcrawl_process_default_hidden_meta_boxes( $arg ) {
	$smartcrawl_options = Smartcrawl_Settings::get_options();
	$arg[] = 'wds-wds-meta-box';
	$arg[] = 'wds_seomoz_urlmetrics';

	return $arg;
}

/**
 * Hide ALL wds metaboxes.
 *
 * Respect wishes for other metaboxes.
 * Still accessble from "Screen Options".
 *
 * @param array $arg Whatever's been already hidden.
 *
 * @return array
 */
function smartcrawl_hide_metaboxes( $arg ) {
	// Hide WP defaults, if nothing else.
	if ( empty( $arg ) ) {
		$arg = array(
			'slugdiv',
			'trackbacksdiv',
			'postcustom',
			'postexcerpt',
			'commentstatusdiv',
			'commentsdiv',
			'authordiv',
			'revisionsdiv',
		);
	}
	$arg[] = 'wds-wds-meta-box';
	$arg[] = 'wds_seomoz_urlmetrics';

	return $arg;
}

/**
 * Register metabox hiding for other boxes.
 *
 * @deprecated
 */
function smartcrawl_register_metabox_hiding() {
	$post_types = get_post_types();
	foreach ( $post_types as $type ) {
		add_filter( 'get_user_option_metaboxhidden_' . $type, 'smartcrawl_hide_metaboxes' );
	}

}

/**
 * Forces metaboxes to start collapsed.
 *
 * It properly merges the WDS boxes with the rest of the users collapsed boxes.
 * For info on registering, see `register_metabox_collapsed_state`.
 *
 * @param array $closed Whatever's been closed this far.
 *
 * @return array
 */
function force_metabox_collapsed_state( $closed ) {
	$closed = is_array( $closed ) ? $closed : array();

	return array_merge( $closed, array(
		'wds-wds-meta-box',
		'wds_seomoz_urlmetrics',
	) );
}

/**
 * Registers WDS boxes state.
 * Collapsed state is tracked per post type.
 * This is why we have this separate hook to register state change processing.
 */
function register_metabox_collapsed_state() {
	global $post;
	if ( $post && $post->post_type ) {
		add_filter( 'get_user_option_closedpostboxes_' . $post->post_type, 'force_metabox_collapsed_state' );
	}
}

add_filter( 'post_edit_form_tag', 'register_metabox_collapsed_state' );

/**
 * Checks the page tab slug against permitted ones.
 *
 * This applies only for multisite, non-sitewide setups.
 *
 * @param string $slug Slug to check.
 * @TODO: This function is a duplicate of Smartcrawl_Settings_Admin.is_tab_allowed, make sure only one remains
 *
 * @return bool
 */
function smartcrawl_is_allowed_tab( $slug ) {
	$blog_tabs = get_site_option( 'wds_blog_tabs' );
	$blog_tabs = is_array( $blog_tabs ) ? $blog_tabs : array();
	$allowed = true;
	if ( is_multisite() && ! SMARTCRAWL_SITEWIDE ) {
		$allowed = in_array( $slug, array_keys( $blog_tabs ), true ) ? true : false;
	}

	return $allowed;
}

/**
 * Checks if transient is stuck
 *
 * Stuck transient has no expiry time.
 * If so found, removes it.
 *
 * @param string $key Transient key.
 *
 * @return bool
 */
function smartcrawl_kill_stuck_transient( $key ) {
	global $_wp_using_ext_object_cache;
	if ( $_wp_using_ext_object_cache ) {
		return true;
	} // In object cache, nothing to do.

	$key = "_transient_{$key}";
	$alloptions = wp_load_alloptions();
	// If option is in alloptions, it is autoloaded and thus has no timeout - kill it.
	if ( isset( $alloptions[ $key ] ) ) {
		return delete_option( $key );
	}

	return true;
}

/**
 * Check for boolean define switches and their values.
 *
 * @param string $switch Define name to check.
 *
 * @return bool
 */
function smartcrawl_is_switch_active( $switch ) {
	return defined( $switch ) ? constant( $switch ) : false;
}

/**
 * Check if we're on main BuddyPress site - BuddyPress root blog check.
 *
 * @return bool Are we on the main BuddyPress site.
 */
function smartcrawl_is_main_bp_site() {
	if ( is_multisite() && defined( 'BP_VERSION' ) && ( defined( 'BP_ROOT_BLOG' ) && BP_ROOT_BLOG ) ) {
		global $blog_id;

		return intval( BP_ROOT_BLOG ) === intval( $blog_id );
	}

	return is_main_site();
}

/**
 * Converts an argument map to HTML attributes string.
 *
 * @param array $args A hash of arguments.
 *
 * @return string Constructed attributes string
 */
function smartcrawl_autolinks_construct_attributes( $args = array() ) {
	$ret = array();
	if ( empty( $args ) ) {
		return '';
	}
	foreach ( $args as $key => $value ) {
		if ( empty( $key ) || empty( $value ) ) {
			continue; // Only accept properly formatted members.
		}
		$ret[] = esc_html( $key ) . '="' . esc_attr( $value ) . '"';
	}

	return apply_filters( 'wds_autolinks_attributes', trim( join( ' ', $ret ) ) );
}

/**
 * Get a value from an array. If nothing is found for the provided keys, returns null.
 *
 * @param array $array The array to search (haystack).
 * @param array|string $key The key to use for the search.
 *
 * @return null|mixed The array value found or null if nothing found.
 */
function smartcrawl_get_array_value( $array, $key ) {
	if ( ! is_array( $key ) ) {
		$key = array( $key );
	}

	if ( ! is_array( $array ) ) {
		return null;
	}

	$value = $array;
	foreach ( $key as $key_part ) {
		$value = isset( $value[ $key_part ] ) ? $value[ $key_part ] : null;
	}

	return $value;
}

/**
 * Inserts a value in the given array.
 *
 * @param mixed $value The value to insert.
 * @param array $array The array in which the value is to be inserted. Passed by reference.
 * @param array|string $keys Key specifying the place where the new value is to be inserted.
 *
 * @return void
 */
function smartcrawl_put_array_value( $value, &$array, $keys ) {
	if ( ! is_array( $keys ) ) {
		$keys = array( $keys );
	}

	$pointer = &$array;
	foreach ( $keys as $key ) {
		if ( ! isset( $pointer[ $key ] ) ) {
			$pointer[ $key ] = array();
		}
		$pointer = &$pointer[ $key ];
	}
	$pointer = $value;
}

/**
 * Checks if a dashboard widget mode is renderable
 *
 * Used on dashboard root page, for scenarios when we're
 * outside the network-wide mode and not all tabs are active
 * for site admins, in order to prevent showing broken "configure" links
 * and such. Re: https://app.asana.com/0/46496453944769/509480319187557/f
 *
 * @param string $tab Tab to check.
 *
 * @return bool
 */
function smartcrawl_can_show_dash_widget_for( $tab ) {
	if ( ! ! smartcrawl_is_switch_active( 'SMARTCRAWL_SITEWIDE' ) ) {
		return true;
	}

	if ( ! is_network_admin() ) {
		return true;
	} // Whatever, let site admin deal with it.

	// Not in sitewide mode, let's check if site admins can access it.
	$allowed_blog_tabs = Smartcrawl_Settings_Settings::get_blog_tabs();
	$allowed = in_array( $tab, array_keys( $allowed_blog_tabs ), true ) && ! empty( $allowed_blog_tabs[ $tab ] );

	return $allowed;
}

/**
 * Sanitizes a string into relative URL
 *
 * @param string $raw Raw string to process.
 *
 * @return string Root-relative string
 */
function smartcrawl_sanitize_relative_url( $raw ) {
	$raw = preg_match( '/^https?:\/\//', $raw ) || preg_match( '/^\//', $raw )
		? esc_url( $raw )
		: esc_url( "/{$raw}" );

	$parsed = wp_parse_url( $raw );
	$result = '';

	if ( empty( $parsed ) ) {
		$domain = preg_replace( '/^https?:\/\//', '', home_url() );
		$raw = preg_replace( '/^https?:\/\//', '', $raw );
		$result = preg_replace( '/^' . preg_quote( $domain, '/' ) . '/', '', $raw );
	} else {
		$result = ! empty( $parsed['path'] ) ? $parsed['path'] : '/';
		if ( ! empty( $parsed['query'] ) ) {
			$result .= '?' . $parsed['query'];
		}
	}

	return '/' . ltrim( $result, '/' );
}

/**
 * Gets regex for matching against a list of relative URLs
 *
 * @param string $urls A list of relative URLs.
 *
 * @return string
 */
function smartcrawl_get_relative_urls_regex( $urls ) {
	$regex = '';
	if ( ! is_array( $urls ) ) {
		return $regex;
	}

	$processed = array();
	foreach ( $urls as $url ) {
		if ( empty( $url ) ) {
			continue;
		}
		$processed[] = preg_quote( $url, '/' );
	}
	$regex = '/https?:\/\/.*?(' . join( '|', $processed ) . ')\/?$/';

	return $regex;
}

function smartcrawl_subsite_setting_page_enabled( $key ) {
	if ( ! is_multisite() || smartcrawl_is_switch_active( 'SMARTCRAWL_SITEWIDE' ) ) {
		return true;
	}

	return (boolean) smartcrawl_get_array_value( get_site_option( 'wds_blog_tabs', array() ), $key );
}

function smartcrawl_get_attachment_id_by_url( $url ) {
	global $wpdb;

	return $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE guid=%s", $url ) );
}

function smartcrawl_get_attachment_by_url( $url ) {
	$attachment_id = smartcrawl_get_attachment_id_by_url( $url );
	if ( $attachment_id ) {
		$attachment_image_src = wp_get_attachment_image_src( $attachment_id, 'full' );

		return $attachment_image_src
			? array(
				'url'    => $attachment_image_src[0],
				'width'  => $attachment_image_src[1],
				'height' => $attachment_image_src[2],
			)
			: null;
	}

	return null;
}

function smartcrawl_get_archive_post_types() {
	return array_keys( smartcrawl_get_archive_post_type_labels() );
}

function smartcrawl_get_archive_post_type_labels() {
	$archive_post_types = array();
	$post_type_args = array(
		'public'      => true,
		'has_archive' => true,
	);
	foreach ( get_post_types( $post_type_args ) as $post_type ) {
		if ( in_array( $post_type, array( 'revision', 'nav_menu_item' ), true ) ) {
			continue;
		}

		$post_type_object = get_post_type_object( $post_type );
		$archive_post_types[ 'pt-archive-' . $post_type ] = $post_type_object->labels->name;
	}

	return $archive_post_types;
}

/**
 * Fetch the actual sitemap path, to the best of our abilities.
 *
 * @return string Sitemap path
 */
function smartcrawl_get_sitemap_path() {
	$smartcrawl_options = Smartcrawl_Settings::get_options();

	$dir = wp_upload_dir();
	$path = ! empty( $smartcrawl_options['sitemappath'] ) ? $smartcrawl_options['sitemappath'] : false; // First thing first, try the sitewide option.

	// Not in sitewide mode, check per-blog options.
	if ( ! SMARTCRAWL_SITEWIDE ) {
		$_data = get_option( 'wds_sitemap_options' );
		$path = ! empty( $_data['sitemappath'] ) ? $_data['sitemappath'] : false;
	}

	// If there isn't a dir we need to write to, or we're on a network child in sitewide mode, go for the uploads dir.
	if ( ! is_dir( dirname( $path ) ) || ( SMARTCRAWL_SITEWIDE && ! is_main_site() ) ) {
		$path = trailingslashit( $dir['basedir'] );
		$path = "{$path}sitemap.xml";
	}

	return wp_normalize_path( $path );
}

/**
 * Fetch sitemap URL in an uniform fashion.
 *
 * @return string Sitemap URL
 */
function smartcrawl_get_sitemap_url() {
	$smartcrawl_options = Smartcrawl_Settings::get_options();
	$sitemap_options = ( is_multisite() && is_main_site() ) ? $smartcrawl_options : get_option( 'wds_sitemap_options' );
	$sitemap_url = ! empty( $sitemap_options['sitemapurl'] ) ? $sitemap_options['sitemapurl'] : false;

	if ( empty( $sitemap_url ) ) {
		$sitemap_url = trailingslashit( home_url( false ) ) . 'sitemap.xml';
	}

	if ( is_multisite() && class_exists( 'domain_map' ) ) {
		$sitemap_url = home_url( false ) . '/sitemap.xml';

		if ( defined( 'SMARTCRAWL_SITEMAP_DM_SIMPLE_DISCOVERY_FALLBACK' ) && SMARTCRAWL_SITEMAP_DM_SIMPLE_DISCOVERY_FALLBACK ) {
			$sitemap_url = ( is_network_admin() ? '../../' : ( is_admin() ? '../' : '/' ) ) . 'sitemap.xml'; // Simplest possible logic.
		}
	}

	return apply_filters( 'wds-sitemaps-sitemap_url', $sitemap_url );
}

function smartcrawl_get_allowed_html_for_forms() {
	return array(
		'form'  => array(
			'class'   => array(),
			'id'      => array(),
			'action'  => array(),
			'method'  => array(),
			'enctype' => array(),
		),
		'input' => array(
			'class' => array(),
			'id'    => array(),
			'type'  => array(),
			'name'  => array(),
			'value' => array(),
		),
	);
}

function smartcrawl_file_get_contents( $file ) {
	if ( ! function_exists( 'WP_Filesystem' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	WP_Filesystem();
	$file_system = new WP_Filesystem_Direct( null );
	$contents = $file_system->get_contents( $file );

	return $contents;
}

function smartcrawl_file_put_contents( $file, $contents, $mode = false ) {
	if ( ! function_exists( 'WP_Filesystem' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	WP_Filesystem();
	$file_system = new WP_Filesystem_Direct( null );

	return $file_system->put_contents( $file, $contents, $mode );
}

/**
 * Gets whatever's latest of a post
 *
 * @param int $post_id Post ID.
 *
 * @return WP_Post
 */
function smartcrawl_get_latest_post_version( $post_id ) {
	$post = get_post( $post_id );
	$post_revisions = wp_get_post_revisions( $post_id, array(
		'orderby' => 'modified',
		'order'   => 'DESC',
	) );
	if ( count( $post_revisions ) ) {
		$revision = array_shift( $post_revisions );
		if ( strtotime( $revision->post_modified ) > strtotime( $post->post_date ) ) {
			return $revision;
		}
	}

	return $post;
}

/**
 * Checks whether the supplied string is a valid meta tag
 *
 * @param string $string String to check.
 *
 * @return bool
 */
function smartcrawl_is_valid_meta_tag( $string ) {
	$string = trim($string);
	if (!preg_match('/^\<meta/i', $string)) return false;
	if (!preg_match('/\>$/', $string)) return false;

	return true;
}
