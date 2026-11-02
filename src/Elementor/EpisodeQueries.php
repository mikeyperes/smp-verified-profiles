<?php
namespace SMP\VerifiedProfiles\Elementor;
/** @deprecated Episode relationships are owned by SMP Core Podcast Integration. */
final class EpisodeQueries {
    public function register(): void {}
    public static function __callStatic(string $name, array $arguments) {
        $owner = \SMP\Podcast\Frontend\EpisodeData::class;
        return class_exists($owner) && is_callable([$owner, $name]) ? $owner::$name(...$arguments) : [];
    }
}
