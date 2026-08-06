<?php

declare( strict_types=1 );

namespace SMP\VerifiedProfiles\Snippets;

use Hexa\PluginCore\CoreContracts\ModuleInterface;
use SMP\VerifiedProfiles\ContentTypes\VerifiedProfileStructures;

defined( 'ABSPATH' ) || exit;

final class SnippetStructureSync implements ModuleInterface {
    /** @var array<string,bool>|null */
    private ?array $managed_options = null;

    public function register(): void {
        add_action( 'added_option', [ $this, 'option_added' ], 10, 2 );
        add_action( 'updated_option', [ $this, 'option_updated' ], 10, 3 );
    }

    public function option_added( string $option, mixed $value ): void {
        $this->sync( $option, $value );
    }

    public function option_updated( string $option, mixed $old_value, mixed $value ): void {
        unset( $old_value );
        $this->sync( $option, $value );
    }

    private function sync( string $option, mixed $value ): void {
        if ( ! isset( $this->managed_options()[ $option ] ) ) {
            return;
        }

        VerifiedProfileStructures::sync_legacy_snippet( $option, (bool) $value );
    }

    /** @return array<string,bool> */
    private function managed_options(): array {
        if ( is_array( $this->managed_options ) ) {
            return $this->managed_options;
        }

        $this->managed_options = [];
        foreach ( [ 'acf', 'admin', 'non_admin' ] as $type ) {
            foreach ( \smp_verified_profiles\get_snippets( $type ) as $snippet ) {
                $id = isset( $snippet['id'] ) ? sanitize_key( (string) $snippet['id'] ) : '';
                if ( '' !== $id ) {
                    $this->managed_options[ $id ] = true;
                }
            }
        }

        return $this->managed_options;
    }
}
