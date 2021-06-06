<?php
declare(strict_types=1);

namespace knotlib\exceptionhandler\html\test;

use PHPUnit\Framework\TestCase;

use knotlib\exception\runtime\HttpStatusException;
use knotlib\exceptionhandler\html\debugtracerenderer\HtmlDebugtraceRenderer;

class HtmlDebugtraceRendererTest extends TestCase
{
    public function testRender()
    {
        $renderer = new HtmlDebugtraceRenderer();
        
        ob_start();
        $renderer->render(new HttpStatusException(404));
        $html = ob_get_clean();
        
        $this->assertEquals('<!DOCTYPE html PUBLIC', substr($html,0,21));
        $this->assertNotEquals(false, strpos($html,'<td class="message"><div class="value">HTTP status error: status=[404]</div></td>'));
    }
}