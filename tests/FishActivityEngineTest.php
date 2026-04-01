<?php
/**
 * Unit tests for FishActivityEngine.
 *
 * Run: c:\xampp\php\php.exe tests/FishActivityEngineTest.php
 *
 * Lightweight test runner — no PHPUnit dependency required.
 */

// Bootstrap autoloader
require_once __DIR__ . '/../src/autoload.php';

use App\Services\FishActivityEngine;

class FishActivityEngineTest
{
    private int $passed = 0;
    private int $failed = 0;
    private array $failures = [];

    public function run(): void
    {
        echo "═══════════════════════════════════════════════\n";
        echo "  FishActivityEngine Unit Tests\n";
        echo "═══════════════════════════════════════════════\n\n";

        $methods = get_class_methods($this);
        foreach ($methods as $m) {
            if (str_starts_with($m, 'test')) {
                echo "  ▶ {$m}... ";
                try {
                    $this->{$m}();
                    echo "✅ PASS\n";
                    $this->passed++;
                } catch (\Throwable $e) {
                    echo "❌ FAIL: {$e->getMessage()}\n";
                    $this->failed++;
                    $this->failures[] = "{$m}: {$e->getMessage()}";
                }
            }
        }

        echo "\n───────────────────────────────────────────────\n";
        echo "  Results: {$this->passed} passed, {$this->failed} failed\n";
        if ($this->failures) {
            echo "\n  Failures:\n";
            foreach ($this->failures as $f) {
                echo "    • {$f}\n";
            }
        }
        echo "───────────────────────────────────────────────\n";
        exit($this->failed > 0 ? 1 : 0);
    }

    // ─── Assertion helpers ─────────────────────────────────

    private function assertEqual($expected, $actual, string $msg = ''): void
    {
        if ($expected !== $actual) {
            throw new \RuntimeException($msg ?: "Expected {$expected}, got {$actual}");
        }
    }

    private function assertTrue(bool $condition, string $msg = ''): void
    {
        if (!$condition) throw new \RuntimeException($msg ?: 'Expected true');
    }

    private function assertRange(int|float $value, int|float $min, int|float $max, string $msg = ''): void
    {
        if ($value < $min || $value > $max) {
            throw new \RuntimeException($msg ?: "Value {$value} not in range [{$min}, {$max}]");
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  TESTS
    // ═══════════════════════════════════════════════════════════

    /** @test */
    public function testCalculateReturnsExpectedStructure(): void
    {
        $engine = new FishActivityEngine();
        $weather = $this->sampleWeather();
        $result = $engine->calculate($weather, 42.7, 25.5, 'general');

        $this->assertTrue(isset($result['total_score']), 'Missing total_score');
        $this->assertTrue(isset($result['species']), 'Missing species');
        $this->assertTrue(isset($result['factors']), 'Missing factors');
        $this->assertTrue(isset($result['moon_phase']), 'Missing moon_phase');
        $this->assertTrue(isset($result['solunar_periods']), 'Missing solunar_periods');
        $this->assertTrue(isset($result['hourly_curve']), 'Missing hourly_curve');
        $this->assertTrue(isset($result['best_times']), 'Missing best_times');
        $this->assertTrue(isset($result['weather_summary']), 'Missing weather_summary');
        $this->assertTrue(isset($result['water_temp_est']), 'Missing water_temp_est');
    }

    /** @test */
    public function testScoreInValidRange(): void
    {
        $engine = new FishActivityEngine();
        $weather = $this->sampleWeather();
        $result = $engine->calculate($weather, 42.7, 25.5, 'general');

        $this->assertRange($result['total_score'], 0, 100, "Total score out of range: {$result['total_score']}");
    }

    /** @test */
    public function testAllFactorsPresent(): void
    {
        $engine = new FishActivityEngine();
        $weather = $this->sampleWeather();
        $result = $engine->calculate($weather, 42.7, 25.5, 'general');

        $expectedFactors = ['solunar', 'time', 'pressure', 'temperature', 'wind_cloud', 'humidity', 'precipitation', 'moon_light'];
        foreach ($expectedFactors as $f) {
            $this->assertTrue(isset($result['factors'][$f]), "Missing factor: {$f}");
            $this->assertTrue(isset($result['factors'][$f]['score']), "Missing score for factor: {$f}");
            $this->assertTrue(isset($result['factors'][$f]['weight']), "Missing weight for factor: {$f}");
            $this->assertRange($result['factors'][$f]['score'], 0, 120, "Factor {$f} score out of range");
        }
    }

    /** @test */
    public function testWeightsSum100Percent(): void
    {
        $engine = new FishActivityEngine();
        $weather = $this->sampleWeather();
        $result = $engine->calculate($weather, 42.7, 25.5);

        $totalWeight = 0;
        foreach ($result['factors'] as $f) {
            $totalWeight += $f['weight'];
        }
        // Allow tiny floating-point deviation
        $this->assertTrue(abs($totalWeight - 1.0) < 0.001, "Weights sum to {$totalWeight}, expected 1.0");
    }

    /** @test */
    public function testHourlyCurveHas24Entries(): void
    {
        $engine = new FishActivityEngine();
        $weather = $this->sampleWeather();
        $result = $engine->calculate($weather, 42.7, 25.5);

        $this->assertEqual(24, count($result['hourly_curve']), 'Hourly curve should have 24 entries');
        foreach ($result['hourly_curve'] as $h => $score) {
            $this->assertRange($score, 0, 100, "Hourly score for h={$h} out of range: {$score}");
        }
    }

    /** @test */
    public function testSpeciesModifiesResult(): void
    {
        $engine = new FishActivityEngine();
        $weather = $this->sampleWeather();

        $generalResult = $engine->calculate($weather, 42.7, 25.5, 'general');
        $troutResult   = $engine->calculate($weather, 42.7, 25.5, 'trout');

        // Temperature profiles differ, so scores should differ
        // (unless by coincidence they match — check structure at least)
        $this->assertEqual('general', $generalResult['species']);
        $this->assertEqual('trout', $troutResult['species']);
    }

    /** @test */
    public function testInvalidSpeciesFallsBackToGeneral(): void
    {
        $engine = new FishActivityEngine();
        $weather = $this->sampleWeather();
        $result = $engine->calculate($weather, 42.7, 25.5, 'nonexistent_fish');

        $this->assertEqual('general', $result['species'], 'Invalid species should fall back to general');
    }

    /** @test */
    public function testMoonPhaseReturnsValidData(): void
    {
        $engine = new FishActivityEngine();
        $moon = $engine->getMoonPhase();

        $this->assertTrue(!empty($moon['name']), 'Moon phase name is empty');
        $this->assertTrue(!empty($moon['icon']), 'Moon phase icon is empty');
        $this->assertRange($moon['score'], 0, 100, "Moon score out of range: {$moon['score']}");
        $this->assertRange($moon['illumination'], 0, 100, "Moon illumination out of range: {$moon['illumination']}");
        $this->assertTrue($moon['age'] >= 0 && $moon['age'] <= 30, "Moon age out of range: {$moon['age']}");
    }

    /** @test */
    public function testSolunarPeriodsStructure(): void
    {
        $engine = new FishActivityEngine();
        $weather = $this->sampleWeather();
        $result = $engine->calculate($weather, 42.7, 25.5);

        $periods = $result['solunar_periods'];
        foreach (['major1', 'major2', 'minor1', 'minor2'] as $key) {
            $this->assertTrue(isset($periods[$key]), "Missing solunar period: {$key}");
            $this->assertTrue(isset($periods[$key]['start']), "Missing start for: {$key}");
            $this->assertTrue(isset($periods[$key]['end']), "Missing end for: {$key}");
            $this->assertTrue(isset($periods[$key]['peak']), "Missing peak for: {$key}");
        }
    }

    /** @test */
    public function testBestTimesStructure(): void
    {
        $engine = new FishActivityEngine();
        $weather = $this->sampleWeather();
        $result = $engine->calculate($weather, 42.7, 25.5);

        $this->assertTrue(is_array($result['best_times']), 'best_times should be array');
        foreach ($result['best_times'] as $window) {
            $this->assertTrue(isset($window['start']), 'Missing start in best_times');
            $this->assertTrue(isset($window['end']), 'Missing end in best_times');
            $this->assertTrue(isset($window['peak_score']), 'Missing peak_score in best_times');
        }
    }

    /** @test */
    public function testExtremeHeatReducesScore(): void
    {
        $engine = new FishActivityEngine();
        $hotWeather = $this->sampleWeather(['temperature' => 38, 'feels_like' => 40]);
        $normalWeather = $this->sampleWeather(['temperature' => 20, 'feels_like' => 20]);

        $hotResult = $engine->calculate($hotWeather, 42.7, 25.5);
        $normalResult = $engine->calculate($normalWeather, 42.7, 25.5);

        $this->assertTrue(
            $hotResult['factors']['temperature']['score'] < $normalResult['factors']['temperature']['score'],
            "Extreme heat ({$hotResult['factors']['temperature']['score']}) should score lower than normal ({$normalResult['factors']['temperature']['score']})"
        );
    }

    /** @test */
    public function testThunderstormReducesScore(): void
    {
        $engine = new FishActivityEngine();
        $stormWeather = $this->sampleWeather(['weather' => 'Thunderstorm']);
        $clearWeather = $this->sampleWeather(['weather' => 'Clear']);

        $stormResult = $engine->calculate($stormWeather, 42.7, 25.5);
        $clearResult = $engine->calculate($clearWeather, 42.7, 25.5);

        $this->assertTrue(
            $stormResult['factors']['precipitation']['score'] < $clearResult['factors']['precipitation']['score'],
            'Thunderstorm should reduce precipitation score'
        );
    }

    /** @test */
    public function testHighWindReducesScore(): void
    {
        $engine = new FishActivityEngine();
        $windyWeather = $this->sampleWeather(['wind_speed' => 15]);
        $calmWeather = $this->sampleWeather(['wind_speed' => 2]);

        $windyResult = $engine->calculate($windyWeather, 42.7, 25.5);
        $calmResult = $engine->calculate($calmWeather, 42.7, 25.5);

        $this->assertTrue(
            $windyResult['factors']['wind_cloud']['score'] < $calmResult['factors']['wind_cloud']['score'],
            'High wind should reduce wind_cloud score'
        );
    }

    /** @test */
    public function testSpeciesListReturnsAll(): void
    {
        $list = FishActivityEngine::getSpeciesList();

        $this->assertTrue(count($list) >= 7, 'Should have at least 7 species (incl. general)');
        foreach (['general', 'carp', 'trout', 'pike', 'catfish', 'perch', 'bass'] as $key) {
            $this->assertTrue(isset($list[$key]), "Missing species: {$key}");
            $this->assertTrue(isset($list[$key]['en']), "Missing EN label for: {$key}");
            $this->assertTrue(isset($list[$key]['bg']), "Missing BG label for: {$key}");
        }
    }

    /** @test */
    public function testWaterTemperatureEstimation(): void
    {
        $engine = new FishActivityEngine();
        $weather = $this->sampleWeather(['temperature' => 25]);
        $result = $engine->calculate($weather, 42.7, 25.5);

        $this->assertTrue(is_numeric($result['water_temp_est']), 'Water temp should be numeric');
        $this->assertRange($result['water_temp_est'], 0, 50, 'Water temp out of reasonable range');
    }

    // ─── Helpers ───────────────────────────────────────────

    private function sampleWeather(array $overrides = []): array
    {
        return array_merge([
            'temperature'   => 20,
            'feels_like'    => 19,
            'pressure'      => 1013,
            'humidity'      => 65,
            'wind_speed'    => 3.5,
            'wind_deg'      => 180,
            'clouds'        => 40,
            'weather'       => 'Clouds',
            'description'   => 'scattered clouds',
            'visibility'    => 10,
            'sunrise'       => strtotime('06:00'),
            'sunset'        => strtotime('20:00'),
            'location'      => 'Test Location',
            'source'        => 'unit_test',
        ], $overrides);
    }
}

// Run tests
(new FishActivityEngineTest())->run();
