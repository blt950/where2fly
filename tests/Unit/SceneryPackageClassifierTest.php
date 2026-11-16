<?php

namespace Tests\Unit;

use App\Services\Msfs\SceneryPackageClassifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SceneryPackageClassifierTest extends TestCase
{
    private const KNOWN = ['KJFK', 'EGLL', 'ENBG', 'EFHK', 'EGKK', 'EDDK', 'ESSA', 'KASE', 'ZUDC', 'ENGM'];

    private function classify(array $packages, bool $includeNative = true, array $known = self::KNOWN, array $config = []): array
    {
        $lookup = function (array $codes) use ($known) {
            $hits = array_intersect($codes, $known);
            // C53 stands for the local codes: it resolves to the airport's own ICAO
            $local = in_array('C53', $codes, true) && in_array('KC53', $known, true) ? ['C53' => 'KC53'] : [];

            return array_combine($hits, $hits) + $local;
        };

        $classifier = new SceneryPackageClassifier($config + require __DIR__ . '/../../config/msfs_scenery.php', $lookup);

        return $classifier->classify(
            array_map(fn ($package) => ['name' => $package[0], 'active' => array_key_exists(1, $package) ? $package[1] : 'Activated'], $packages),
            $includeNative
        );
    }

    public static function airportPackages(): array
    {
        return [
            'native' => ['fs24-asobo-airport-kjfk-new-york-jfk', 'native', 'KJFK'],
            'community' => ['communityfs24-blt950-airport-enbg-gronneviksoren', 'community', 'ENBG'],
            'community override' => ['communityfs20-gatwick', 'community', 'EGKK'],
            'native override' => ['fs24-microsoft-eddk-cologne-bonn', 'native', 'EDDK'],
            'marketplace' => ['fs24-aerosoft-airport-engm-oslo', 'marketplace', 'ENGM'],
            'fs20 package still active' => ['fs20-asobo-airport-egll-heathrow', 'native', 'EGLL'],
            'mixed case and padding' => [' CommunityFS24-BLT950-Airport-ENBG-Gronneviksoren ', 'community', 'ENBG'],
        ];
    }

    #[DataProvider('airportPackages')]
    public function test_an_airport_package_resolves_to_its_airport(string $package, string $category, string $icao): void
    {
        $result = $this->classify([[$package]]);

        $this->assertSame($icao, $result['suggestion']);
        $this->assertSame([['icao' => $icao, 'category' => $category, 'also_native' => false, 'packages' => [trim($package)]]], $result['airports']);
        $this->assertSame([], $result['errors']);
        $this->assertSame([], $result['needs_review']);
    }

    public static function ignoredPackages(): array
    {
        return [
            'model pack with a fake ICAO' => ['communityfs20-blt950-airport-enxx-models'],
            'landing challenge' => ['fs24-asobo-landingchallenge-kase-aspen'],
            'lepack landing challenge' => ['fs24-asobo-lepack-landingchallenge-lfpg-cdg'],
            'model library' => ['fs24-asobo-modellib-airport-generic'],
            'core content' => ['fs24-fs-base-genericairports'],
            'core content outside fs-base' => ['fs24-fs-devmode'],
            'asset pack' => ['communityfs20-wombiiactual-assetpack-enxx'],
            'aircraft' => ['communityfs24-pmdg-aircraft-77w'],
            'no known prefix' => ['some-airport-kjfk-thing'],
        ];
    }

    #[DataProvider('ignoredPackages')]
    public function test_packages_that_are_not_scenery_are_ignored(string $package): void
    {
        $result = $this->classify([[$package]]);

        $this->assertSame('', $result['suggestion']);
        $this->assertSame([], $result['errors']);
        $this->assertSame([], $result['needs_review']);
        $this->assertSame(1, $result['stats']['ignored']);
    }

    public function test_only_activated_packages_count(): void
    {
        $result = $this->classify([
            ['fs20-asobo-airport-egll-heathrow', 'SystemDisabled'],
            ['fs24-asobo-airport-kjfk-new-york-jfk', 'UserDisabled'],
            ['communityfs20-aerosoft-airport-engm-oslo', 'ACTIVATED'],
        ]);

        $this->assertSame('ENGM', $result['suggestion']);
        $this->assertSame(1, $result['stats']['enabled']);
        $this->assertSame(['SystemDisabled' => 1, 'UserDisabled' => 1], $result['stats']['disabled_by_state']);
        $this->assertSame(['ACTIVATED', 'SystemDisabled', 'UserDisabled'], $result['stats']['states_seen']);
    }

    public function test_a_guess_from_the_name_needs_review_and_stays_out_of_the_suggestion(): void
    {
        $result = $this->classify([['communityfs20-efhk-helsinki'], ['communityfs20-kui-zudc'], ['communityfs20-kui-zudcsat'], ['communityfs20-fsltl-traffic-base']]);

        $this->assertSame('', $result['suggestion']);
        $this->assertSame([
            ['package' => 'communityfs20-efhk-helsinki', 'suggested_icao' => 'EFHK', 'reason' => 'icao_guessed_from_name', 'category' => 'community'],
            ['package' => 'communityfs20-kui-zudc', 'suggested_icao' => 'ZUDC', 'reason' => 'icao_guessed_from_name', 'category' => 'community'],
            ['package' => 'communityfs20-kui-zudcsat', 'suggested_icao' => 'ZUDC', 'reason' => 'icao_guessed_from_name', 'category' => 'community'],
            ['package' => 'communityfs20-fsltl-traffic-base', 'suggested_icao' => null, 'reason' => 'no_icao_found', 'category' => 'community'],
        ], $result['needs_review']);
    }

    public function test_native_packages_are_never_guessed_or_listed_for_review(): void
    {
        $result = $this->classify([['fs24-asobo-kase-services'], ['fs20-microsoft-airport-voloport']]);

        $this->assertSame([], $result['needs_review']);
        $this->assertSame(2, $result['stats']['native_unresolved']);
    }

    public function test_denylisted_tokens_are_not_guessed(): void
    {
        $result = $this->classify([['communityfs20-orbx-volanta']], known: ['ORBX']);

        $this->assertNull($result['needs_review'][0]['suggested_icao']);
    }

    public function test_an_identifier_missing_from_the_database_is_an_error(): void
    {
        $result = $this->classify([['fs24-asobo-airport-c53-lowerloon']]);

        $this->assertSame('', $result['suggestion']);
        $this->assertSame([['package' => 'fs24-asobo-airport-c53-lowerloon', 'icao' => 'C53', 'reason' => 'icao_not_in_database', 'category' => 'native']], $result['errors']);
    }

    public function test_a_local_identifier_is_listed_under_the_airports_icao(): void
    {
        $result = $this->classify([['fs24-asobo-airport-c53-lowerloon']], known: ['KC53']);

        $this->assertSame('KC53', $result['suggestion']);
        $this->assertSame('native', $result['airports'][0]['category']);
    }

    public function test_a_malformed_override_is_an_error(): void
    {
        $result = $this->classify([['communityfs20-broken']], config: ['package_overrides' => ['communityfs20-broken' => 'not an icao']]);

        $this->assertSame([['package' => 'communityfs20-broken', 'icao' => 'NOT AN ICAO', 'reason' => 'malformed_icao', 'category' => 'community']], $result['errors']);
    }

    public function test_the_last_duplicate_wins(): void
    {
        $result = $this->classify([
            ['fs24-asobo-airport-kjfk-new-york-jfk', 'Activated'],
            ['fs24-asobo-airport-egll-heathrow', 'SystemDisabled'],
            ['fs24-asobo-airport-kjfk-new-york-jfk', 'UserDisabled'],
            ['fs24-asobo-airport-egll-heathrow', 'Activated'],
        ]);

        $this->assertSame('EGLL', $result['suggestion']);
        $this->assertSame(4, $result['stats']['packages_total']);
        $this->assertSame(2, $result['stats']['duplicates_collapsed']);
        $this->assertSame(1, $result['stats']['enabled']);
    }

    public function test_entries_missing_a_name_or_state_are_skipped(): void
    {
        $result = $this->classify([[null, 'Activated'], ['fs24-asobo-airport-kjfk-new-york-jfk', null], ['fs24-asobo-airport-egll-heathrow']]);

        $this->assertSame('EGLL', $result['suggestion']);
        $this->assertSame(2, $result['stats']['skipped_incomplete']);
    }

    public function test_a_community_package_outranks_the_native_one_for_the_same_airport(): void
    {
        $packages = [['fs24-microsoft-airport-essa-arlanda'], ['communityfs20-orbx-airport-essa-stockholm-arlanda'], ['fs24-asobo-airport-kjfk-new-york-jfk']];

        $without = $this->classify($packages, includeNative: false);
        $this->assertSame('ESSA', $without['suggestion']);
        $this->assertSame(1, $without['count']);
        $this->assertSame(1, $without['stats']['native_only_available']);
        $this->assertSame(
            [['icao' => 'ESSA', 'category' => 'community', 'also_native' => true, 'packages' => ['fs24-microsoft-airport-essa-arlanda', 'communityfs20-orbx-airport-essa-stockholm-arlanda']]],
            $without['airports']
        );

        $with = $this->classify($packages, includeNative: true);
        $this->assertSame("ESSA\nKJFK", $with['suggestion']);
        $this->assertSame(0, $with['stats']['native_only_available']);
    }
}
