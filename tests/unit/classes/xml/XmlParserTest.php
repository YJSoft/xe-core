<?php

class XmlParserTest extends \Codeception\TestCase\Test
{
    public function testLegacyClassNameParsesXml()
    {
        if(class_exists('XMLParser', false))
        {
            $reflection = new ReflectionClass('XMLParser');
            if($reflection->isInternal())
            {
                $this->markTestSkipped('PHP provides an internal XMLParser class.');
            }
        }

        $parser = new XmlParser();
        $result = $parser->parse('<root><item key="value">Legacy XML</item></root>');

        $this->assertInstanceOf('XeXmlParser', $parser);
        $this->assertSame('Legacy XML', $result->root->item->body);
        $this->assertSame('value', $result->root->item->attrs->key);
    }

    public function testLegacyIncludePathCanBeLoadedRepeatedly()
    {
        require_once _XE_PATH_ . 'classes/xml/XeXmlParser.class.php';
        $internal = false;
        if(class_exists('XMLParser', false))
        {
            $reflection = new ReflectionClass('XMLParser');
            $internal = $reflection->isInternal();
        }

        require _XE_PATH_ . 'classes/xml/XmlParser.class.php';
        require _XE_PATH_ . 'classes/xml/XmlParser.class.php';

        $reflection = new ReflectionClass('XmlParser');
        $this->assertSame($internal, $reflection->isInternal());

        $parser = new XeXmlParser();
        $result = $parser->parse('<root>Modern XML</root>');
        $this->assertSame('Modern XML', $result->root->body);
    }
}
