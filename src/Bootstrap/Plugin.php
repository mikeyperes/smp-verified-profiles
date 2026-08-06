<?php

declare( strict_types=1 );

namespace SMP\VerifiedProfiles\Bootstrap;

use Hexa\PluginCore\CoreBootstrap\CoreBootstrap;
use Hexa\PluginCore\CoreContracts\RegisterMethodModule;
use Hexa\PluginCore\CorePackageUpdates\CorePackageAjaxController;
use Hexa\PluginCore\CoreRuntime\PluginContext;
use Hexa\PluginCore\PluginChecks\PluginChecksAjaxController;
use Hexa\PluginCore\PluginUpdates\GitHubPluginUpdater;
use Hexa\PluginCore\PluginUpdates\UpdaterAjaxController;
use Hexa\PluginCore\SnippetRegistry\SnippetAjaxController;
use Hexa\PluginCore\WpAdminTabs\CoreTabConfig;
use Hexa\PluginCore\WpAdminTabs\CoreTabModule;
use SMP\VerifiedProfiles\Admin\AdminAjaxHandlers;
use SMP\VerifiedProfiles\Admin\AdminAjaxModule;
use SMP\VerifiedProfiles\ContentTypes\StructureMigrations;
use SMP\VerifiedProfiles\ContentTypes\VerifiedProfileStructures;
use SMP\VerifiedProfiles\Infrastructure\LegacyUpdaterAjaxAlias;
use SMP\VerifiedProfiles\Infrastructure\Updates;
use SMP\VerifiedProfiles\Snippets\DeferredSnippetModule;
use SMP\VerifiedProfiles\Snippets\SnippetCatalog;
use SMP\VerifiedProfiles\UserRoles\VerifiedProfileManagerRole;
use smp_verified_profiles\Config;

defined( 'ABSPATH' ) || exit;

final class Plugin {
    private static ?self $instance = null;

    private ?PluginContext $context = null;
    private ?CoreBootstrap $bootstrap = null;
    private ?SnippetAjaxController $snippet_controller = null;
    private ?AdminAjaxHandlers $ajax_handlers = null;
    private bool $booted = false;

    private function __construct() {
    }

    public static function instance(): self {
        if ( ! self::$instance instanceof self ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function boot(): void {
        if ( $this->booted ) {
            return;
        }

        VerifiedProfileStructures::prepare();

        $context   = $this->context();
        $bootstrap = new CoreBootstrap( $context );
        $bootstrap
            ->add_module( new GitHubPluginUpdater( Updates::plugin_config() ) )
            ->add_module( VerifiedProfileStructures::content_types() )
            ->add_module( VerifiedProfileStructures::acf_groups() )
            ->add_module( new StructureMigrations() )
            ->add_module( new VerifiedProfileManagerRole() );

        if ( $this->is_admin_runtime() ) {
            $updater_ajax = new UpdaterAjaxController( Updates::plugin_config() );

            $bootstrap
                ->add_module( $updater_ajax )
                ->add_module( new LegacyUpdaterAjaxAlias( $updater_ajax ) )
                ->add_module( new CorePackageAjaxController( Updates::core_config() ) );

            $bootstrap
                ->add_module(
                    new DeferredSnippetModule(
                        fn(): SnippetAjaxController => $this->snippet_controller(),
                        fn(): bool => $this->needs_snippet_runtime()
                    )
                )
                ->add_module( new AdminAjaxModule( $this->ajax_handlers() ) )
                ->add_module( new RegisterMethodModule( $this->plugin_checks_module() ) )
                ->add_module( $this->core_tab_module() );
        }

        $bootstrap->boot();

        $this->bootstrap = $bootstrap;
        $this->booted    = true;

        do_action( 'smp_verified_profiles_core_booted', $context, $bootstrap );
    }

    public function context(): PluginContext {
        if ( $this->context instanceof PluginContext ) {
            return $this->context;
        }

        $plugin_file = dirname( __DIR__, 2 ) . '/smp-verified-profiles.php';
        $basename    = function_exists( 'plugin_basename' )
            ? plugin_basename( $plugin_file )
            : Config::get_plugin_basename();

        $this->context = new PluginContext(
            [
                'slug'        => Config::$plugin_folder_name,
                'basename'    => $basename,
                'version'     => Config::$plugin_version,
                'path'        => dirname( __DIR__, 2 ) . '/',
                'url'         => plugin_dir_url( $plugin_file ),
                'github_repo' => Config::$github_repo,
                'admin_page'  => Config::$settings_page_slug,
                'capability'  => Config::$settings_page_capability,
            ]
        );

        return $this->context;
    }

    public function bootstrap(): ?CoreBootstrap {
        return $this->bootstrap;
    }

    public function snippet_controller(): SnippetAjaxController {
        if ( ! $this->snippet_controller instanceof SnippetAjaxController ) {
            $this->snippet_controller = new SnippetAjaxController(
                SnippetCatalog::registry(),
                [
                    'capability'    => Config::$settings_page_capability,
                    'nonce_action'  => Config::$ajax_nonce_action,
                    'nonce_field'   => Config::$ajax_nonce_field,
                    'toggle_action' => 'smp_vp_toggle_snippet',
                    'test_action'   => 'smp_vp_test_snippet',
                ]
            );
        }

        return $this->snippet_controller;
    }

    public function ajax_handlers(): AdminAjaxHandlers {
        if ( ! $this->ajax_handlers instanceof AdminAjaxHandlers ) {
            $this->ajax_handlers = new AdminAjaxHandlers();
        }

        return $this->ajax_handlers;
    }

    private function plugin_checks_module(): PluginChecksAjaxController {
        require_once dirname( __DIR__, 2 ) . '/settings-dashboard-plugin-checks.php';

        return new PluginChecksAjaxController(
            \smp_verified_profiles\smp_vp_plugin_check_definitions(),
            [
                'capability'    => 'update_plugins',
                'nonce_action'  => Config::$ajax_nonce_action,
                'nonce_field'   => Config::$ajax_nonce_field,
                'action_prefix' => 'smp_vp_plugin_checks',
            ]
        );
    }

    private function core_tab_module(): CoreTabModule {
        $plugin_root = dirname( __DIR__, 2 );

        return new CoreTabModule(
            new CoreTabConfig(
                [
                    'tab_id'        => 'hexa-core',
                    'tabs_filter'   => 'smp_vp_dashboard_tabs',
                    'render_filter' => 'smp_vp_render_dashboard_tab',
                    'label'         => 'Hexa WP Core',
                    'capability'    => Config::$settings_page_capability,
                    'core_root'     => $plugin_root . '/lib/hexa-wordpress-plugin-core',
                    'readme_path'   => $plugin_root . '/lib/hexa-wordpress-plugin-core/README.md',
                    'library_path'  => $plugin_root . '/HEXA_PLUGIN_CORE_LIBRARY.md',
                ]
            )
        );
    }

    private function is_admin_runtime(): bool {
        return is_admin() || wp_doing_ajax();
    }

    private function needs_snippet_runtime(): bool {
        if ( function_exists( '\\smp_verified_profiles\\smp_vp_is_settings_dashboard_request' )
            && \smp_verified_profiles\smp_vp_is_settings_dashboard_request()
        ) {
            return true;
        }

        $action = isset( $_REQUEST['action'] ) && is_scalar( $_REQUEST['action'] )
            ? sanitize_key( wp_unslash( (string) $_REQUEST['action'] ) )
            : '';

        return in_array( $action, [ 'smp_vp_toggle_snippet', 'smp_vp_test_snippet', 'smp_verified_profiles_toggle_snippet' ], true );
    }
}
