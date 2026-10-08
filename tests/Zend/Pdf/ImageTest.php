<?php

class Zend_Pdf_ImageTest extends Zend_Pdf_TestCase
{
    private function createPngFile()
    {
        $path = $this->registerTempFile(sys_get_temp_dir() . '/zpng_' . uniqid('', true) . '.png');
        $im = imagecreatetruecolor(4, 4);
        imagepng($im, $path);
        imagedestroy($im);
        return $path;
    }

    private function createJpegFile()
    {
        $path = $this->registerTempFile(sys_get_temp_dir() . '/zjpg_' . uniqid('', true) . '.jpg');
        $im = imagecreatetruecolor(4, 4);
        imagejpeg($im, $path);
        imagedestroy($im);
        return $path;
    }

    public function testImageWithPathPng()
    {
        $path = $this->createPngFile();
        $image = Zend_Pdf_Image::imageWithPath($path);
        $this->assertInstanceOf('Zend_Pdf_Resource_Image', $image);
        $this->assertGreaterThan(0, $image->getPixelWidth());
        $this->assertGreaterThan(0, $image->getPixelHeight());
    }

    public function testImageWithPathJpeg()
    {
        $path = $this->createJpegFile();
        $image = Zend_Pdf_Image::imageWithPath($path);
        $this->assertInstanceOf('Zend_Pdf_Resource_Image', $image);
    }

    public function testDrawImageOnPage()
    {
        $pdf = new Zend_Pdf();
        $page = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);
        $pdf->pages[] = $page;
        $image = Zend_Pdf_Image::imageWithPath($this->createPngFile());
        $page->drawImage($image, 10, 10, 50, 50);
        $contents = $this->getPageContents($page);
        $this->assertContainsSubstring(' Do', $contents);
    }
}
