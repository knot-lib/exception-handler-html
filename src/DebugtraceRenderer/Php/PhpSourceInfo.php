<?php
declare(strict_types=1);

namespace KnotLib\ExceptionHandler\Html\DebugtraceRenderer\Php;

use KnotLib\ExceptionHandler\Html\Exception\PhpSourceParserException;

class PhpSourceInfo
{
    const DEFAULT_RANGE        = 5;

    /** @var string */
    private $_file;
    
    /** @var int */
    private $_line;
    
    /** @var int */
    private $_range;
    
    /**
     * Charcoal_PhpSourceInfo constructor.
     *
     * @param string $file
     * @param int $line
     * @param int|NULL $range
     */
    public function __construct( string $file, int $line, int $range = NULL )
    {
        $this->_file = $file;
        $this->_line = $line;
        $this->_range = $range ? $range : self::DEFAULT_RANGE;
    }


    /*
     *    ソースファイル名を取得
     */
    public function getFile()
    {
        return $this->_file;
    }

    /*
     *    行番号を取得
     */
    public function getLine()
    {
        return $this->_line;
    }
    
    /**
     * @return string
     */
    public function __toString()
    {
        $keyword_file = __DIR__ . '/php.kwd';

        $file = $this->_file;
        $line = $this->_line;

        $p = new PhpSourceParser();
        try{
            $p->init( $keyword_file );
            $tokens = $p->parse( $file );
        }
        catch(PhpSourceParserException $e){
            return '';
        }
        return PhpSourceRenderer::render( $tokens, '%4d:', NULL, $line - $this->_range, $line + $this->_range );
    }
}

