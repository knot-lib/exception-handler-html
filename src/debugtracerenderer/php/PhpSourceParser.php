<?php
declare(strict_types=1);

namespace knotlib\exceptionhandler\html\debugtracerenderer\php;

use knotlib\exceptionhandler\html\exception\PhpSourceParserException;

class PhpSourceParser
{
    private $_keywords;

    const ERROR_FILENOTFOUND    = 1;

    const DELEMITER_ELEMENTS   = '<>?\'\"$-+*%/;{}!&():,.=     []';
//    const OPERATOR_ELEMENTS    = '&& || += -= .= + - * / % = == === < > <= >= != <> ++ -- and or xor !';

    const LEXSTATE_NORMAL             = 'N';
    const LEXSTATE_BLOCK_COMMENT      = 'BC';
    const LEXSTATE_LINE_COMMENT       = 'LC';
    const LEXSTATE_CONST_STRING_DQ    = 'SD';
    const LEXSTATE_CONST_STRING_SQ    = 'SS';

    /**
     *    初期化
     *
     * @param string $keyword_file
     */
    public function init( string $keyword_file ) : void
    {
        $this->_keywords = file($keyword_file);
    }

    /**
     * @param string $buffer
     * @param string $state
     *
     * @return PhpSourceElement
     */
    private function getElement( string $buffer, string $state ) : PhpSourceElement
    {
        $is_keyword = isset($this->_keywords[$buffer]);
        $type = $is_keyword ? PhpSourceElement::TYPE_KEYWORD : PhpSourceElement::TYPE_IDENTIFIER;
        return new PhpSourceElement( $buffer, $type, $state );
    }
    
    /**
     * @param string $source_path
     *
     * @return array
     * @throws PhpSourceParserException
     */
    public function parse( string $source_path ) : array
    {
        if ( !is_file($source_path) ){
            return array();
        }

        // ソースファイルの読み込み
        $fp = fopen( $source_path, 'r' );
        if ( !$fp ) {
            throw new PhpSourceParserException("Source file not found:$source_path", self::ERROR_FILENOTFOUND);
        }
        $line_no = 1;
        $source = [];
        while ( !feof($fp) ) {
            $buffer = fgets( $fp, 4096 );
            $source[$line_no++] = $buffer;
        }
        fclose( $fp );

        // 解析
        $state = self::LEXSTATE_NORMAL;
        $const_string_escaping = FALSE;

        $tokens = array();
        $rows = count($source);
        for( $i=1; $i<$rows; $i++ ){
            $line = $source[$i];
            $cols = strlen($line);
            $in_buffer = array();
            for($j=0;$j<$cols;$j++){
                $c = $line[$j];
                array_push( $in_buffer, $c );
            }

            $done_buffer = array();
            $buffer = '';
            $tokens_line = array();
            while( NULL !== ($c = array_shift($in_buffer)) ){
                switch( $state ){
                case self::LEXSTATE_NORMAL:
                    {
                        // デリミタ要素ならトークン区切りと判断
                        $delimiter = (strpos(self::DELEMITER_ELEMENTS,$c) !== FALSE);
                        if ( $delimiter ){
                            if ( strlen($buffer) > 0 ){
                                $tokens_line[] = $this->getElement( $buffer, $state );
                            }
                            $buffer = '';
                        }
                        // コメント突入判定
                        $d = array_shift($in_buffer);
                        if ( $d && $c === '/' && $d === '*' ){
                            $state = self::LEXSTATE_BLOCK_COMMENT;
                            $buffer = '/*';
                            break;
                        }
                        if ( $d && $c === '/' && $d === '/' ){
                            $state = self::LEXSTATE_LINE_COMMENT;
                            $buffer = '//';
                            break;
                        }
                        array_unshift( $in_buffer, $d );
                        // 文字列定数判定
                        if ( $c === '"' ){
                            $state = self::LEXSTATE_CONST_STRING_DQ;
                            $buffer = '"';
                            break;
                        }
                        if ( $c === "'" ){
                            $state = self::LEXSTATE_CONST_STRING_SQ;
                            $buffer = "'";
                            break;
                        }
                        // バッファに１文字だけ追加
                        if ( $delimiter ){
                            $tokens_line[] = new PhpSourceElement( $c, PhpSourceElement::TYPE_DELIMITER, $state );
                            $buffer = '';
                        }
                        else{
                            $buffer .= $c;
                        }
                        array_unshift( $done_buffer, $c );
                    }
                    break;
                case self::LEXSTATE_BLOCK_COMMENT:
                    {
                        // コメント脱出判定
                        $d = array_shift($in_buffer);
                        if ( $d && $c === '*' && $d === '/' ){
                            $buffer .= $c . $d;
                            $state = self::LEXSTATE_NORMAL;
                            $tokens_line[] = new PhpSourceElement( $buffer, PhpSourceElement::TYPE_COMMENT, $state );
                            $buffer = '';
                            array_unshift( $done_buffer, $c );
                            array_unshift( $done_buffer, $d );
                            break;
                        }
                        array_unshift( $in_buffer, $d );
                        $buffer .= $c;
                        array_unshift( $done_buffer, $c );
                    }
                    break;
                case self::LEXSTATE_LINE_COMMENT:
                    {
                        // コメント脱出判定
                        $d = array_shift($in_buffer);
                        if ( !$d ){
                            $state = self::LEXSTATE_NORMAL;
                            if ( strlen($buffer) > 0 ){
                                $tokens_line[] = new PhpSourceElement( $buffer, PhpSourceElement::TYPE_COMMENT, $state );
                                $buffer = '';
                            }
                            break;
                        }
                        array_unshift( $in_buffer, $d );
                        $buffer .= $c;
                        array_unshift( $done_buffer, $c );
                    }
                    break;
                case self::LEXSTATE_CONST_STRING_DQ:
                    {
                        // エスケープ判定
                        if ( $c === '\\' ){
                            $const_string_escaping = !$const_string_escaping;
                        }
                        // 文字列定数脱出判定
                        if ( $c === '"' && !$const_string_escaping ){
                            $state = self::LEXSTATE_NORMAL;
                            $buffer .= $c;
                            $tokens_line[] = new PhpSourceElement( $buffer, PhpSourceElement::TYPE_CONST_STRING, $state );
                            $buffer = '';
                            break;
                        }
                        $buffer .= $c;
                        array_unshift( $done_buffer, $c );
                    }
                    break;
                case self::LEXSTATE_CONST_STRING_SQ:
                    {
                        // エスケープ判定
                        if ( $c === '\\' ){
                            $const_string_escaping = !$const_string_escaping;
                        }
                        // 文字列定数脱出判定
                        if ( $c === "'" && !$const_string_escaping ){
                            $state = self::LEXSTATE_NORMAL;
                            $buffer .= $c;
                            $tokens_line[] = new PhpSourceElement( $buffer, PhpSourceElement::TYPE_CONST_STRING, $state );
                            $buffer = '';
                            break;
                        }
                        $buffer .= $c;
                        array_unshift( $done_buffer, $c );
                    }
                    break;
                }
            }
            if ( strlen($buffer) > 0 ){
                switch ( $state ){
                case self::LEXSTATE_BLOCK_COMMENT:
                    {
                        $tokens_line[] = new PhpSourceElement( $buffer, PhpSourceElement::TYPE_COMMENT, $state );
                    }
                    break;
                default:
                    {
                        $tokens_line[] = $this->getElement( $buffer, $state );
                    }
                    break;
                }
            }
            $tokens[$i] = $tokens_line;
            // １行コメントならノーマル状態に戻す
            if ( $state === self::LEXSTATE_LINE_COMMENT ){
                $state = self::LEXSTATE_NORMAL;
            }
        }

        return $tokens;
    }


}

