<?php

declare( strict_types=1 );

namespace SMP\VerifiedProfiles\Elementor;

use Elementor\Controls_Manager;
use Elementor\Core\DynamicTags\Tag;
use Elementor\Modules\DynamicTags\Module as TagsModule;

defined( 'ABSPATH' ) || exit;

/**
 * One field of the current post (a profile in a profile loop) as plain text,
 * with HTML removed; optionally hidden when it only repeats the post's name.
 */
final class ProfileFieldTag extends Tag {
    public function get_name(): string {
        return 'smp-vp-profile-field';
    }

    public function get_title(): string {
        return 'Profile field (plain text)';
    }

    public function get_group(): string {
        return DynamicTags::GROUP;
    }

    public function get_categories(): array {
        return [ TagsModule::TEXT_CATEGORY ];
    }

    protected function register_controls(): void {
        $this->add_control( 'key', [ 'label' => 'Field name', 'type' => Controls_Manager::TEXT, 'default' => 'title' ] );
        $this->add_control( 'hide_if_name', [ 'label' => 'Hide when it repeats the name', 'type' => Controls_Manager::SWITCHER, 'default' => '' ] );
    }

    public function render(): void {
        echo esc_html( self::value( (int) get_the_ID(), (string) $this->get_settings( 'key' ), 'yes' === $this->get_settings( 'hide_if_name' ) ) );
    }

    public static function value( int $post_id, string $key, bool $hide_if_name ): string {
        $key = sanitize_key( $key );
        if ( $post_id <= 0 || '' === $key ) {
            return '';
        }
        $raw   = get_post_meta( $post_id, $key, true );
        $value = trim( wp_strip_all_tags( is_scalar( $raw ) ? (string) $raw : '' ) );
        if ( $hide_if_name && 0 === strcasecmp( $value, trim( (string) get_the_title( $post_id ) ) ) ) {
            return '';
        }

        return $value;
    }
}
