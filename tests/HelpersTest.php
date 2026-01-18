<?php
declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

final class HelpersTest extends TestCase
{
  public function testParseAedAmount(): void
  {
    $this->assertSame(1200000, \parse_aed_amount('AED 1,200,000'));
    $this->assertSame(1500, \parse_aed_amount('1 500'));
    $this->assertNull(\parse_aed_amount('abc'));
    $this->assertNull(\parse_aed_amount(null));
  }

  public function testBuildQueryOverridesAndFiltersEmpty(): void
  {
    $original = $_GET;
    $_GET = ['a' => '1', 'b' => '', 'c' => null, 'd' => 'ok'];

    $query = \build_query(['b' => 'two', 'e' => '']);
    $params = [];
    parse_str($query, $params);

    $this->assertSame(['a' => '1', 'b' => 'two', 'd' => 'ok'], $params);
    $_GET = $original;
  }

  public function testResolvePriceDataPrefersFurnishedAmount(): void
  {
    $input = [
      'price_amount' => '1000000',
      'price_furnished_amount' => '1200000',
      'price_unfurnished_amount' => null,
    ];

    $result = \App\Models\Listing::resolvePriceData($input);

    $this->assertSame('FURNISHED', $result['price_display_type']);
    $this->assertSame('Furnished', $result['price_display_label']);
    $this->assertSame('1200000', (string)$result['price_amount']);
  }
}
