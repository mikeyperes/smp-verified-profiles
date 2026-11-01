<?php

declare( strict_types=1 );

namespace SMP\VerifiedProfiles\Elementor;

use Elementor\Controls_Manager;
use Elementor\Core\DynamicTags\Tag;
use Elementor\Modules\DynamicTags\Module as TagsModule;
use SMP\VerifiedProfiles\ProfileTypes\ProfileTypes;

defined( 'ABSPATH' ) || exit;

/**
 * A link to the profile a post points to (for example an episode's guest):
 * "<label> <name> →", read from a field that holds the profile's ID.
 */
final class LinkedProfileTag extends Tag {
    public function get_name(): string {
        return 'smp-vp-linked-profile';
    }

    public function get_title(): string {
        return 'Linked profile';
    }

    public function get_group(): string {
        return DynamicTags::GROUP;
    }

    public function get_categories(): array {
        return [ TagsModule::TEXT_CATEGORY ];
    }

    protected function register_controls(): void {
        $this->add_control( 'key', [ 'label' => 'Field holding the profile', 'type' => Controls_Manager::TEXT, 'default' => 'profiles_0_profile' ] );
        $this->add_control( 'label', [ 'label' => 'Label before the name', 'type' => Controls_Manager::TEXT, 'default' => 'Guest' ] );
    }

    public function render(): void {
        echo self::html( (int) get_the_ID(), (string) $this->get_settings( 'key' ), (string) $this->get_settings( 'label' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in html()
    }

    public static function html( int $post_id, string $key, string $label ): string {
        $raw = $post_id > 0 ? get_post_meta( $post_id, sanitize_key( $key ), true ) : '';
        if ( is_array( $raw ) ) {
            $raw = reset( $raw );
        }
        $profile_id = absint( is_object( $raw ) && isset( $raw->ID ) ? $raw->ID : $raw );
        if ( ! $profile_id || ! ProfileTypes::is_profile_type( (string) get_post_type( $profile_id ) ) ) {
            return '';
        }
        $url   = get_permalink( $profile_id );
        $title = get_the_title( $profile_id );
        if ( ! $url || '' === $title ) {
            return '';
        }

        return sprintf(
            '<a href="%1$s"><span class="mpp-guest-k">%2$s</span><span class="mpp-guest-n">%3$s</span><span class="mpp-guest-arrow" aria-hidden="true">&#8594;</span><span class="screen-reader-text"> — view the verified profile</span></a>',
            esc_url( $url ),
            esc_html( $label ),
            esc_html( $title )
        );
    }
}
