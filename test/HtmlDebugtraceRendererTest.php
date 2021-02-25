<?php
declare(strict_types=1);

namespace KnotLib\ExceptionHandler\Html\Test;

use PHPUnit\Framework\TestCase;
use KnotLib\Exception\Runtime\HttpStatusException;
use KnotLib\ExceptionHandler\Html\DebugtraceRenderer\HtmlDebugtraceRenderer;

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