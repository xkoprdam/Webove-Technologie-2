<?php

declare(strict_types=1);

namespace Endroid\QrCode\Tests;

use Endroid\QrCode\Bacon\MatrixFactory;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\Matrix\MatrixInterface;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\BinaryWriter;
use Endroid\QrCode\Writer\ConsoleWriter;
use Endroid\QrCode\Writer\DebugWriter;
use Endroid\QrCode\Writer\EpsWriter;
use Endroid\QrCode\Writer\GifWriter;
use Endroid\QrCode\Writer\PdfWriter;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\Result\BinaryResult;
use Endroid\QrCode\Writer\Result\ConsoleResult;
use Endroid\QrCode\Writer\Result\DebugResult;
use Endroid\QrCode\Writer\Result\EpsResult;
use Endroid\QrCode\Writer\Result\GifResult;
use Endroid\QrCode\Writer\Result\PdfResult;
use Endroid\QrCode\Writer\Result\PngResult;
use Endroid\QrCode\Writer\Result\SvgResult;
use Endroid\QrCode\Writer\Result\WebpResult;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Writer\ValidatingWriterInterface;
use Endroid\QrCode\Writer\WebPWriter;
use Endroid\QrCode\Writer\WriterInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

final class QrCodeTest extends TestCase
{
    #[TestDox('Write as $resultClass with content type $contentType')]
    #[DataProvider('writerProvider')]
    public function testQrCode(WriterInterface $writer, string $resultClass, string $contentType): void
    {
        $qrCode = new QrCode(
            data: 'Data',
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Low,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255)
        );

        // Create generic logo
        $logo = new Logo(
            path: __DIR__.'/assets/symfony.png',
            resizeToWidth: 50
        );

        // Create generic label
        $label = new Label(
            text: 'Label',
            textColor: new Color(255, 0, 0)
        );

        $result = $writer->write($qrCode, $logo, $label);
        $this->assertInstanceOf(MatrixInterface::class, $result->getMatrix());

        if ($writer instanceof ValidatingWriterInterface) {
            $writer->validateResult($result, $qrCode->getData());
        }

        $this->assertInstanceOf($resultClass, $result);
        $this->assertEquals($contentType, $result->getMimeType());
        $this->assertStringContainsString('data:'.$result->getMimeType().';base64,', $result->getDataUri());
    }

    public static function writerProvider(): iterable
    {
        yield [new BinaryWriter(), BinaryResult::class, 'text/plain'];
        yield [new ConsoleWriter(), ConsoleResult::class, 'text/plain'];
        yield [new DebugWriter(), DebugResult::class, 'text/plain'];
        yield [new EpsWriter(), EpsResult::class, 'image/eps'];
        yield [new GifWriter(), GifResult::class, 'image/gif'];
        yield [new PdfWriter(), PdfResult::class, 'application/pdf'];
        yield [new PngWriter(), PngResult::class, 'image/png'];
        yield [new SvgWriter(), SvgResult::class, 'image/svg+xml'];
        yield [new WebPWriter(), WebpResult::class, 'image/webp'];
    }

    #[TestDox('Size and margin are handled correctly')]
    public function testSetSize(): void
    {
        $builder = new Builder(
            data: 'QR Code',
            size: 400,
            margin: 15
        );

        $result = $builder->build();

        $image = imagecreatefromstring($result->getString());

        $this->assertTrue(430 === imagesx($image));
        $this->assertTrue(430 === imagesy($image));
    }

    #[TestDox('Size and margin are handled correctly with rounded blocks')]
    #[DataProvider('roundedSizeProvider')]
    public function testSetSizeRounded(int $size, int $margin, RoundBlockSizeMode $roundBlockSizeMode, int $expectedSize): void
    {
        $builder = new Builder(
            data: 'QR Code contents with some length to have some data',
            size: $size,
            margin: $margin,
            roundBlockSizeMode: $roundBlockSizeMode
        );

        $result = $builder->build();

        $image = imagecreatefromstring($result->getString());

        $this->assertTrue(imagesx($image) === $expectedSize);
        $this->assertTrue(imagesy($image) === $expectedSize);
    }

    public static function roundedSizeProvider(): iterable
    {
        yield [400, 0, RoundBlockSizeMode::Enlarge, 406];
        yield [400, 5, RoundBlockSizeMode::Enlarge, 416];
        yield [400, 0, RoundBlockSizeMode::Margin, 400];
        yield [400, 5, RoundBlockSizeMode::Margin, 410];
        yield [400, 0, RoundBlockSizeMode::Shrink, 377];
        yield [400, 5, RoundBlockSizeMode::Shrink, 387];
    }

    #[TestDox('Invalid logo path results in exception')]
    public function testInvalidLogoPath(): void
    {
        $writer = new SvgWriter();
        $qrCode = new QrCode('QR Code');

        $logo = new Logo('/my/invalid/path.png');
        $this->expectExceptionMessageMatches('#Could not read logo image data from path "/my/invalid/path.png"#');
        $writer->write($qrCode, $logo);
    }

    #[TestDox('Invalid logo data results in exception')]
    public function testInvalidLogoData(): void
    {
        $writer = new SvgWriter();
        $qrCode = new QrCode('QR Code');

        $logo = new Logo(__DIR__.'/QrCodeTest.php');
        $this->expectExceptionMessage('Logo path is not an image');
        $writer->write($qrCode, $logo);
    }

    #[TestDox('Result can be saved to file')]
    public function testSaveToFile(): void
    {
        $path = __DIR__.'/test-save-to-file.png';

        $writer = new PngWriter();
        $qrCode = new QrCode('QR Code');
        $writer->write($qrCode)->saveToFile($path);

        $image = imagecreatefromstring(file_get_contents($path));

        $this->assertTrue(false !== $image);

        unlink($path);
    }

    #[TestDox('Label line breaks are not supported')]
    public function testLabelLineBreaks(): void
    {
        $qrCode = new QrCode('QR Code');
        $label = new Label("this\none has\nline breaks in it");

        $writer = new PngWriter();
        $this->expectExceptionMessage('Label does not support line breaks');
        $writer->write($qrCode, null, $label);
    }

    #[TestDox('Block size should be at least 1')]
    public function testBlockSizeTooSmall(): void
    {
        $aLotOfData = str_repeat('alot', 100);
        $qrCode = new QrCode(
            data: $aLotOfData,
            size: 10
        );

        $matrixFactory = new MatrixFactory();
        $this->expectExceptionMessage('Too much data: increase image dimensions or lower error correction level');
        $matrixFactory->create($qrCode);
    }

    #[TestDox('PNG Writer does not accept SVG logo, while SVG writer does')]
    public function testSvgLogo(): void
    {
        $qrCode = new QrCode('QR Code');
        $logo = new Logo(
            path: __DIR__.'/assets/symfony.svg',
            resizeToWidth: 100,
            resizeToHeight: 50
        );

        $svgWriter = new SvgWriter();
        $result = $svgWriter->write($qrCode, $logo);
        $this->assertInstanceOf(SvgResult::class, $result);

        $pngWriter = new PngWriter();
        $this->expectExceptionMessage('PNG Writer does not support SVG logo');
        $pngWriter->write($qrCode, $logo);
    }

    #[TestDox('SVG Writer can write compact SVG and non-compact SVG')]
    public function testSvgCompactOption(): void
    {
        $qrCode = new QrCode('QR Code');

        $svgWriter = new SvgWriter();
        $result = $svgWriter->write(qrCode: $qrCode, options: [SvgWriter::WRITER_OPTION_COMPACT => true]);
        $this->assertInstanceOf(SvgResult::class, $result);

        $svgWriter = new SvgWriter();
        $result = $svgWriter->write(qrCode: $qrCode, options: [SvgWriter::WRITER_OPTION_COMPACT => false]);
        $this->assertInstanceOf(SvgResult::class, $result);
    }

    #[TestDox('Logo punchout background is only available for GD writers')]
    public function testLogoPunchoutBackgroundAvailability(): void
    {
        $qrCode = new QrCode('QR Code');
        $logo = new Logo(
            path: __DIR__.'/assets/symfony.svg',
            resizeToWidth: 100,
            resizeToHeight: 50,
            punchoutBackground: true
        );

        $svgWriter = new SvgWriter();
        $this->expectExceptionMessageMatches('#The SVG writer does not support logo punchout background#');
        $svgWriter->write($qrCode, $logo);
    }
}
