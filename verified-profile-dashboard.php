<?php

namespace smp_verified_profiles;

defined( 'ABSPATH' ) || exit;

/*
 * Legacy compatibility file.
 *
 * The active Profiles Dashboard implementation lives in profile-manager-dashboard.php.
 * This file intentionally registers no callbacks so old include paths cannot redeclare
 * dashboard functions or reintroduce the removed HerForward-specific dashboard code.
 */
