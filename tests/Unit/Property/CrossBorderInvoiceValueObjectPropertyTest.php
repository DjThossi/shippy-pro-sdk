<?php

declare(strict_types=1);

namespace DjThossi\ShippyProSdk\Tests\Unit\Property;

use function checkdate;

use DjThossi\ShippyProSdk\Domain\CountryCode;
use DjThossi\ShippyProSdk\Domain\CreateCrossBorderInvoiceRequest;
use DjThossi\ShippyProSdk\Domain\CrossBorderGoodsCollection;
use DjThossi\ShippyProSdk\Domain\CrossBorderGoodsItem;
use DjThossi\ShippyProSdk\Domain\DateOfExportation;
use DjThossi\ShippyProSdk\Domain\Incoterm;
use DjThossi\ShippyProSdk\Domain\InvoiceParty;
use DjThossi\ShippyProSdk\Exception\InvalidCountryCodeException;
use DjThossi\ShippyProSdk\Exception\InvalidCrossBorderInvoiceException;
use DjThossi\ShippyProSdk\Exception\InvalidDateOfExportationException;
use Eris\Attributes\ErisRepeat;
use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

use function preg_match;
use function sprintf;
use function str_pad;

use const STR_PAD_LEFT;

use function strtoupper;
use function trim;

final class CrossBorderInvoiceValueObjectPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Feature: CreateCrossBorderInvoice, Property 8: For any well-formed real YYYY-MM-DD date string construction of
     * DateOfExportation SHALL succeed and expose the trimmed input unchanged via ->value, while any malformed string or
     * impossible calendar date SHALL be rejected with InvalidDateOfExportationException.
     *
     * _Requirements: 11.2_
     * _Properties: 8_
     */
    #[ErisRepeat(100)]
    public function testDateOfExportationFormatValidation(): void
    {
        $this->forAll(
            Generators::choose(1, 9999),
            Generators::choose(1, 20),
            Generators::choose(1, 40),
        )->then(function (int $year, int $month, int $day): void {
            $candidate = sprintf(
                '%s-%s-%s',
                str_pad((string) $year, 4, '0', STR_PAD_LEFT),
                str_pad((string) $month, 2, '0', STR_PAD_LEFT),
                str_pad((string) $day, 2, '0', STR_PAD_LEFT),
            );

            if (checkdate($month, $day, $year)) {
                $date = new DateOfExportation($candidate);
                $this->assertSame($candidate, $date->value);
                // Leading/trailing whitespace is trimmed away.
                $padded = new DateOfExportation('  ' . $candidate . '  ');
                $this->assertSame($candidate, $padded->value);
            } else {
                // Impossible calendar dates (e.g. 2021-02-30, 2021-13-01) are rejected.
                $this->expectException(InvalidDateOfExportationException::class);
                new DateOfExportation($candidate);
            }
        });

        // Malformed shapes that do not match YYYY-MM-DD are always rejected.
        $this->forAll(
            Generators::elements('2021/01/01', '21-01-01', '2021-1-1', 'not-a-date', '', '2021-01', '2021-01-01T00:00'),
        )->then(function (string $malformed): void {
            $this->expectException(InvalidDateOfExportationException::class);
            new DateOfExportation($malformed);
        });
    }

    /**
     * Feature: CreateCrossBorderInvoice, Property 6: Any string that does not match ^[A-Z]{2}$ once uppercased and
     * trimmed SHALL be rejected with InvalidCountryCodeException when used as the InvoiceParty country, while any valid
     * two-letter code SHALL construct an InvoiceParty successfully exposing the normalized code.
     *
     * _Requirements: 11.10_
     * _Properties: 6_
     */
    #[ErisRepeat(100)]
    public function testInvalidCountryCodesAreRejectedForInvoiceParty(): void
    {
        // Invalid: any string whose trimmed/uppercased form is not exactly two A-Z letters.
        $this->forAll(Generators::suchThat(
            static function (string $value): bool {
                $normalized = strtoupper(trim($value));

                return preg_match('/^[A-Z]{2}$/', $normalized) !== 1;
            },
            Generators::string(),
        ))->then(function (string $invalid): void {
            $this->expectException(InvalidCountryCodeException::class);
            new InvoiceParty(
                'Acme Ltd',
                '1 Example Street',
                '12345',
                'Example City',
                new CountryCode($invalid),
                '+100000000',
            );
        });

        // Valid: a canonical two-letter code constructs an InvoiceParty and normalizes the country.
        $this->forAll(Generators::elements('US', 'de', ' it ', 'Fr', 'GB'))
            ->then(function (string $valid): void {
                $party = new InvoiceParty(
                    'Acme Ltd',
                    '1 Example Street',
                    '12345',
                    'Example City',
                    new CountryCode($valid),
                    '+100000000',
                );
                $this->assertSame(strtoupper(trim($valid)), $party->country->value);
            });
    }

    /**
     * Feature: CreateCrossBorderInvoice, Property 7: Constructing a CreateCrossBorderInvoiceRequest with an empty
     * CrossBorderGoodsCollection SHALL be rejected with InvalidCrossBorderInvoiceException, while any non-empty goods
     * collection SHALL be accepted.
     *
     * _Requirements: 11.2, 11.8_
     * _Properties: 7_
     */
    #[ErisRepeat(100)]
    public function testGoodsMinimumOneInvariant(): void
    {
        $this->forAll(Generators::choose(0, 5))
            ->then(function (int $goodsCount): void {
                $items = [];
                for ($i = 0; $i < $goodsCount; $i++) {
                    $items[] = new CrossBorderGoodsItem(
                        1,
                        'Widget ' . $i,
                        '123456',
                        1,
                        1.0,
                        10.0,
                    );
                }
                $goods = new CrossBorderGoodsCollection(...$items);

                if ($goodsCount === 0) {
                    $this->expectException(InvalidCrossBorderInvoiceException::class);
                    $this->buildRequest($goods);

                    return;
                }

                $request = $this->buildRequest($goods);
                $this->assertSame($goodsCount, $request->goods->count());
            });
    }

    private function buildRequest(CrossBorderGoodsCollection $goods): CreateCrossBorderInvoiceRequest
    {
        $party = new InvoiceParty(
            'Acme Ltd',
            '1 Example Street',
            '12345',
            'Example City',
            new CountryCode('DE'),
            '+100000000',
        );

        return new CreateCrossBorderInvoiceRequest(
            Incoterm::Dap,
            'permanent',
            'ORDER-1',
            'INV-1',
            new DateOfExportation('2021-01-01'),
            $party,
            $party,
            $goods,
        );
    }
}
