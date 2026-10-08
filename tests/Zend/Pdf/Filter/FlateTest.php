<?php

class Zend_Pdf_Filter_FlateTest extends Zend_Pdf_TestCase
{
    public function testRoundTrip()
    {
        $data = str_repeat('compress me ', 20);
        $this->assertSame($data, Zend_Pdf_Filter_Compression_Flate::decode(Zend_Pdf_Filter_Compression_Flate::encode($data)));
    }

    public function testPredictorSubRoundTrip()
    {
        $data = pack('C8', 1, 2, 3, 4, 5, 6, 7, 8);
        $params = array(
            'Predictor' => 11,
            'Columns' => 4,
            'Colors' => 1,
            'BitsPerComponent' => 8,
        );
        $encoded = Zend_Pdf_Filter_Compression_Flate::encode($data, $params);
        $this->assertSame($data, Zend_Pdf_Filter_Compression_Flate::decode($encoded, $params));
    }

    public function testPredictorPaethViaFifteen()
    {
        $data = pack('C8', 10, 20, 30, 40, 50, 60, 70, 80);
        $params = array(
            'Predictor' => 15,
            'Columns' => 4,
            'Colors' => 1,
            'BitsPerComponent' => 8,
        );
        $encoded = Zend_Pdf_Filter_Compression_Flate::encode($data, $params);
        $this->assertSame($data, Zend_Pdf_Filter_Compression_Flate::decode($encoded, $params));
    }

    public function testInvalidPredictorThrows()
    {
        $this->expectException(Zend_Pdf_Exception::class);
        Zend_Pdf_Filter_Compression_Flate::encode('x', array('Predictor' => 99));
    }
}
