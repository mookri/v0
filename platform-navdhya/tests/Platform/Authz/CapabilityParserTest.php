<?php

namespace Tests\Platform\Authz;

use App\Platform\Authz\CapabilityParser;
use App\Platform\Authz\Exceptions\CapabilityParseException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Capability parsing (SEC-020, T-0K01-01.3): strict shape parsing of
 * <module>:<resource>:<action> codes; malformed codes throw so sync can
 * fail closed with a named error code.
 */
final class CapabilityParserTest extends TestCase
{
    private CapabilityParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new CapabilityParser();
    }

    public function test_parses_valid_code_into_structured_metadata(): void
    {
        $capability = $this->parser->parse('sales:order:approve');

        $this->assertSame('sales:order:approve', $capability->code);
        $this->assertSame('sales', $capability->module);
        $this->assertSame('order', $capability->resource);
        $this->assertSame('approve', $capability->action);
        $this->assertSame(1, $capability->version);
        $this->assertFalse($capability->sensitive);
    }

    public function test_accepts_underscores_and_digits_in_segments(): void
    {
        $capability = $this->parser->parse('sales_v2:order_line_items:bulk_export');

        $this->assertSame('sales_v2', $capability->module);
        $this->assertSame('order_line_items', $capability->resource);
        $this->assertSame('bulk_export', $capability->action);
    }

    public function test_to_array_matches_k004_shape(): void
    {
        $payload = $this->parser->parse('platform:policy:view')->toArray();

        $this->assertSame(
            ['code', 'module', 'resource', 'action', 'version', 'sensitive', 'description'],
            array_keys($payload),
        );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function malformedCodeProvider(): array
    {
        return [
            'too few segments' => ['sales:order'],
            'too many segments' => ['sales:order:approve:extra'],
            'empty code' => [''],
            'uppercase module' => ['Sales:order:view'],
            'leading digit' => ['9sales:order:view'],
            'leading underscore resource' => ['sales:_order:view'],
            'hyphenated action' => ['sales:order:bulk-approve'],
            'wildcard is not a concrete code' => ['sales:*:view'],
            'whitespace segment' => ['sales: order:view'],
        ];
    }

    #[DataProvider('malformedCodeProvider')]
    public function test_malformed_codes_throw_capability_parse_exception(string $code): void
    {
        $this->expectException(CapabilityParseException::class);

        $this->parser->parse($code);
    }

    public function test_exception_carries_the_offending_code(): void
    {
        try {
            $this->parser->parse('sales:order');
            $this->fail('Expected CapabilityParseException.');
        } catch (CapabilityParseException $exception) {
            $this->assertSame('sales:order', $exception->capabilityCode);
            $this->assertStringContainsString('<module>:<resource>:<action>', $exception->getMessage());
        }
    }
}
