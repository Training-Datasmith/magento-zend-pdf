<?php

use PHPUnit\Framework\TestCase;

abstract class Zend_Pdf_TestCase extends TestCase
{
    /** @var string[] */
    protected $tempFiles = array();

    protected function setUp()
    {
        parent::setUp();
        $this->resetMemoryManager();
        $this->assertFontCachesEmpty();
    }

    protected function tearDown()
    {
        $this->resetMemoryManager();

        $fontRef = new ReflectionClass('Zend_Pdf_Font');
        foreach (array('_fontNames', '_fontFilePaths') as $name) {
            $p = $fontRef->getProperty($name);
            $p->setAccessible(true);
            $p->setValue(null, array());
        }

        foreach ($this->tempFiles as $file) {
            if (is_string($file) && is_file($file)) {
                @unlink($file);
            }
        }
        $this->tempFiles = array();

        parent::tearDown();
    }

    protected function resetMemoryManager()
    {
        $ref = new ReflectionClass('Zend_Pdf');
        $prop = $ref->getProperty('_memoryManager');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }

    protected function registerTempFile($path)
    {
        $this->tempFiles[] = $path;
        return $path;
    }

    protected function assertFontCachesEmpty()
    {
        $fontRef = new ReflectionClass('Zend_Pdf_Font');
        foreach (array('_fontNames', '_fontFilePaths') as $name) {
            $p = $fontRef->getProperty($name);
            $p->setAccessible(true);
            $this->assertSame(array(), $p->getValue(null));
        }
    }

    /**
     * @param Zend_Pdf_Page $page
     * @return string
     */
    protected function getPageContents(Zend_Pdf_Page $page)
    {
        $ref = new ReflectionProperty($page, '_contents');
        $ref->setAccessible(true);
        return $ref->getValue($page);
    }

    protected function assertContainsSubstring($needle, $haystack)
    {
        $this->assertTrue(
            strpos($haystack, $needle) !== false,
            "Failed asserting that string contains '$needle'"
        );
    }

    protected function assertNotContainsSubstring($needle, $haystack)
    {
        $this->assertTrue(
            strpos($haystack, $needle) === false,
            "Failed asserting that string does not contain '$needle'"
        );
    }

    protected function assertEndsWith($suffix, $haystack)
    {
        $this->assertTrue(
            substr($haystack, -strlen($suffix)) === $suffix,
            "Failed asserting string ends with '$suffix'"
        );
    }
}
