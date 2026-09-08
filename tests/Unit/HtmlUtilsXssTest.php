<?php

use MailSo\Base\HtmlUtils;

class HtmlUtilsXssTest extends \PHPUnit\Framework\TestCase
{
    private $scriptOpen;
    private $scriptClose;

    protected function setUp(): void
    {
        $this->scriptOpen = '<' . 'script';
        $this->scriptClose = '<' . '/script';
    }

    public function testIsDangerousUrlBlocksJavascriptProtocol()
    {
        $this->assertTrue(HtmlUtils::IsDangerousUrl('javascript:alert(1)'));
    }

    public function testIsDangerousUrlBlocksJavaScriptCaseInsensitive()
    {
        $this->assertTrue(HtmlUtils::IsDangerousUrl('JavaScript:alert(1)'));
        $this->assertTrue(HtmlUtils::IsDangerousUrl('JAVASCRIPT:alert(1)'));
        $this->assertTrue(HtmlUtils::IsDangerousUrl('JaVaScRiPt:alert(1)'));
    }

    public function testIsDangerousUrlBlocksHtmlEntityEncodedJavascript()
    {
        $this->assertTrue(HtmlUtils::IsDangerousUrl('&#106;avascript:alert(1)'));
        $this->assertTrue(HtmlUtils::IsDangerousUrl('&#00000000106;avascript:alert(1)'));
    }

    public function testIsDangerousUrlBlocksJavascriptWithWhitespaceInScheme()
    {
        $this->assertTrue(HtmlUtils::IsDangerousUrl("java\tscript:alert(1)"));
        $this->assertTrue(HtmlUtils::IsDangerousUrl("java\nscript:alert(1)"));
    }

    public function testIsDangerousUrlBlocksVbscriptProtocol()
    {
        $this->assertTrue(HtmlUtils::IsDangerousUrl('vbscript:msgbox(1)'));
    }

    public function testIsDangerousUrlBlocksDataProtocol()
    {
        $this->assertTrue(HtmlUtils::IsDangerousUrl('data:text/html,foo'));
    }

    public function testIsDangerousUrlAllowsSafeUrls()
    {
        $this->assertFalse(HtmlUtils::IsDangerousUrl('https://example.com'));
        $this->assertFalse(HtmlUtils::IsDangerousUrl('http://example.com/page'));
        $this->assertFalse(HtmlUtils::IsDangerousUrl('/relative/path'));
        $this->assertFalse(HtmlUtils::IsDangerousUrl('#anchor'));
        $this->assertFalse(HtmlUtils::IsDangerousUrl('mailto:test@example.com'));
    }

    public function testIsDangerousUrlAllowsEmptyString()
    {
        $this->assertFalse(HtmlUtils::IsDangerousUrl(''));
    }

    public function testIsDangerousUrlAllowsPlainPath()
    {
        $this->assertFalse(HtmlUtils::IsDangerousUrl('path/to/resource'));
    }

    public function testClearHtmlNeutralizesDataUrlInHref()
    {
        $html = '<a href="data:text/html,foo">click</a>';
        $result = HtmlUtils::ClearHtmlSimple($html);

        $this->assertStringNotContainsString('data:text/html', $result);
    }

    public function testClearHtmlNeutralizesCaseInsensitiveJavascriptInHref()
    {
        $html = '<a href="JavaScript:alert(1)">click</a>';
        $result = HtmlUtils::ClearHtmlSimple($html);

        $this->assertStringNotContainsString('JavaScript:alert', $result);
    }

    public function testClearHtmlNeutralizesVbscriptInHref()
    {
        $html = '<a href="vbscript:msgbox(1)">click</a>';
        $result = HtmlUtils::ClearHtmlSimple($html);

        $this->assertStringNotContainsString('vbscript:', $result);
    }

    public function testClearHtmlStripsEventHandlers()
    {
        $html = '<div onmouseover="alert(1)" onload="alert(2)">text</div>';
        $result = HtmlUtils::ClearHtmlSimple($html);

        $this->assertStringNotContainsString('onmouseover', $result);
        $this->assertStringNotContainsString('onload', $result);
        $this->assertStringNotContainsString('alert(1)', $result);
        $this->assertStringNotContainsString('alert(2)', $result);
    }

    public function testClearHtmlRemovesForbiddenTags()
    {
        $html = '<p>text</p>' . $this->scriptOpen . '>alert(1)' . $this->scriptClose . '><iframe src="evil.com"></iframe>';
        $result = HtmlUtils::ClearHtmlSimple($html);

        $this->assertStringNotContainsString($this->scriptOpen, $result);
        $this->assertStringNotContainsString('<iframe', $result);
        $this->assertStringContainsString('text', $result);
    }

    public function testClearHtmlRemovesStyleExpression()
    {
        $html = '<div style="width: expression(alert(1))">text</div>';
        $result = HtmlUtils::ClearHtmlSimple($html);

        $this->assertStringNotContainsString('expression', $result);
        $this->assertStringContainsString('text', $result);
    }

    public function testClearHtmlRemovesMozBinding()
    {
        $html = '<div style="-moz-binding: url(data:image/svg+xml,' . $this->scriptOpen . '">text</div>';
        $result = HtmlUtils::ClearHtmlSimple($html);

        $this->assertStringNotContainsString('-moz-binding', $result);
    }

    public function testClearHtmlRemovesDangerousUrlEncodedJavascriptInHref()
    {
        $html = '<a href="' . $this->scriptOpen . ':alert(1)">click</a>';
        $result = HtmlUtils::ClearHtmlSimple($html);

        $this->assertStringNotContainsString($this->scriptOpen . ':alert', $result);
    }
}
