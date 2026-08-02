<?php

namespace smp_verified_profiles;

use Hexa\PluginCore\WpAdminComponents\ColorControl;
use Hexa\PluginCore\WpAdminComponents\CoreUi;
use Hexa\PluginCore\WpAdminComponents\DetailedColorPicker;
use Hexa\PluginCore\WpAdminComponents\TemplateSelectionControl;
use Hexa\PluginCore\WpAdminComponents\TypographyControl;

defined( 'ABSPATH' ) || exit;

const SMP_VP_PROFILE_PAGE_OPTION = 'smp_vp_profile_page_settings';
const SMP_VP_PROFILE_PAGE_NONCE  = 'smp_vp_profile_page_nonce';
const SMP_VP_PROFILE_PAGE_CUSTOM_TEMPLATE = 'custom';

add_action( 'wp_ajax_smp_vp_profile_page_save', __NAMESPACE__ . '\\smp_vp_ajax_profile_page_save' );
add_filter( 'the_content', __NAMESPACE__ . '\\smp_vp_profile_page_filter_content', 18 );
add_filter( 'template_include', __NAMESPACE__ . '\\smp_vp_profile_page_template_include', 99 );
add_filter( 'elementor/theme/get_location_templates/template_id', __NAMESPACE__ . '\\smp_vp_profile_page_elementor_single_template_id', 10, 2 );
add_filter( 'hello_elementor_page_title', __NAMESPACE__ . '\\smp_vp_profile_page_hello_page_title', 20 );
add_shortcode( 'verified_profile_page', __NAMESPACE__ . '\\smp_vp_verified_profile_page_shortcode' );

function smp_vp_profile_page_templates(): array {
    return [
        'editorial-masthead' => [
            'label'       => 'Template A: Editorial Masthead',
            'short_label' => 'Editorial Masthead',
            'class'       => 'pp-a',
            'description' => 'Sharp editorial profile page with a large serif name, portrait, social row, details, organizations, and article sections.',
        ],
        'modern-ledger' => [
            'label'       => 'Template B: Modern Ledger',
            'short_label' => 'Modern Ledger',
            'class'       => 'pp-b',
            'description' => 'Balanced portrait-led profile with a clear biography column, compact facts, organizations, and recent coverage.',
        ],
        'sidebar-dossier' => [
            'label'       => 'Template C: Sidebar Dossier',
            'short_label' => 'Sidebar Dossier',
            'class'       => 'pp-c',
            'description' => 'Sectioned profile page with a left dossier column and main biography, organizations, and article sections.',
        ],
    ];
}

function smp_vp_profile_page_defaults(): array {
    return [
        'enabled'           => false,
        'selected_template' => 'editorial-masthead',
        'render_mode'       => 'auto',
        'grid_width'        => 0,
        'primary_color'     => '#2f55ff',
        'ink_color'         => '#101010',
        'body_color'        => '#565656',
        'muted_color'       => '#8a8a8a',
        'line_color'        => '#e8e4df',
        'soft_color'        => '#faf8f5',
        'heading_font_size' => 46,
        'body_font_size'    => 17,
        'profile_page_preserve_font_size' => false,
    ];
}

function smp_vp_profile_page_color_keys(): array {
    return [
        'primary_color' => 'Accent color',
        'ink_color'     => 'Heading color',
        'body_color'    => 'Body color',
        'muted_color'   => 'Muted color',
        'line_color'    => 'Line color',
        'soft_color'    => 'Soft background',
    ];
}

function smp_vp_profile_page_grid_width_css( array $settings ): string {
    $width = isset( $settings['grid_width'] ) ? absint( $settings['grid_width'] ) : 0;
    return $width > 0 ? $width . 'px' : 'none';
}

function smp_vp_profile_page_color_palette_html( array $settings ): string {
    $defaults = smp_vp_profile_page_defaults();
    if ( ! class_exists( DetailedColorPicker::class ) || ! class_exists( ColorControl::class ) ) {
        return '';
    }

    $detailed = DetailedColorPicker::render(
        [
            'id'          => 'smp-vp-profile-page-detailed-colors',
            'title'       => 'Detailed Color Picker',
            'description' => 'Set the accent and heading colors, or import both directly from Elementor.',
            'show_elementor_import' => true,
            'primary'     => [
                'key'             => 'primary_color',
                'label'           => 'Primary color',
                'value'           => (string) $settings['primary_color'],
                'default'         => (string) $defaults['primary_color'],
                'id'              => 'smp-vp-profile-page-primary-color',
                'control_class'   => 'smp-vp-profile-page-color-control',
                'hex_input_class' => 'smp-vp-page-color',
            ],
            'secondary'   => [
                'key'             => 'ink_color',
                'label'           => 'Secondary color',
                'value'           => (string) $settings['ink_color'],
                'default'         => (string) $defaults['ink_color'],
                'id'              => 'smp-vp-profile-page-ink-color',
                'control_class'   => 'smp-vp-profile-page-color-control',
                'hex_input_class' => 'smp-vp-page-color',
            ],
        ]
    );

    $supporting = '<div class="smp-vp-page-supporting-colors">';
    foreach ( [ 'body_color', 'muted_color', 'line_color', 'soft_color' ] as $key ) {
        $supporting .= ColorControl::render(
            [
                'key'             => $key,
                'label'           => smp_vp_profile_page_color_keys()[ $key ],
                'value'           => (string) $settings[ $key ],
                'default'         => (string) $defaults[ $key ],
                'id'              => 'smp-vp-profile-page-' . str_replace( '_', '-', $key ),
                'control_class'   => 'smp-vp-profile-page-color-control',
                'hex_input_class' => 'smp-vp-page-color',
            ]
        );
    }
    $supporting .= '</div>';

    return '<div class="smp-vp-page-color-tools">' . $detailed . CoreUi::detail_card(
        [
            'title'       => 'Supporting colors',
            'body_html'   => $supporting,
            'open'        => false,
            'persist_key' => 'smp-vp-profile-page-supporting-colors',
        ]
    ) . '</div>';
}

function smp_vp_profile_page_settings(): array {
    $stored   = get_option( SMP_VP_PROFILE_PAGE_OPTION, [] );
    $settings = array_replace( smp_vp_profile_page_defaults(), is_array( $stored ) ? $stored : [] );

    return smp_vp_profile_page_sanitize( $settings );
}

function smp_vp_profile_page_sanitize( array $input ): array {
    $templates = array_keys( smp_vp_profile_page_templates() );
    $defaults  = smp_vp_profile_page_defaults();

    $selected = sanitize_key( (string) ( $input['selected_template'] ?? $defaults['selected_template'] ) );
    $templates[] = SMP_VP_PROFILE_PAGE_CUSTOM_TEMPLATE;
    if ( ! in_array( $selected, $templates, true ) ) {
        $selected = $defaults['selected_template'];
    }

    $mode = sanitize_key( (string) ( $input['render_mode'] ?? $defaults['render_mode'] ) );
    if ( ! in_array( $mode, [ 'auto', 'shortcode' ], true ) ) {
        $mode = 'auto';
    }

    $settings = [
        'enabled'           => ! empty( $input['enabled'] ),
        'selected_template' => $selected,
        'render_mode'       => $mode,
        'grid_width'        => min( 2400, max( 0, absint( $input['grid_width'] ?? $defaults['grid_width'] ) ) ),
        'heading_font_size' => min( 84, max( 24, absint( $input['heading_font_size'] ?? $defaults['heading_font_size'] ) ) ),
        'body_font_size'    => min( 28, max( 12, absint( $input['body_font_size'] ?? $defaults['body_font_size'] ) ) ),
        'profile_page_preserve_font_size' => ! empty( $input['profile_page_preserve_font_size'] ),
    ];

    foreach ( array_keys( smp_vp_profile_page_color_keys() ) as $key ) {
        $color = sanitize_hex_color( (string) ( $input[ $key ] ?? $defaults[ $key ] ) );
        $settings[ $key ] = $color ?: $defaults[ $key ];
    }

    if ( SMP_VP_PROFILE_PAGE_CUSTOM_TEMPLATE === $settings['selected_template'] ) {
        $settings['enabled']     = false;
        $settings['render_mode'] = 'shortcode';
    }

    return $settings;
}

function smp_vp_profile_page_cpt_slug(): string {
    $slug = 'profile';

    if ( function_exists( __NAMESPACE__ . '\\get_verified_profile_settings' ) ) {
        $settings = get_verified_profile_settings();
        if ( is_array( $settings ) && ! empty( $settings['slug'] ) ) {
            $slug = (string) $settings['slug'];
        }
    }

    return sanitize_key( $slug ?: 'profile' );
}

function smp_vp_profile_page_is_profile_post( $post = null ): bool {
    $post = get_post( $post );
    return $post instanceof \WP_Post && smp_vp_profile_page_cpt_slug() === $post->post_type;
}

function smp_vp_profile_page_should_render_auto(): bool {
    $settings = smp_vp_profile_page_settings();

    return ! empty( $settings['enabled'] )
        && 'auto' === $settings['render_mode']
        && SMP_VP_PROFILE_PAGE_CUSTOM_TEMPLATE !== $settings['selected_template']
        && ! is_admin()
        && is_singular( smp_vp_profile_page_cpt_slug() )
        && in_the_loop()
        && is_main_query();
}

function smp_vp_profile_page_is_excerpt_context(): bool {
    return doing_filter( 'get_the_excerpt' ) || doing_filter( 'the_excerpt' );
}

function smp_vp_profile_page_log_failure( string $context, \Throwable $error ): void {
    error_log(
        sprintf(
            'SMP Verified Profiles profile-page render failure [%s]: %s in %s:%d',
            $context,
            $error->getMessage(),
            $error->getFile(),
            $error->getLine()
        )
    );
}

function smp_vp_profile_page_fallback_html( int $post_id ): string {
    $title = get_the_title( $post_id );
    if ( '' === trim( (string) $title ) ) {
        return '';
    }

    return '<article class="smp-vp-profile-page smp-vp-profile-page-fallback"><h1>' . esc_html( $title ) . '</h1></article>';
}

function smp_vp_profile_page_preview_template(): string {
    if ( empty( $_GET['smp_vp_template_preview'] ) || ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
        return '';
    }

    $template = sanitize_key( wp_unslash( (string) $_GET['smp_vp_template_preview'] ) );
    return isset( smp_vp_profile_page_templates()[ $template ] ) ? $template : '';
}

function smp_vp_profile_page_should_use_single_template(): bool {
    if ( is_admin() || ! is_singular( smp_vp_profile_page_cpt_slug() ) ) {
        return false;
    }

    if ( '' !== smp_vp_profile_page_preview_template() ) {
        return true;
    }

    $settings = smp_vp_profile_page_settings();
    return ! empty( $settings['enabled'] )
        && 'auto' === $settings['render_mode']
        && SMP_VP_PROFILE_PAGE_CUSTOM_TEMPLATE !== $settings['selected_template'];
}

function smp_vp_profile_page_template_include( string $template ): string {
    if ( ! smp_vp_profile_page_should_use_single_template() ) {
        return $template;
    }

    $plugin_template = __DIR__ . '/templates/single-profile-page.php';
    return file_exists( $plugin_template ) ? $plugin_template : $template;
}

function smp_vp_profile_page_elementor_single_template_id( $template_id, string $location ) {
    if ( 'single' !== $location || ! smp_vp_profile_page_should_use_single_template() ) {
        return $template_id;
    }

    return 0;
}

function smp_vp_profile_page_hello_page_title( $show_title ) {
    return smp_vp_profile_page_should_use_single_template() ? false : $show_title;
}

function smp_vp_profile_page_filter_content( $content ) {
    static $rendering = false;

    if ( ! smp_vp_profile_page_is_profile_post() ) {
        return $content;
    }

    if ( $rendering || smp_vp_profile_page_is_excerpt_context() ) {
        return $content;
    }

    $preview = smp_vp_profile_page_preview_template();
    if ( '' !== $preview ) {
        try {
            $rendering = true;
            return smp_vp_render_profile_page_template( get_the_ID(), $preview );
        } catch ( \Throwable $error ) {
            smp_vp_profile_page_log_failure( 'content-preview', $error );
            return $content;
        } finally {
            $rendering = false;
        }
    }

    if ( ! smp_vp_profile_page_should_render_auto() ) {
        return $content;
    }

    $settings = smp_vp_profile_page_settings();
    try {
        $rendering = true;
        return smp_vp_render_profile_page_template( get_the_ID(), $settings['selected_template'] );
    } catch ( \Throwable $error ) {
        smp_vp_profile_page_log_failure( 'content-auto', $error );
        return $content;
    } finally {
        $rendering = false;
    }
}

function smp_vp_verified_profile_page_shortcode( $atts = [] ): string {
    $settings = smp_vp_profile_page_settings();
    $atts     = shortcode_atts(
        [
            'template'   => '',
            'post_id'    => 0,
            'grid_width' => '',
        ],
        (array) $atts,
        'verified_profile_page'
    );

    $post_id = absint( $atts['post_id'] );
    if ( ! $post_id ) {
        $post_id = get_the_ID();
    }

    if ( ! $post_id || ! smp_vp_profile_page_is_profile_post( $post_id ) ) {
        return '';
    }

    $requested_template = sanitize_key( (string) $atts['template'] );
    $template           = $requested_template;
    if ( '' === $template || 'auto' === $template ) {
        $template = (string) $settings['selected_template'];
    } elseif ( ! isset( smp_vp_profile_page_templates()[ $template ] ) ) {
        return '';
    }

    if ( SMP_VP_PROFILE_PAGE_CUSTOM_TEMPLATE === $template ) {
        return '';
    }

    $overrides = [];
    if ( '' !== trim( (string) $atts['grid_width'] ) ) {
        $overrides['grid_width'] = absint( $atts['grid_width'] );
    }

    try {
        return smp_vp_render_profile_page_template( $post_id, $template, $overrides );
    } catch ( \Throwable $error ) {
        smp_vp_profile_page_log_failure( 'shortcode', $error );
        return smp_vp_profile_page_fallback_html( $post_id );
    }
}

function smp_vp_profile_page_is_empty_display_value( $value ): bool {
    if ( null === $value || '' === $value || [] === $value || is_bool( $value ) ) {
        return true;
    }

    if ( is_scalar( $value ) ) {
        $value = strtolower( trim( wp_strip_all_tags( (string) $value ) ) );
        return '' === $value || in_array( $value, [ '0', 'false', 'no', 'none', 'n/a', 'na', 'null' ], true );
    }

    return false;
}

function smp_vp_profile_page_raw_field( int $post_id, string $field ) {
    if ( function_exists( 'get_field' ) ) {
        $value = get_field( $field, $post_id );
        if ( ! smp_vp_profile_page_is_empty_display_value( $value ) ) {
            return $value;
        }
    }

    $value = get_post_meta( $post_id, $field, true );
    return ! smp_vp_profile_page_is_empty_display_value( $value ) ? $value : null;
}

function smp_vp_profile_page_flatten_value( $value ): string {
    if ( smp_vp_profile_page_is_empty_display_value( $value ) ) {
        return '';
    }

    if ( is_scalar( $value ) ) {
        return trim( wp_strip_all_tags( (string) $value ) );
    }

    if ( $value instanceof \WP_Post ) {
        return get_the_title( $value );
    }

    if ( is_array( $value ) ) {
        $flat = [];
        array_walk_recursive(
            $value,
            static function ( $item ) use ( &$flat ): void {
                if ( is_scalar( $item ) && ! smp_vp_profile_page_is_empty_display_value( $item ) ) {
                    $item = trim( wp_strip_all_tags( (string) $item ) );
                    $flat[] = $item;
                }
            }
        );

        return trim( implode( ' ', array_unique( $flat ) ) );
    }

    return '';
}

function smp_vp_profile_page_first_field( int $post_id, array $fields ): string {
    foreach ( $fields as $field ) {
        $value = smp_vp_profile_page_flatten_value( smp_vp_profile_page_raw_field( $post_id, $field ) );
        $normalized = strtolower( trim( $value ) );
        if ( '' !== $value && ! in_array( $normalized, [ '0', 'false', 'none', 'n/a', 'na' ], true ) ) {
            return $value;
        }
    }

    return '';
}

function smp_vp_profile_page_group_url( int $post_id, string $key ): string {
	$group = smp_vp_profile_page_raw_field( $post_id, 'url' );
	if ( is_array( $group ) && ! empty( $group[ $key ] ) ) {
		return smp_vp_profile_page_resolved_url( $post_id, $key, (string) $group[ $key ] );
	}

	foreach ( [ 'url_' . $key, $key ] as $field ) {
		$value = smp_vp_profile_page_first_field( $post_id, [ $field ] );
		if ( '' !== $value ) {
			$url = smp_vp_profile_page_resolved_url( $post_id, $key, $value );
			if ( '' !== $url ) {
				return $url;
			}
		}
	}

	return '';
}

function smp_vp_profile_page_resolved_url( int $post_id, string $key, string $url ): string {
	$url = smp_vp_profile_page_normalize_url( $url );
	if ( '' === $url ) {
		return '';
	}

	if ( 'website' === $key && smp_vp_profile_page_is_internal_article_url( $post_id, $url ) ) {
		return '';
	}

	return $url;
}

function smp_vp_profile_page_is_internal_article_url( int $post_id, string $url ): bool {
	$url_host  = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
	$home_host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );

	$url_host  = preg_replace( '#^www\.#', '', $url_host );
	$home_host = preg_replace( '#^www\.#', '', $home_host );

	if ( '' === $url_host || '' === $home_host || $url_host !== $home_host ) {
		return false;
	}

	$path = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );
	if ( '' === $path ) {
		return false;
	}

	$profile_path = trim( (string) wp_parse_url( get_permalink( $post_id ), PHP_URL_PATH ), '/' );
	if ( '' !== $profile_path && $path === $profile_path ) {
		return false;
	}

	foreach ( [ 'profile/', 'profiles/' ] as $allowed_prefix ) {
		if ( 0 === strpos( $path, $allowed_prefix ) ) {
			return false;
		}
	}

	return true;
}

function smp_vp_profile_page_normalize_url( string $url ): string {
	$url = trim( $url );
    if ( '' === $url ) {
        return '';
    }

    if ( ! preg_match( '#^https?://#i', $url ) && 0 !== strpos( $url, 'mailto:' ) ) {
        $url = 'https://' . $url;
    }

    return esc_url_raw( $url );
}

function smp_vp_profile_page_link_label( string $url ): string {
    $label = preg_replace( '#^https?://#i', '', rtrim( $url, '/' ) );
    return '' !== (string) $label ? (string) $label : $url;
}

function smp_vp_profile_page_link_html( string $url, string $label = '' ): string {
    $url = smp_vp_profile_page_normalize_url( $url );
    if ( '' === $url ) {
        return '';
    }

    $label = '' !== trim( $label ) ? $label : smp_vp_profile_page_link_label( $url );

    return '<a class="pp-link pp-link-external" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $label ) . '</a>';
}

function smp_vp_profile_page_fact_value_html( $value ): string {
    if ( is_array( $value ) && isset( $value['html'] ) ) {
        return wp_kses(
            (string) $value['html'],
            [
                'a' => [
                    'class'  => true,
                    'href'   => true,
                    'target' => true,
                    'rel'    => true,
                ],
            ]
        );
    }

    $value = trim( (string) $value );
    if ( preg_match( '#^(https?://|www\.|[a-z0-9.-]+\.[a-z]{2,})(/[^\s]*)?$#i', $value ) ) {
        return smp_vp_profile_page_link_html( $value );
    }

    return esc_html( $value );
}

function smp_vp_profile_page_bio_html( int $post_id ): string {
    foreach ( [ 'biography_short', 'biography' ] as $field ) {
        $value = smp_vp_profile_page_raw_field( $post_id, $field );
        if ( is_scalar( $value ) && '' !== trim( (string) $value ) ) {
            return wp_kses_post( wpautop( (string) $value ) );
        }
    }

    $excerpt = get_post_field( 'post_excerpt', $post_id );
    if ( '' !== trim( (string) $excerpt ) ) {
        return wp_kses_post( wpautop( $excerpt ) );
    }

    $content = get_post_field( 'post_content', $post_id );
    $content = wp_trim_words( wp_strip_all_tags( (string) $content ), 70 );

    return '' !== $content ? wp_kses_post( wpautop( $content ) ) : '';
}

function smp_vp_profile_page_image_url( int $post_id, string $size = 'medium' ): string {
    $featured = get_the_post_thumbnail_url( $post_id, $size );
    if ( $featured ) {
        return $featured;
    }

    $gallery = smp_vp_profile_page_raw_field( $post_id, 'photo_gallery' );
    if ( is_array( $gallery ) ) {
        foreach ( $gallery as $image ) {
            if ( is_array( $image ) ) {
                if ( ! empty( $image['sizes'][ $size ] ) ) {
                    return (string) $image['sizes'][ $size ];
                }
                if ( ! empty( $image['url'] ) ) {
                    return (string) $image['url'];
                }
                if ( ! empty( $image['ID'] ) ) {
                    $url = wp_get_attachment_image_url( absint( $image['ID'] ), $size );
                    if ( $url ) {
                        return $url;
                    }
                }
            } elseif ( is_numeric( $image ) ) {
                $url = wp_get_attachment_image_url( absint( $image ), $size );
                if ( $url ) {
                    return $url;
                }
            }
        }
    }

    return '';
}

function smp_vp_profile_page_education_rows( int $post_id ): array {
    if ( function_exists( __NAMESPACE__ . '\\smp_vp_profile_education_rows' ) ) {
        return smp_vp_profile_education_rows( $post_id );
    }

    $rows = smp_vp_profile_page_raw_field( $post_id, 'personal_education' );
    return is_array( $rows ) ? $rows : [];
}

function smp_vp_profile_page_education_summary( int $post_id ): string {
    foreach ( smp_vp_profile_page_education_rows( $post_id ) as $row ) {
        if ( ! is_array( $row ) ) {
            continue;
        }

        $parts = [];
        foreach ( [ 'school', 'degree', 'field_of_study' ] as $key ) {
            if ( ! empty( $row[ $key ] ) ) {
                $parts[] = trim( wp_strip_all_tags( (string) $row[ $key ] ) );
            }
        }

        if ( ! empty( $parts ) ) {
            return implode( ' - ', array_unique( $parts ) );
        }
    }

    return '';
}

function smp_vp_profile_page_education_summary_markup( int $post_id ) {
    foreach ( smp_vp_profile_page_education_rows( $post_id ) as $row ) {
        if ( ! is_array( $row ) ) {
            continue;
        }

        $school = ! empty( $row['school'] ) ? trim( wp_strip_all_tags( (string) $row['school'] ) ) : '';
        if ( '' === $school ) {
            continue;
        }

        $school_html = esc_html( $school );
        $wiki_url    = ! empty( $row['wikipedia_url'] ) ? smp_vp_profile_page_normalize_url( (string) $row['wikipedia_url'] ) : '';
        if ( '' !== $wiki_url ) {
            $school_html = smp_vp_profile_page_link_html( $wiki_url, $school );
        }

        $parts = [];
        foreach ( [ 'degree', 'field_of_study' ] as $key ) {
            if ( ! empty( $row[ $key ] ) ) {
                $parts[] = trim( wp_strip_all_tags( (string) $row[ $key ] ) );
            }
        }

        $html = $school_html;
        if ( ! empty( $parts ) ) {
            $html .= ' - ' . esc_html( implode( ' - ', array_unique( $parts ) ) );
        }

        return [ 'html' => $html ];
    }

    return '';
}

function smp_vp_profile_page_organizations( int $post_id, int $limit = 4 ): array {
    $rows = smp_vp_profile_page_raw_field( $post_id, 'organizations_founded' );
    if ( ! is_array( $rows ) ) {
        return [];
    }

    $items = [];
    foreach ( $rows as $row ) {
        if ( count( $items ) >= $limit || ! is_array( $row ) ) {
            break;
        }

        $org_id = 0;
        if ( ! empty( $row['organization'] ) ) {
            $org_id = $row['organization'] instanceof \WP_Post ? (int) $row['organization']->ID : absint( $row['organization'] );
        }

        $name = $org_id ? get_the_title( $org_id ) : smp_vp_profile_page_flatten_value( $row['name'] ?? $row['organization_name'] ?? '' );
        if ( '' === $name ) {
            continue;
        }

        $description = $org_id ? smp_vp_profile_page_first_field( $org_id, [ 'biography', 'description', 'short_description' ] ) : smp_vp_profile_page_flatten_value( $row['description'] ?? '' );
        $url         = $org_id ? smp_vp_profile_page_group_url( $org_id, 'website' ) : smp_vp_profile_page_normalize_url( smp_vp_profile_page_flatten_value( $row['url'] ?? '' ) );

        $items[] = [
            'name'        => $name,
            'description' => $description,
            'url'         => $url,
        ];
    }

    return $items;
}

function smp_vp_profile_page_related_posts( int $profile_id, int $limit = 3 ): array {
    global $wpdb;

    $profile_id = absint( $profile_id );
    if ( ! $profile_id ) {
        return [];
    }

    $post_ids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key LIKE %s AND meta_value = %s ORDER BY post_id DESC LIMIT %d",
            $wpdb->esc_like( 'profiles_' ) . '%' . $wpdb->esc_like( '_profile' ),
            (string) $profile_id,
            absint( $limit )
        )
    );

    if ( empty( $post_ids ) ) {
        return [];
    }

    return get_posts(
        [
            'post_type'      => [ 'post', 'press-release' ],
            'post_status'    => 'publish',
            'post__in'       => array_map( 'absint', $post_ids ),
            'orderby'        => 'post__in',
            'posts_per_page' => absint( $limit ),
        ]
    );
}

function smp_vp_profile_page_authored_posts( int $profile_id, int $limit = 3 ): array {
    $user = smp_vp_profile_page_raw_field( $profile_id, 'contributor_profile' );
    $user_id = 0;
    if ( $user instanceof \WP_User ) {
        $user_id = (int) $user->ID;
    } elseif ( $user instanceof \WP_Post ) {
        $user_id = (int) $user->ID;
    } elseif ( is_numeric( $user ) ) {
        $user_id = absint( $user );
    }

    if ( ! $user_id ) {
        return [];
    }

    return get_posts(
        [
            'post_type'      => [ 'post', 'press-release' ],
            'post_status'    => 'publish',
            'author'         => $user_id,
            'posts_per_page' => absint( $limit ),
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]
    );
}

function smp_vp_profile_page_fact_rows( int $post_id ): array {
    $facts = [
        'Born'      => smp_vp_profile_page_first_field( $post_id, [ 'personal_location_born_location_born', 'location_born', 'birthplace' ] ),
        'Based'     => smp_vp_profile_page_first_field( $post_id, [ 'personal_current_residence_current_residence', 'current_residence', 'based', 'location' ] ),
        'Education' => smp_vp_profile_page_education_summary_markup( $post_id ),
        'Field'     => smp_vp_profile_page_first_field( $post_id, [ 'field', 'field_of_work', 'industry', 'profession' ] ),
        'Known for' => smp_vp_profile_page_first_field( $post_id, [ 'known_for', 'notable_for' ] ),
    ];

    $website = smp_vp_profile_page_group_url( $post_id, 'website' );
    if ( '' !== $website ) {
        $facts['Website'] = [ 'html' => smp_vp_profile_page_link_html( $website ) ];
    }

    return array_filter(
        $facts,
        static function ( $value ): bool {
            if ( is_array( $value ) ) {
                return ! empty( $value['html'] );
            }

            return '' !== trim( (string) $value );
        }
    );
}

function smp_vp_profile_page_data( int $post_id ): array {
    $name = get_the_title( $post_id );
    $role = smp_vp_profile_page_first_field( $post_id, [ 'title', 'job_title', 'role', 'occupation', 'profession' ] );

    return [
        'id'            => $post_id,
        'name'          => $name,
        'role'          => '' !== $role ? $role : 'Verified Profile',
        'image'         => smp_vp_profile_page_image_url( $post_id ),
        'bio'           => smp_vp_profile_page_bio_html( $post_id ),
        'facts'         => smp_vp_profile_page_fact_rows( $post_id ),
        'organizations' => smp_vp_profile_page_organizations( $post_id ),
        'related_posts' => smp_vp_profile_page_related_posts( $post_id, 3 ),
        'authored_posts' => smp_vp_profile_page_authored_posts( $post_id, 3 ),
        'socials'       => smp_vp_profile_page_social_links( $post_id ),
    ];
}

function smp_vp_profile_page_social_links( int $post_id ): array {
    $map = [
        'linkedin'  => 'LinkedIn',
        'x'         => 'X',
        'wikipedia' => 'Wikipedia',
        'instagram' => 'Instagram',
        'youtube'   => 'YouTube',
        'website'   => 'Website',
    ];

    $links = [];
    foreach ( $map as $key => $label ) {
        $url = smp_vp_profile_page_group_url( $post_id, $key );
        if ( '' !== $url ) {
            $links[] = [
                'key'   => $key,
                'label' => $label,
                'url'   => $url,
            ];
        }
    }

    return $links;
}

function smp_vp_render_profile_page_template( int $post_id, string $template = '', array $overrides = [] ): string {
    $templates = smp_vp_profile_page_templates();
    if ( SMP_VP_PROFILE_PAGE_CUSTOM_TEMPLATE === $template ) {
        return '';
    }

    if ( '' === $template ) {
        $template = smp_vp_profile_page_settings()['selected_template'];
    }
    if ( SMP_VP_PROFILE_PAGE_CUSTOM_TEMPLATE === $template || ! isset( $templates[ $template ] ) ) {
        return '';
    }

    return smp_vp_render_profile_page_template_data( smp_vp_profile_page_data( $post_id ), $template, $overrides );
}

function smp_vp_render_profile_page_template_data( array $data, string $template, array $overrides = [] ): string {
    $templates = smp_vp_profile_page_templates();
    if ( SMP_VP_PROFILE_PAGE_CUSTOM_TEMPLATE === $template || ! isset( $templates[ $template ] ) ) {
        return '';
    }

    $settings = array_replace( smp_vp_profile_page_settings(), $overrides );
    $settings = smp_vp_profile_page_sanitize( $settings );
    $preserve = ! empty( $settings['profile_page_preserve_font_size'] );
    $vars     = sprintf(
        '--pp-accent:%s;--ink:%s;--body:%s;--muted:%s;--line:%s;--soft:%s;--pp-grid-width:%s;--pp-heading-size:%s;--pp-mobile-heading-size:%s;--pp-body-size:%s;',
        esc_attr( $settings['primary_color'] ),
        esc_attr( $settings['ink_color'] ),
        esc_attr( $settings['body_color'] ),
        esc_attr( $settings['muted_color'] ),
        esc_attr( $settings['line_color'] ),
        esc_attr( $settings['soft_color'] ),
        esc_attr( smp_vp_profile_page_grid_width_css( $settings ) ),
        esc_attr( $preserve ? 'inherit' : (int) $settings['heading_font_size'] . 'px' ),
        esc_attr( $preserve ? 'inherit' : min( 34, (int) $settings['heading_font_size'] ) . 'px' ),
        esc_attr( $preserve ? 'inherit' : (int) $settings['body_font_size'] . 'px' )
    );

    ob_start();
    ?>
    <style><?php echo smp_vp_profile_page_css(); ?></style>
    <article class="smp-vp-profile-page <?php echo esc_attr( $templates[ $template ]['class'] ); ?>" style="<?php echo esc_attr( $vars ); ?>" data-profile-template="<?php echo esc_attr( $template ); ?>">
        <?php
        if ( 'sidebar-dossier' === $template ) {
            echo smp_vp_profile_page_template_c( $data );
        } elseif ( 'modern-ledger' === $template ) {
            echo smp_vp_profile_page_template_b( $data );
        } else {
            echo smp_vp_profile_page_template_a( $data );
        }
        ?>
    </article>
    <?php
    return (string) ob_get_clean();
}

function smp_vp_profile_page_verified_icon(): string {
    return '<svg class="pp-verif" viewBox="0 0 512 512" aria-hidden="true"><path fill="currentColor" d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM369 209 241 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L335 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/></svg>';
}

function smp_vp_profile_page_photo_html( array $data, string $class ): string {
    $image = '';
    if ( function_exists( __NAMESPACE__ . '\\verified_profile_shortcode' ) ) {
        $image = verified_profile_shortcode(
            [
                'field'   => 'featured_image',
                'size'    => 'medium',
                'output'  => 'img',
                'class'   => 'pp-photo ' . $class,
                'post_id' => (int) $data['id'],
                'alt'     => (string) $data['name'],
            ]
        );
    }

    if ( '' !== $image ) {
        return $image;
    }

    if ( '' === $data['image'] ) {
        $initials = '';
        $parts    = preg_split( '/\s+/', (string) $data['name'] );
        foreach ( array_slice( (array) $parts, 0, 2 ) as $part ) {
            $initials .= strtoupper( substr( (string) $part, 0, 1 ) );
        }
        if ( '' === $initials ) {
            $initials = 'VP';
        }
        return '<div class="pp-photo pp-photo-empty ' . esc_attr( $class ) . '">' . esc_html( $initials ) . '</div>';
    }

    return '<img class="pp-photo ' . esc_attr( $class ) . '" src="' . esc_url( $data['image'] ) . '" alt="' . esc_attr( $data['name'] ) . '" loading="lazy">';
}

function smp_vp_profile_page_socials_html( array $links ): string {
    if ( empty( $links ) ) {
        return '';
    }

    $html = '<div class="pp-socials">';
    foreach ( $links as $link ) {
        $html .= '<a href="' . esc_url( $link['url'] ) . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr( $link['label'] ) . '">' . esc_html( smp_vp_profile_page_social_short_label( $link['key'], $link['label'] ) ) . '</a>';
    }
    $html .= '</div>';

    return $html;
}

function smp_vp_profile_page_social_short_label( string $key, string $label ): string {
    $labels = [
        'linkedin'  => 'in',
        'x'         => 'X',
        'wikipedia' => 'W',
        'instagram' => 'IG',
        'youtube'   => 'YT',
        'website'   => 'Web',
    ];

    return $labels[ $key ] ?? $label;
}

function smp_vp_profile_page_facts_html( array $facts, int $limit = 6 ): string {
    if ( empty( $facts ) ) {
        return '';
    }

    $html = '<div class="pp-facts">';
    $i = 0;
    foreach ( $facts as $label => $value ) {
        if ( $i >= $limit ) {
            break;
        }
        $html .= '<div class="pp-fact"><div class="k">' . esc_html( $label ) . '</div><div class="v">' . smp_vp_profile_page_fact_value_html( $value ) . '</div></div>';
        $i++;
    }
    $html .= '</div>';

    return $html;
}

function smp_vp_profile_page_orgs_html( array $organizations ): string {
    if ( empty( $organizations ) ) {
        return '';
    }

    $html = '<div class="pp-orgs">';
    foreach ( $organizations as $organization ) {
        $name = esc_html( $organization['name'] );
        if ( ! empty( $organization['url'] ) ) {
            $name = '<a class="pp-link pp-link-external" href="' . esc_url( $organization['url'] ) . '" target="_blank" rel="noopener noreferrer">' . $name . '</a>';
        }
        $html .= '<div class="pp-org"><div class="on">' . $name . '</div>';
        if ( ! empty( $organization['description'] ) ) {
            $html .= '<div class="od">' . esc_html( wp_trim_words( $organization['description'], 24 ) ) . '</div>';
        }
        $html .= '</div>';
    }
    $html .= '</div>';

    return $html;
}

function smp_vp_profile_page_articles_html( array $posts ): string {
    if ( empty( $posts ) ) {
        return '';
    }

    $html = '<div class="pp-arts">';
    foreach ( $posts as $post ) {
        if ( ! $post instanceof \WP_Post ) {
            continue;
        }

        $thumb = get_the_post_thumbnail_url( $post, 'medium_large' );
        $cat   = '';
        $terms = get_the_terms( $post, 'category' );
        if ( is_array( $terms ) && ! empty( $terms ) && ! is_wp_error( $terms ) ) {
            $cat = $terms[0]->name;
        }

        $html .= '<a class="pp-art" href="' . esc_url( get_permalink( $post ) ) . '">';
        if ( $thumb ) {
            $html .= '<img class="athumb" src="' . esc_url( $thumb ) . '" alt="' . esc_attr( get_the_title( $post ) ) . '" loading="lazy">';
        } else {
            $html .= '<div class="athumb"></div>';
        }
        $html .= '<div class="ah">' . esc_html( get_the_title( $post ) ) . '</div>';
        if ( '' !== $cat ) {
            $html .= '<div class="am">' . esc_html( $cat ) . '</div>';
        }
        $html .= '</a>';
    }
    $html .= '</div>';

    return $html;
}

function smp_vp_profile_page_section_html( string $label, string $body ): string {
    if ( '' === trim( $body ) ) {
        return '';
    }

    return '<div class="pp-section"><p class="pp-label">' . esc_html( $label ) . '</p>' . $body . '</div>';
}

function smp_vp_profile_page_template_a( array $data ): string {
    $facts    = smp_vp_profile_page_facts_html( $data['facts'] );
    $orgs     = smp_vp_profile_page_orgs_html( $data['organizations'] );
    $articles = smp_vp_profile_page_articles_html( $data['related_posts'] );

    ob_start();
    ?>
    <div class="ppa-inner">
        <div class="ppa-hero">
            <div>
                <span class="pp-eyebrow">Verified Profile</span>
                <h1 class="pp-name"><?php echo esc_html( $data['name'] ); ?> <?php echo smp_vp_profile_page_verified_icon(); ?></h1>
                <div class="pp-role"><?php echo esc_html( $data['role'] ); ?></div>
                <?php if ( '' !== $data['bio'] ) : ?><div class="pp-bio"><?php echo $data['bio']; ?></div><?php endif; ?>
                <?php echo smp_vp_profile_page_socials_html( $data['socials'] ); ?>
            </div>
            <?php echo smp_vp_profile_page_photo_html( $data, 'ppa-photo' ); ?>
        </div>
        <?php echo smp_vp_profile_page_section_html( 'Details', $facts ); ?>
        <?php echo smp_vp_profile_page_section_html( 'Organizations Founded', $orgs ); ?>
        <?php echo smp_vp_profile_page_section_html( 'In ' . get_bloginfo( 'name' ), $articles ); ?>
    </div>
    <?php
    return (string) ob_get_clean();
}

function smp_vp_profile_page_template_b( array $data ): string {
    $facts    = smp_vp_profile_page_facts_html( $data['facts'], 6 );
    $orgs     = smp_vp_profile_page_orgs_html( $data['organizations'] );
    $articles = smp_vp_profile_page_articles_html( $data['related_posts'] );

    ob_start();
    ?>
    <div class="ppb-inner">
        <header class="ppb-hero">
            <?php echo smp_vp_profile_page_photo_html( $data, 'ppb-photo' ); ?>
            <div class="ppb-intro">
                <span class="pp-eyebrow">Verified Profile</span>
                <h1 class="pp-name"><?php echo esc_html( $data['name'] ); ?> <?php echo smp_vp_profile_page_verified_icon(); ?></h1>
                <div class="pp-role"><?php echo esc_html( $data['role'] ); ?></div>
                <?php echo smp_vp_profile_page_socials_html( $data['socials'] ); ?>
            </div>
        </header>
        <div class="ppb-layout">
            <main class="ppb-main">
                <?php echo smp_vp_profile_page_section_html( 'Profile', '<div class="pp-bio">' . $data['bio'] . '</div>' ); ?>
                <?php echo smp_vp_profile_page_section_html( 'Recent Coverage', $articles ); ?>
            </main>
            <aside class="ppb-aside">
                <?php echo smp_vp_profile_page_section_html( 'At a Glance', $facts ); ?>
                <?php echo smp_vp_profile_page_section_html( 'Organizations', $orgs ); ?>
            </aside>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}

function smp_vp_profile_page_template_c( array $data ): string {
    $meta_posts = ! empty( $data['authored_posts'] ) ? $data['authored_posts'] : $data['related_posts'];
    $facts      = smp_vp_profile_page_facts_html( $data['facts'], 8 );
    $orgs       = smp_vp_profile_page_orgs_html( $data['organizations'] );
    $articles   = smp_vp_profile_page_articles_html( $meta_posts );

    ob_start();
    ?>
    <div class="ppc-inner">
        <aside class="ppc-side">
            <?php echo smp_vp_profile_page_photo_html( $data, 'ppc-photo' ); ?>
            <span class="pp-eyebrow">Verified Profile</span>
            <h1 class="pp-name"><?php echo esc_html( $data['name'] ); ?> <?php echo smp_vp_profile_page_verified_icon(); ?></h1>
            <div class="pp-role"><?php echo esc_html( $data['role'] ); ?></div>
            <?php echo smp_vp_profile_page_socials_html( $data['socials'] ); ?>
            <div class="ppc-meta"><?php echo $facts; ?></div>
        </aside>
        <div class="ppc-main">
            <?php echo smp_vp_profile_page_section_html( 'About', '<div class="pp-bio">' . $data['bio'] . '</div>' ); ?>
            <?php echo smp_vp_profile_page_section_html( 'Organizations Founded', $orgs ); ?>
            <?php echo smp_vp_profile_page_section_html( 'Words by ' . $data['name'], $articles ); ?>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}

function smp_vp_profile_page_css(): string {
    return '
.smp-vp-profile-page{--pp-accent:#2f55ff;--ink:#101010;--body:#565656;--muted:#8a8a8a;--line:#e8e4df;--soft:#faf8f5;background:#fff;color:var(--body);font-family:inherit;line-height:1.55;width:100%;box-sizing:border-box}
.smp-vp-profile-page.pp-frame{border:1px solid var(--line);border-radius:14px;box-shadow:0 24px 60px -38px rgba(15,15,15,.45);overflow:hidden}
.smp-vp-profile-page *{box-sizing:border-box}
.smp-vp-profile-page a{color:inherit;text-decoration:none}
.smp-vp-profile-page .pp-link,.smp-vp-profile-page .pp-art .ah{text-decoration:underline;text-decoration-thickness:1px;text-underline-offset:.18em}
.smp-vp-profile-page .pp-link:after,.smp-vp-profile-page .pp-art .ah:after{content:" \\2197";display:inline-block;font-size:.78em;line-height:1;transform:translateY(-.14em)}
.smp-vp-profile-page .pp-eyebrow{align-items:center;color:var(--pp-accent);display:flex;font-size:11px;font-weight:700;gap:10px;letter-spacing:.22em;text-transform:uppercase;margin:0 0 10px}
.smp-vp-profile-page .pp-eyebrow:before{background:var(--pp-accent);content:"";display:block;flex:0 0 auto;height:2px;width:18px}
.smp-vp-profile-page .pp-name{align-items:center;color:var(--ink);display:flex;font-family:Georgia,serif;font-size:var(--pp-heading-size,46px);font-weight:700;gap:13px;letter-spacing:0;line-height:1.04;margin:10px 0 0}
.smp-vp-profile-page .pp-role{color:var(--muted);font-size:12.5px;font-weight:600;letter-spacing:.14em;line-height:1.7;margin-top:12px;text-transform:uppercase}
.smp-vp-profile-page .pp-bio{color:var(--body);font-size:var(--pp-body-size,17px);line-height:1.72;margin:0}
.smp-vp-profile-page .pp-bio p{margin:0 0 1em}
.smp-vp-profile-page .pp-bio p:last-child{margin-bottom:0}
.smp-vp-profile-page .pp-verif{color:var(--pp-accent);display:inline-block;flex:0 0 auto;height:21px;width:21px}
.smp-vp-profile-page .pp-photo{background:#ececed;display:block;filter:grayscale(100%);object-fit:cover}
.smp-vp-profile-page .pp-photo-empty{align-items:center;color:var(--muted);display:flex;font-family:Georgia,serif;font-size:46px;justify-content:center}
.smp-vp-profile-page .pp-socials{display:flex;flex-wrap:wrap;gap:9px;margin-top:22px}
.smp-vp-profile-page .pp-socials a{align-items:center;background:var(--ink);border-radius:50%;color:#fff;display:inline-flex;font-size:11px;font-weight:800;height:35px;justify-content:center;letter-spacing:.04em;width:35px;text-transform:uppercase;transition:background .2s}
.smp-vp-profile-page .pp-socials a:hover{background:var(--pp-accent);color:#fff}
.smp-vp-profile-page .pp-section{border-top:1px solid var(--line);margin-top:34px;padding-top:26px}
.smp-vp-profile-page .pp-label{align-items:center;color:var(--ink);display:flex;font-size:12px;font-weight:700;gap:10px;letter-spacing:.18em;margin:0 0 16px;text-transform:uppercase}
.smp-vp-profile-page .pp-label:before{background:var(--pp-accent);content:"";display:block;height:2px;width:18px}
.smp-vp-profile-page .pp-facts{display:grid;gap:20px;grid-template-columns:repeat(3,minmax(0,1fr))}
.smp-vp-profile-page .pp-fact .k,.smp-vp-profile-page .ppc-meta .k{color:var(--muted);font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase}
.smp-vp-profile-page .pp-fact .v,.smp-vp-profile-page .ppc-meta .v{color:var(--ink);font-size:15px;font-weight:600;margin-top:5px}
.smp-vp-profile-page .pp-orgs{display:grid;gap:16px;grid-template-columns:repeat(2,minmax(0,1fr))}
.smp-vp-profile-page .pp-org{border:1px solid var(--line);border-radius:10px;padding:16px 18px}
.smp-vp-profile-page .pp-org .on{color:var(--ink);font-family:Georgia,serif;font-size:18px;font-weight:700}
.smp-vp-profile-page .pp-org .od{color:var(--muted);font-size:13px;line-height:1.5;margin-top:5px}
.smp-vp-profile-page .pp-arts{display:grid;gap:18px;grid-template-columns:repeat(3,minmax(0,1fr))}
.smp-vp-profile-page .pp-art{display:block}
.smp-vp-profile-page .pp-art .athumb{aspect-ratio:16/10;background:#ececed;border-radius:8px;display:block;filter:grayscale(100%);object-fit:cover;width:100%}
.smp-vp-profile-page .pp-art .ah{color:var(--ink);font-family:Georgia,serif;font-size:15.5px;font-weight:600;line-height:1.32;margin:10px 0 0}
.smp-vp-profile-page .pp-art .am{color:var(--muted);font-size:11px;letter-spacing:.1em;margin-top:8px;text-transform:uppercase}
.smp-vp-profile-page.pp-a .ppa-inner{margin:0 auto;max-width:var(--pp-grid-width,none);padding:50px 0;width:100%}
.smp-vp-profile-page .ppa-hero{align-items:start;display:grid;gap:40px;grid-template-columns:1fr 260px}
.smp-vp-profile-page .ppa-hero .pp-bio{margin-top:20px}
.smp-vp-profile-page .ppa-photo{border-radius:14px;height:320px;width:260px}
.smp-vp-profile-page.pp-b .ppb-inner{margin:0 auto;max-width:var(--pp-grid-width,none);padding:44px 0;width:100%}
.smp-vp-profile-page .ppb-hero{align-items:center;border-bottom:1px solid var(--line);display:grid;gap:34px;grid-template-columns:210px minmax(0,1fr);padding-bottom:34px}
.smp-vp-profile-page .ppb-photo{border-radius:6px;height:240px;width:210px}
.smp-vp-profile-page.pp-b .ppb-intro .pp-name{font-family:inherit;font-weight:760;max-width:18ch}
.smp-vp-profile-page .ppb-layout{display:grid;gap:44px;grid-template-columns:minmax(0,1fr) 310px}
.smp-vp-profile-page .ppb-main,.smp-vp-profile-page .ppb-aside{min-width:0}
.smp-vp-profile-page .ppb-aside{background:var(--soft);padding:0 24px 28px}
.smp-vp-profile-page.pp-b .ppb-aside .pp-section{margin-top:28px;padding-top:22px}
.smp-vp-profile-page.pp-b .ppb-aside .pp-facts,.smp-vp-profile-page.pp-b .ppb-aside .pp-orgs{grid-template-columns:1fr}
.smp-vp-profile-page.pp-b .ppb-aside .pp-org{background:#fff;border-radius:6px}
.smp-vp-profile-page.pp-c .ppc-inner{display:grid;gap:0;grid-template-columns:268px 1fr;margin:0 auto;max-width:var(--pp-grid-width,none);width:100%}
.smp-vp-profile-page .ppc-side{background:var(--soft);border-right:1px solid var(--line);padding:34px 28px}
.smp-vp-profile-page .ppc-photo{aspect-ratio:4/5;border-radius:10px;width:100%}
.smp-vp-profile-page.pp-c .ppc-side .pp-name{display:block;font-size:27px;margin-top:18px}
.smp-vp-profile-page.pp-c .ppc-side .pp-verif{display:inline-block;margin-left:6px;vertical-align:middle}
.smp-vp-profile-page.pp-c .ppc-side .pp-role{font-size:11px}
.smp-vp-profile-page .ppc-meta{display:grid;gap:12px;margin-top:24px}
.smp-vp-profile-page .ppc-meta .pp-facts{display:grid;gap:14px;grid-template-columns:1fr}
.smp-vp-profile-page .ppc-main{padding:42px 44px}
.smp-vp-profile-page .ppc-main .pp-section:first-child{border-top:0;margin-top:0;padding-top:0}
@media(max-width:740px){.smp-vp-profile-page.pp-a .ppa-inner,.smp-vp-profile-page.pp-b .ppb-inner{padding:34px 0}.smp-vp-profile-page .ppa-hero,.smp-vp-profile-page .ppb-hero,.smp-vp-profile-page .ppb-layout{grid-template-columns:1fr}.smp-vp-profile-page .ppa-photo{height:220px;order:-1;width:180px}.smp-vp-profile-page .ppb-photo{height:220px;width:190px}.smp-vp-profile-page .pp-name{font-size:var(--pp-mobile-heading-size,34px)}.smp-vp-profile-page .pp-facts,.smp-vp-profile-page .pp-arts{grid-template-columns:1fr}.smp-vp-profile-page .ppb-aside{padding:1px 18px 24px}.smp-vp-profile-page.pp-c .ppc-inner{grid-template-columns:1fr}.smp-vp-profile-page .ppc-side{border-bottom:1px solid var(--line);border-right:0}.smp-vp-profile-page .ppc-main{padding:32px 0}}
';
}

function smp_vp_profile_page_sample_profile_id(): int {
    $slug = smp_vp_profile_page_cpt_slug();
    $post = get_page_by_path( 'michael-peres', OBJECT, $slug );
    if ( $post instanceof \WP_Post ) {
        return (int) $post->ID;
    }

    $posts = get_posts(
        [
            'post_type'      => $slug,
            'post_status'    => [ 'publish', 'draft', 'private' ],
            'posts_per_page' => 1,
            'orderby'        => 'modified',
            'order'          => 'DESC',
            'fields'         => 'ids',
        ]
    );

    return ! empty( $posts ) ? absint( $posts[0] ) : 0;
}

function smp_vp_profile_page_preview_data( int $sample_id = 0 ): array {
    if ( $sample_id > 0 ) {
        return smp_vp_profile_page_data( $sample_id );
    }

    return [
        'id'             => 0,
        'name'           => 'Alex Morgan',
        'role'           => 'Founder and Industry Leader',
        'image'          => '',
        'bio'            => '<p>Alex is a verified professional known for building thoughtful organizations and contributing practical expertise to the industry.</p>',
        'facts'          => [
            'Based'     => 'New York, United States',
            'Education' => 'State University',
            'Field'     => 'Media and Technology',
        ],
        'organizations'  => [
            [ 'name' => 'Northstar Group', 'description' => 'Independent organization focused on durable growth.', 'url' => '' ],
            [ 'name' => 'Civic Studio', 'description' => 'Research and strategy for public-facing brands.', 'url' => '' ],
        ],
        'related_posts'  => [],
        'authored_posts' => [],
        'socials'        => [
            [ 'key' => 'linkedin', 'label' => 'LinkedIn', 'url' => '#' ],
            [ 'key' => 'website', 'label' => 'Website', 'url' => '#' ],
        ],
    ];
}

function smp_vp_profile_page_template_selector( array $settings, int $sample_id, string $sample_url ): string {
    if ( ! class_exists( TemplateSelectionControl::class ) ) {
        return '';
    }

    $choices = [];
    $data    = smp_vp_profile_page_preview_data( $sample_id );
    foreach ( smp_vp_profile_page_templates() as $key => $template ) {
        $actions = CoreUi::copy_button( '[verified_profile_page template="' . $key . '"]', 'Copy shortcode' );
        if ( '' !== $sample_url ) {
            $preview_url = add_query_arg( 'smp_vp_template_preview', $key, $sample_url );
            $actions    .= CoreUi::external_link( $preview_url, 'View live', 'hpc-button secondary' );
        }

        $choices[ $key ] = [
            'label'          => (string) $template['short_label'],
            'description'    => (string) $template['description'],
            'preview_html'   => smp_vp_render_profile_page_template_data( $data, $key ),
            'preview_width'  => 1120,
            'preview_height' => 300,
            'actions_html'   => $actions,
        ];
    }

    return TemplateSelectionControl::render(
        [
            'id'          => 'smp-vp-profile-page-template-selector',
            'name'        => 'smp_vp_profile_page_template',
            'value'       => (string) $settings['selected_template'],
            'title'       => 'Verified Profile page design',
            'description' => 'Choose the complete design used on individual Verified Profile pages. Every available template is shown here.',
            'templates'   => $choices,
            'custom'      => [
                'label'              => 'No plugin design',
                'description'        => 'Disable automatic page takeover, default shortcode markup, and all plugin template styling.',
                'toggle_label'       => 'No plugin design',
                'toggle_description' => 'Turn this on to disable every page design choice and build the profile page yourself.',
            ],
            'custom_control' => 'toggle',
            'columns'        => 3,
            'preview_height' => 300,
            'preview_width'  => 1120,
            'input_class'    => 'smp-vp-profile-page-template-setting',
        ]
    );
}

function smp_vp_profile_page_render_settings(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        echo '<div class="notice notice-error"><p>Insufficient permissions.</p></div>';
        return;
    }

    CoreUi::render_assets();
    $settings   = smp_vp_profile_page_settings();
    $templates  = smp_vp_profile_page_templates();
    $sample_id  = smp_vp_profile_page_sample_profile_id();
    $sample_url = $sample_id ? (string) get_permalink( $sample_id ) : '';
    $nonce      = wp_create_nonce( SMP_VP_PROFILE_PAGE_NONCE );
    $labels     = [ SMP_VP_PROFILE_PAGE_CUSTOM_TEMPLATE => 'No plugin design' ];
    foreach ( $templates as $key => $template ) {
        $labels[ $key ] = (string) $template['short_label'];
    }

    ob_start();
    ?>
    <div class="smp-vp-profile-page-settings" id="smp-vp-profile-pages" data-nonce="<?php echo esc_attr( $nonce ); ?>" data-hpc-typography-scope>
        <p class="smp-vp-page-panel-intro">Choose the complete Verified Profile page design and its shared visual settings. Previews use the same renderer as the frontend.</p>
        <div class="smp-vp-page-sections">
            <?php echo smp_vp_profile_page_template_selector( $settings, $sample_id, $sample_url ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <section class="smp-vp-page-setting-section">
                <h3>Output</h3>
                <div class="smp-vp-page-output-grid">
                    <?php echo CoreUi::toggle( 'smp_vp_profile_page_enabled', ! empty( $settings['enabled'] ), 'Enable plugin profile-page output', [ 'id' => 'smp-vp-page-enabled' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <label class="smp-vp-page-field" for="smp-vp-page-render-mode"><span>Frontend mode</span><select id="smp-vp-page-render-mode"><option value="auto" <?php selected( $settings['render_mode'], 'auto' ); ?>>Automatic profile-page replacement</option><option value="shortcode" <?php selected( $settings['render_mode'], 'shortcode' ); ?>>Shortcode only</option></select></label>
                    <label class="smp-vp-page-field" for="smp-vp-page-grid-width"><span>Maximum grid width</span><input id="smp-vp-page-grid-width" type="number" min="0" max="2400" step="10" value="<?php echo esc_attr( (string) $settings['grid_width'] ); ?>"><small>Use 0 to inherit the theme content width.</small></label>
                </div>
                <p class="smp-vp-page-custom-note" data-smp-vp-page-custom-note<?php echo SMP_VP_PROFILE_PAGE_CUSTOM_TEMPLATE === $settings['selected_template'] ? '' : ' hidden'; ?>>Custom-design mode is active. Automatic takeover and default shortcode output are disabled; explicitly named template shortcodes remain available.</p>
            </section>
            <?php if ( class_exists( TypographyControl::class ) ) : ?>
                <?php echo TypographyControl::render( [
                    'prefix'      => 'profile_page',
                    'settings'    => $settings,
                    'defaults'    => false,
                    'title'       => 'Typography',
                    'description' => 'Adjust profile-page heading and body sizes, or preserve the surrounding site sizes.',
                    'input_class' => 'smp-vp-profile-page-typography-setting',
                    'font_size'   => [
                        [ 'key' => 'heading_font_size', 'label' => 'Heading size', 'min' => 24, 'max' => 84, 'suffix' => 'px' ],
                        [ 'key' => 'body_font_size', 'label' => 'Body size', 'min' => 12, 'max' => 28, 'suffix' => 'px' ],
                    ],
                ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php endif; ?>
            <?php echo smp_vp_profile_page_color_palette_html( $settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
        <div class="smp-vp-page-actions">
            <button type="button" class="hpc-button" id="smp-vp-page-save">Save Profile Page Settings</button>
            <?php echo CoreUi::copy_button( '[verified_profile_page]', 'Copy default shortcode' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php if ( '' !== $sample_url ) : ?><?php echo CoreUi::external_link( $sample_url, 'Open current profile', 'hpc-button secondary' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php endif; ?>
            <span class="smp-vp-page-log" id="smp-vp-page-log" role="status">Ready.</span>
        </div>
    </div>
    <?php
    $body = (string) ob_get_clean();

    echo '<style>' . smp_vp_profile_page_admin_css() . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo CoreUi::collapsible( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        [
            'title'       => 'Verified Profile Pages',
            'body_html'   => $body,
            'meta_html'   => CoreUi::pill( 'Selected: ' . ( $labels[ $settings['selected_template'] ] ?? $settings['selected_template'] ), 'dark' ),
            'open'        => true,
            'query_key'   => 'profile-pages',
            'class'       => 'smp-vp-profile-page-core-panel',
        ]
    );
    smp_vp_profile_page_admin_script( $labels );
}

function smp_vp_profile_page_admin_css(): string {
    return '
.smp-vp-profile-page-core-panel{margin-top:18px}.smp-vp-profile-page-core-panel>.hpc-section-body{padding:0}.smp-vp-profile-page-settings{min-width:0}.smp-vp-page-panel-intro{color:#64748b;margin:0;padding:18px 20px 0}.smp-vp-page-sections{display:grid;gap:18px;padding:20px}.smp-vp-profile-page-settings .hpc-template-selection{margin:0}.smp-vp-profile-page-settings .hpc-template-selection-label{display:flex;font-weight:inherit;margin:0}.smp-vp-profile-page-settings .hpc-template-selection-preview .smp-vp-profile-page{min-height:620px}.smp-vp-profile-page-settings .hpc-template-selection-actions{min-height:58px}.smp-vp-page-setting-section{border:1px solid #d8dee8;border-radius:8px;padding:16px}.smp-vp-page-setting-section>h3{font-size:15px;margin:0 0 14px}.smp-vp-page-output-grid{align-items:start;display:grid;gap:14px;grid-template-columns:repeat(3,minmax(0,1fr))}.smp-vp-page-output-grid>.hpc-toggle{margin-top:25px}.smp-vp-page-field{display:grid;gap:6px;margin:0}.smp-vp-page-field>span{color:#314056;font-size:12px;font-weight:800;text-transform:uppercase}.smp-vp-page-field select,.smp-vp-page-field input{min-height:40px;width:100%}.smp-vp-page-field small{color:#64748b}.smp-vp-page-custom-note{background:#fff7e5;border:1px solid #e7c86d;border-radius:6px;color:#694d00;margin:14px 0 0;padding:10px 12px}.smp-vp-page-color-tools{display:grid;gap:14px}.smp-vp-page-color-tools .hpc-detail-card{margin:0}.smp-vp-page-supporting-colors{display:grid;gap:14px;grid-template-columns:repeat(2,minmax(0,1fr));padding:14px}.smp-vp-page-actions{align-items:center;background:#f6f7f7;border-top:1px solid #e4e9f0;display:flex;flex-wrap:wrap;gap:10px;padding:16px 20px}.smp-vp-page-log{color:#475569;font-weight:700;margin-left:auto}.smp-vp-profile-page-settings.is-custom .smp-vp-page-output-grid{opacity:.7}@media(max-width:1100px){.smp-vp-page-output-grid{grid-template-columns:1fr 1fr}.smp-vp-page-output-grid>.hpc-toggle{margin-top:0}}@media(max-width:782px){.smp-vp-page-output-grid,.smp-vp-page-supporting-colors{grid-template-columns:1fr}.smp-vp-page-log{margin-left:0;width:100%}}
';
}

function smp_vp_profile_page_admin_script( array $labels ): void {
    ?>
    <script>
    (function($){
        const $root = $("#smp-vp-profile-pages");
        if (!$root.length) return;
        const labels = <?php echo wp_json_encode( $labels ); ?>;
        const custom = <?php echo wp_json_encode( SMP_VP_PROFILE_PAGE_CUSTOM_TEMPLATE ); ?>;
        const $log = $("#smp-vp-page-log");
        function selected(){ return String($root.find(".smp-vp-profile-page-template-setting:checked").val() || ""); }
        function color(key){ return String($root.find('[data-hpc-color-hex-input][data-key="'+key+'"]').first().val() || ""); }
        function collect(){
            return {
                enabled: $("#smp-vp-page-enabled").is(":checked") ? 1 : 0,
                selected_template: selected(),
                render_mode: $("#smp-vp-page-render-mode").val(),
                grid_width: $("#smp-vp-page-grid-width").val(),
                primary_color: color("primary_color"), ink_color: color("ink_color"), body_color: color("body_color"),
                muted_color: color("muted_color"), line_color: color("line_color"), soft_color: color("soft_color"),
                heading_font_size: $root.find('[data-key="heading_font_size"]').first().val(),
                body_font_size: $root.find('[data-key="body_font_size"]').first().val(),
                profile_page_preserve_font_size: $root.find('[data-key="profile_page_preserve_font_size"]').is(":checked") ? 1 : 0
            };
        }
        function sync(){
            const isCustom = selected() === custom;
            if (isCustom) { $("#smp-vp-page-enabled").prop("checked", false); $("#smp-vp-page-render-mode").val("shortcode"); }
            $root.toggleClass("is-custom", isCustom);
            $root.find("[data-smp-vp-page-custom-note]").prop("hidden", !isCustom);
            $root.closest(".smp-vp-profile-page-core-panel").find(".hpc-pill").first().text("Selected: " + (labels[selected()] || selected()));
        }
        function syncPreviews(){
            const preserve = $root.find('[data-key="profile_page_preserve_font_size"]').is(":checked");
            const heading = Math.max(24, Math.min(84, parseInt($root.find('[data-key="heading_font_size"]').first().val(), 10) || 46));
            const body = Math.max(12, Math.min(28, parseInt($root.find('[data-key="body_font_size"]').first().val(), 10) || 17));
            const width = Math.max(0, Math.min(2400, parseInt($("#smp-vp-page-grid-width").val(), 10) || 0));
            $root.find(".hpc-template-selection-preview .smp-vp-profile-page").each(function(){
                this.style.setProperty("--pp-accent", color("primary_color") || "#2f55ff");
                this.style.setProperty("--ink", color("ink_color") || "#101010");
                this.style.setProperty("--body", color("body_color") || "#565656");
                this.style.setProperty("--muted", color("muted_color") || "#8a8a8a");
                this.style.setProperty("--line", color("line_color") || "#e8e4df");
                this.style.setProperty("--soft", color("soft_color") || "#faf8f5");
                this.style.setProperty("--pp-grid-width", width ? width + "px" : "none");
                this.style.setProperty("--pp-heading-size", preserve ? "inherit" : heading + "px");
                this.style.setProperty("--pp-mobile-heading-size", preserve ? "inherit" : Math.min(34, heading) + "px");
                this.style.setProperty("--pp-body-size", preserve ? "inherit" : body + "px");
            });
        }
        function save(button, message){
            const $button = $(button || "#smp-vp-page-save"), original = $button.text();
            $button.prop("disabled", true).text("Saving..."); $log.text("Saving profile page settings...");
            return $.post(ajaxurl, {action:"smp_vp_profile_page_save", nonce:$root.data("nonce"), settings:collect()})
                .done(function(response){
                    if (response && response.success) { $log.text(message || response.data.message || "Saved."); sync(); return; }
                    $log.text((response && response.data && response.data.message) || "Save failed.");
                })
                .fail(function(){ $log.text("Save request failed."); })
                .always(function(){ $button.prop("disabled", false).text(original); });
        }
        $root.on("change", ".smp-vp-profile-page-template-setting", function(){ sync(); save(document.getElementById("smp-vp-page-save"), "Template selection saved."); });
        $root.on("click", "#smp-vp-page-save", function(){ save(this); });
        $root.on("input change", ".smp-vp-page-color,.smp-vp-profile-page-typography-setting,#smp-vp-page-grid-width", syncPreviews);
        sync(); syncPreviews();
    })(jQuery);
    </script>
    <?php
}

function smp_vp_ajax_profile_page_save(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => 'Insufficient permissions.' ], 403 );
    }

    check_ajax_referer( SMP_VP_PROFILE_PAGE_NONCE, 'nonce' );

    $input    = isset( $_POST['settings'] ) && is_array( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : [];
    $settings = smp_vp_profile_page_sanitize( $input );

    update_option( SMP_VP_PROFILE_PAGE_OPTION, $settings, false );
    wp_send_json_success( [ 'message' => 'Profile page settings saved.', 'settings' => $settings ] );
}
