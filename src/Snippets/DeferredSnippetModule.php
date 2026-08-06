<?php

declare( strict_types=1 );

namespace SMP\VerifiedProfiles\Snippets;

use Hexa\PluginCore\CoreContracts\ModuleInterface;
use Hexa\PluginCore\SnippetRegistry\SnippetAjaxController;
use Hexa\PluginCore\WpAdminAjax\AjaxActionRegistry;
use smp_verified_profiles\Config;

defined( 'ABSPATH' ) || exit;

final class DeferredSnippetModule implements ModuleInterface {
    public function __construct(
        private mixed $controller_factory,
        private mixed $should_register
    ) {
        if ( ! is_callable( $controller_factory ) || ! is_callable( $should_register ) ) {
            throw new \InvalidArgumentException( 'Deferred snippet callbacks must be callable.' );
        }
    }

    public function register(): void {
        add_action( 'init', [ $this, 'register_runtime' ], 20 );
    }

    public function register_runtime(): void {
        if ( ! call_user_func( $this->should_register ) ) {
            return;
        }

        $controller = call_user_func( $this->controller_factory );
        if ( ! $controller instanceof SnippetAjaxController ) {
            return;
        }

        $controller->register();
        ( new SnippetStructureSync() )->register();

        ( new AjaxActionRegistry(
            [
                'capability'   => Config::$settings_page_capability,
                'nonce_action' => Config::$ajax_nonce_action,
                'nonce_field'  => Config::$ajax_nonce_field,
            ]
        ) )->register(
            [
                'smp_verified_profiles_toggle_snippet' => [ 'callback' => [ $controller, 'toggle' ] ],
            ]
        );
    }
}
