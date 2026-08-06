<?php

declare( strict_types=1 );

namespace SMP\VerifiedProfiles\Infrastructure;

use Hexa\PluginCore\CoreContracts\ModuleInterface;
use Hexa\PluginCore\PluginUpdates\UpdaterAjaxController;

defined( 'ABSPATH' ) || exit;

final class LegacyUpdaterAjaxAlias implements ModuleInterface {
    public function __construct( private UpdaterAjaxController $controller ) {
    }

    public function register(): void {
        add_action( 'wp_ajax_smp_vp_force_plugin_update_check', [ $this->controller, 'force_update_check' ] );
    }
}
