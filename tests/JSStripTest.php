<?php

namespace splitbrain\JSStrip\tests;

use PHPUnit\Framework\TestCase;
use splitbrain\JSStrip\JSStrip;

class JSStripTest extends TestCase
{

    function test_mlcom1()
    {
        $text = '/**
                  * A multi
                  * line *test*
                  * check
                  */';
        $this->assertEquals('', (new JSStrip())->compress($text));
    }

    function test_mlcom2()
    {
        $text = 'var foo=6;/* another comment */';
        $this->assertEquals('var foo=6;', (new JSStrip())->compress($text));
    }

    function test_mlcomcond()
    {
        $text = '/*@if (@_win32)';
        $this->assertEquals('/*@if(@_win32)', (new JSStrip())->compress($text));
    }

    function test_slcom1()
    {
        $text = '// an comment';
        $this->assertEquals('', (new JSStrip())->compress($text));
    }

    function test_slcom2()
    {
        $text = 'var foo=6;// another comment ';
        $this->assertEquals('var foo=6;', (new JSStrip())->compress($text));
    }

    function test_slcom3()
    {
        $text = 'var foo=6;// another comment / or something with // comments ';
        $this->assertEquals('var foo=6;', (new JSStrip())->compress($text));
    }

    function test_regex1()
    {
        $text = 'foo.split( /[a-Z\/]*/ );';
        $this->assertEquals('foo.split(/[a-Z\/]*/);', (new JSStrip())->compress($text));
    }

    function test_regex_in_array()
    {
        $text = '[/"/ , /"/ , /"/]';
        $this->assertEquals('[/"/,/"/,/"/]', (new JSStrip())->compress($text));
    }

    function test_regex_in_hash()
    {
        $text = '{ a : /"/ }';
        $this->assertEquals('{a:/"/}', (new JSStrip())->compress($text));
    }

    function test_regex_preceded_by_spaces_caracters()
    {
        $text = "text.replace( \t \r\n  /\"/ , " . '"//" )';
        $this->assertEquals('text.replace(/"/,"//")', (new JSStrip())->compress($text));
    }

    function test_regex_after_and_with_slashes_outside_string()
    {
        $text = 'if ( peng == bla && /pattern\//.test(url)) request = new Something();';
        $this->assertEquals('if(peng==bla&&/pattern\//.test(url))request=new Something();',
            (new JSStrip())->compress($text));
    }

    function test_regex_after_or_with_slashes_outside_string()
    {
        $text = 'if ( peng == bla || /pattern\//.test(url)) request = new Something();';
        $this->assertEquals('if(peng==bla||/pattern\//.test(url))request=new Something();',
            (new JSStrip())->compress($text));
    }

    function test_dquot1()
    {
        $text = 'var foo="Now what \\" \'do we//get /*here*/ ?";';
        $this->assertEquals($text, (new JSStrip())->compress($text));
    }

    function test_dquot2()
    {
        $text = 'var foo="Now what \\\\\\" \'do we//get /*here*/ ?";';
        $this->assertEquals($text, (new JSStrip())->compress($text));
    }

    function test_dquotrunaway()
    {
        $text = 'var foo="Now where does it end';
        $this->assertEquals($text, (new JSStrip())->compress($text));
    }

    function test_squot1()
    {
        $text = "var foo='Now what \\' \"do we//get /*here*/ ?';";
        $this->assertEquals($text, (new JSStrip())->compress($text));
    }

    function test_squotrunaway()
    {
        $text = "var foo='Now where does it end";
        $this->assertEquals($text, (new JSStrip())->compress($text));
    }

    function test_nl1()
    {
        $text = "var foo=6;\nvar baz=7;";
        $this->assertEquals('var foo=6;var baz=7;', (new JSStrip())->compress($text));
    }

    function test_lws1()
    {
        $text = "  \t  var foo=6;";
        $this->assertEquals('var foo=6;', (new JSStrip())->compress($text));
    }

    function test_tws1()
    {
        $text = "var foo=6;  \t  ";
        $this->assertEquals('var foo=6;', (new JSStrip())->compress($text));
    }

    function test_shortcond()
    {
        $text = "var foo = (baz) ? 'bar' : 'bla';";
        $this->assertEquals("var foo=(baz)?'bar':'bla';", (new JSStrip())->compress($text));

    }

    function test_complexminified()
    {
        $text = 'if(!k.isXML(a))try{if(e||!l.match.PSEUDO.test(c)&&!/!=/.test(c)){var f=b.call(a,c);if(f||!d||a.document&&a.document.nodeType!==11)return f}}catch(g){}return k(c,null,null,[a]).length>0}}}(),function(){var a=c.createElement("div");a.innerHTML="<div class=\'test e\'></div><div class=\'test\'></div>";if(!!a.getElementsByClassName&&a.getElementsByClassName("e").length!==0){a.lastChild.className="e";if(a.getElementsByClassName("e").length===1)return;foo="text/*";bla="*/"';

        $this->assertEquals($text, (new JSStrip())->compress($text));
    }

    function test_multilinestring()
    {
        $text = 'var foo = "this is a \\' . "\n" . 'multiline string";';
        $this->assertEquals('var foo="this is a multiline string";', (new JSStrip())->compress($text));

        $text = "var foo = 'this is a \\\nmultiline string';";
        $this->assertEquals("var foo='this is a multiline string';", (new JSStrip())->compress($text));
    }

    function test_nocompress()
    {
        $text = <<<EOF
var meh   =    'test' ;

/* BEGIN NOCOMPRESS */


var foo   =    'test' ;

var bar   =    'test' ;


/* END NOCOMPRESS */

var moh   =    'test' ;
EOF;
        $out = <<<EOF
var meh='test';
var foo   =    'test' ;

var bar   =    'test' ;
var moh='test';
EOF;

        $this->assertEquals($out, (new JSStrip())->compress($text));
    }

    function test_plusplus1()
    {
        $text = 'a = 5 + ++b;';
        $this->assertEquals('a=5+ ++b;', (new JSStrip())->compress($text));
    }

    function test_plusplus2()
    {
        $text = 'a = 5+ ++b;';
        $this->assertEquals('a=5+ ++b;', (new JSStrip())->compress($text));
    }

    function test_plusplus3()
    {
        $text = 'a = 5++ + b;';
        $this->assertEquals('a=5++ +b;', (new JSStrip())->compress($text));
    }

    function test_plusplus4()
    {
        $text = 'a = 5++ +b;';
        $this->assertEquals('a=5++ +b;', (new JSStrip())->compress($text));
    }

    function test_minusminus1()
    {
        $text = 'a = 5 - --b;';
        $this->assertEquals('a=5- --b;', (new JSStrip())->compress($text));
    }

    function test_minusminus2()
    {
        $text = 'a = 5- --b;';
        $this->assertEquals('a=5- --b;', (new JSStrip())->compress($text));
    }

    function test_minusminus3()
    {
        $text = 'a = 5-- - b;';
        $this->assertEquals('a=5-- -b;', (new JSStrip())->compress($text));
    }

    function test_minusminus4()
    {
        $text = 'a = 5-- -b;';
        $this->assertEquals('a=5-- -b;', (new JSStrip())->compress($text));
    }

    function test_minusplus1()
    {
        $text = 'a = 5-- +b;';
        $this->assertEquals('a=5--+b;', (new JSStrip())->compress($text));
    }

    function test_minusplus2()
    {
        $text = 'a = 5-- + b;';
        $this->assertEquals('a=5--+b;', (new JSStrip())->compress($text));
    }

    function test_plusminus1()
    {
        $text = 'a = 5++ - b;';
        $this->assertEquals('a=5++-b;', (new JSStrip())->compress($text));
    }

    function test_plusminus2()
    {
        $text = 'a = 5++ -b;';
        $this->assertEquals('a=5++-b;', (new JSStrip())->compress($text));
    }

    function test_unusual_signs()
    {
        $text = 'var π = Math.PI, τ = 2 * π, halfπ = π / 2, ε = 1e-6, ε2 = ε * ε, radians = π / 180, degrees = 180 / π;';
        $this->assertEquals('var π=Math.PI,τ=2*π,halfπ=π/2,ε=1e-6,ε2=ε*ε,radians=π/180,degrees=180/π;', (new JSStrip())->compress($text));
    }

    /**
     * Test the files provided with the original JsStrip
     */
    function test_original()
    {
        $files = glob(__DIR__ . '/data/test-*-in.js');

        foreach ($files as $file) {
            $info = "Using file $file";
            $this->assertEquals(file_get_contents(substr($file, 0, -5) . 'out.js'),
                (new JSStrip())->compress(file_get_contents($file)), $info);
        };
    }
}
