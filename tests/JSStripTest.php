<?php

namespace splitbrain\JSStrip\tests;

use PHPUnit\Framework\TestCase;
use splitbrain\JSStrip\JSStrip;

class JSStripTest extends TestCase
{
    /**
     * Basic test cases
     *
     * @return array[] [input, expected]
     * @see testBasics
     */
    public function provideBasics()
    {
        return [
            ['var foo=6;/* another comment */', 'var foo=6;'],
            ['/*@if (@_win32)', '/*@if(@_win32)'], // conditional comment
            ['// an comment', ''],
            ['var foo=6;// another comment ', 'var foo=6;'],
            ['var foo=6;// another comment / or something with // comments ', 'var foo=6;'],
            ['foo.split( /[a-Z\/]*/ );', 'foo.split(/[a-Z\/]*/);'],
            ['[/"/ , /"/ , /"/]', '[/"/,/"/,/"/]'],
            ['{ a : /"/ }', '{a:/"/}'],
            ['a = 5 + ++b;', 'a=5+ ++b;'],
            ['a = 5+ ++b;', 'a=5+ ++b;',],
            ['a = 5++ + b;', 'a=5++ +b;'],
            ['a = 5++ +b;', 'a=5++ +b;'],
            ['a = 5 - --b;', 'a=5- --b;'],
            ['a = 5- --b;', 'a=5- --b;'],
            ['a = 5-- - b;', 'a=5-- -b;'],
            ['a = 5-- -b;', 'a=5-- -b;'],
            ['a = 5-- +b;', 'a=5--+b;'],
            ['a = 5-- + b;', 'a=5--+b;'],
            ['a = 5++ - b;', 'a=5++-b;'],
            ['a = 5++ -b;', 'a=5++-b;'],
            ["var foo=6;\nvar baz=7;", 'var foo=6;var baz=7;'],
            ["  \t  var foo=6;", 'var foo=6;'],
            ["var foo=6;  \t  ", 'var foo=6;'],
            ["var foo = (baz) ? 'bar' : 'bla';", "var foo=(baz)?'bar':'bla';"],
            [
                "text.replace( \t \r\n  /\"/ , " . '"//" )',
                'text.replace(/"/,"//")'
            ],
            [
                'if ( peng == bla && /pattern\//.test(url)) request = new Something();',
                'if(peng==bla&&/pattern\//.test(url))request=new Something();'
            ],
            [
                'if ( peng == bla || /pattern\//.test(url)) request = new Something();',
                'if(peng==bla||/pattern\//.test(url))request=new Something();'
            ],
            [
                'var foo = "this is a \\' . "\n" . 'multiline string";',
                'var foo="this is a multiline string";'
            ],
            [
                "var foo = 'this is a \\\nmultiline string';",
                "var foo='this is a multiline string';"
            ],
            [
                'var π = Math.PI, τ = 2 * π, halfπ = π / 2, ε = 1e-6, ε2 = ε * ε, radians = π / 180, degrees = 180 / π;',
                'var π=Math.PI,τ=2*π,halfπ=π/2,ε=1e-6,ε2=ε*ε,radians=π/180,degrees=180/π;'
            ]
        ];
    }

    /**
     * @dataProvider provideBasics
     * @param string $input
     * @param string $expected
     */
    public function testBasics($input, $expected)
    {
        $this->assertEquals($expected, (new JSStrip())->compress($input));
    }

    /**
     * Test cases that should not be changed by the compressor
     *
     * @return array[] [input=output]
     * @see testUntouchables
     */
    public function provideUntouchables()
    {
        return [
            ['var foo="Now where does it end'],
            ["var foo='Now where does it end"],

            ["var foo='Now what \\' \"do we//get /*here*/ ?';"],
            ['var foo="Now what \\" \'do we//get /*here*/ ?";'],
            ['var foo="Now what \\\\\\" \'do we//get /*here*/ ?";'],
        ];
    }

    /**
     * @dataProvider provideUntouchables
     * @param string $input should be equal to the expected output
     */
    public function testUntouchables($input)
    {
        $this->assertEquals($input, (new JSStrip())->compress($input));
    }

    /**
     * Test cases provided as data files
     *
     * @return \Generator|string[][] [input, expected, file]
     * @see testFileData
     */
    public function provideFileData()
    {
        $files = glob(__DIR__ . '/data/test-*-in.js');

        foreach ($files as $file) {
            $input = file_get_contents($file);
            $output = file_get_contents(substr($file, 0, -5) . 'out.js');

            // ignore last newline
            if (substr($input, -1) == "\n") $input = substr($input, 0, -1);
            if (substr($output, -1) == "\n") $output = substr($output, 0, -1);

            yield [$input, $output, $file];
        }
    }

    /**
     * @dataProvider provideFileData
     * @param string $input
     * @param string $output
     * @param string $file
     */
    function testFileData($input, $output, $file)
    {
        $this->assertEquals($output, (new JSStrip())->compress($input), $file);
    }
}
