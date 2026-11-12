<?php

namespace Tests\Unit;

use App\Support\Text;
use PHPUnit\Framework\TestCase;

class TextTest extends TestCase
{
    public function test_plain_decodes_entities_strips_tags_and_collapses_whitespace(): void
    {
        $this->assertSame('Men\'s & Kids "sale"', Text::plain(" Men&#039;s &amp;   <b>Kids</b>\n&quot;sale&quot; "));
    }

    public function test_plain_returns_an_empty_string_for_non_scalar_values(): void
    {
        $this->assertSame('', Text::plain(null));
        $this->assertSame('', Text::plain(['nested']));
    }

    public function test_rich_html_decodes_entity_encoded_markup_before_sanitizing(): void
    {
        $html = Text::richHtml('&lt;p onclick=&quot;x()&quot;&gt;Hello &lt;strong&gt;world&lt;/strong&gt;&lt;/p&gt;&lt;script&gt;x()&lt;/script&gt;');

        $this->assertSame('<p>Hello <strong>world</strong></p>', $html);
    }

    public function test_rich_html_hardens_links_and_images(): void
    {
        $html = Text::richHtml('<a href="https://example.com">link</a><img src="https://example.com/a.jpg"><img src="http://insecure.test/a.jpg">');

        $this->assertStringContainsString('rel="noopener noreferrer nofollow"', $html);
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('loading="lazy"', $html);
        $this->assertStringNotContainsString('http://insecure.test', $html);
    }

    public function test_rich_html_returns_an_empty_string_for_empty_input(): void
    {
        $this->assertSame('', Text::richHtml(null));
        $this->assertSame('', Text::richHtml("  \n "));
    }
}
