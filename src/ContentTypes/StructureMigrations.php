<?php

declare( strict_types=1 );

namespace SMP\VerifiedProfiles\ContentTypes;

use Hexa\PluginCore\CoreContracts\ModuleInterface;

defined( 'ABSPATH' ) || exit;

final class StructureMigrations implements ModuleInterface {
    public function register(): void {
        add_action( 'init', [ VerifiedProfileStructures::class, 'migrate_legacy_content_type_settings' ], 3 );
        add_action( 'init', [ VerifiedProfileStructures::class, 'migrate_legacy_acf_settings' ], 3 );
    }
}
