<?php
declare(strict_types=1);

namespace knotlib\exceptionhandler\html\debugtracerenderer\php;

class PhpSourceRenderer
{
    /**
     *
     * @param PhpSourceElement[] $tokens
     * @param string|null $number_format
     * @param array|null $classes
     * @param int $start
     * @param int $end
     * @param int $tabsize
     *
     * @return string
     */
    public static function render( array $tokens, string $number_format = NULL, array $classes = NULL, int $start = 1, int $end = -1, int $tabsize = 4 ) : string
    {
        if ( !$classes ){
            $classes = array();
        }
        if ( $start < 1 ){
            $start = 1;
        }
        if ( $end < $start ){
            $end = count($tokens);
        }

        $class = $classes['source_code'] ?? 'source_code';
        $html = "<pre class=\"$class\">";

        for( $i=$start; $i<$end; $i++ ){
            // 行
            $even_odd = (($i % 2) === 0) ? 'even' : 'odd';
            $class = $classes[$even_odd] ?? $even_odd;
            $html .= "<div class=\"$class\">";
            // 行番号
            if ( $number_format ){
                $class = $classes['line_no'] ?? 'line_no';
                $line_no = sprintf( $number_format, $i );
                $html .= "<span class=\"$class\">$line_no</span>";
            }
            // ソースコード
            /** @var array $tokens_line */
            $tokens_line = $tokens[$i] ?? array();
            $cnt = count($tokens_line);
            for( $j=0; $j<$cnt; $j++ ){
                $token = $tokens_line[$j];
                $code = htmlspecialchars($token->getCode());
                $type = $token->getType();
                // タブ展開
                if ( $tabsize && is_int($tabsize) ){
                    $code = str_replace( '    ', str_repeat('&nbsp;',$tabsize), $code );
                }
                switch( $type ){
                case PhpSourceElement::TYPE_KEYWORD:        $type = 'keyword';            break;
                case PhpSourceElement::TYPE_IDENTIFIER:    $type = 'identifier';        break;
                case PhpSourceElement::TYPE_COMMENT:        $type = 'comment';            break;
                case PhpSourceElement::TYPE_DELIMITER:        $type = 'delimiter';        break;
                case PhpSourceElement::TYPE_CONST_STRING:    $type = 'const_string';        break;
                }
                $class = $classes[$type] ?? $type;
                $html .= "<span class=\"$class\">$code</span>";
            }
            $html .= "</div>";
        }
        $html .= "</pre>";

        return $html;
    }


}

