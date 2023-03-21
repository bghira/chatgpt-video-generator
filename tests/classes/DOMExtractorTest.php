<?php

use PHPUnit\Framework\TestCase;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class DOMExtractorTest extends TestCase {
	public function __construct(string $name) {
		parent::__construct($name);
		$this->log = new Logger('testLogger');
    $this->log->pushHandler(new StreamHandler(__DIR__ . DIRECTORY_SEPARATOR . '/logs/tests.log', Logger::DEBUG));
	}
	public function testHandleSingleTicks() {
		$jsonData = json_decode('{"input":{"type":"div","data":{"tagName":"DIV","nodeType":1,"nodeValue":null,"attributes":{"class":"markdown prose w-full break-words dark:prose-invert dark"},"childNodes":[{"tagName":"P","nodeType":1,"nodeValue":null,"attributes":{},"childNodes":[{"nodeType":3,"nodeValue":"A short function that doubles input: ","attributes":{},"childNodes":[]},{"tagName":"CODE","nodeType":1,"nodeValue":null,"attributes":{},"childNodes":[{"nodeType":3,"nodeValue":"x => x * 2","attributes":{},"childNodes":[]}]}]}]}}}');
		$domExtractor = new DOMExtractor($jsonData, $this->log);
		$output = $domExtractor->extract();
		$expected = 'A short function that doubles input: `x => x * 2`';
		$this->assertEquals($expected, $output);
	}
	public function testHandlePreTag() {
		$jsonData = json_decode('{"input":{"type":"div","data":{"tagName":"DIV","nodeType":1,"nodeValue":null,"attributes":{"class":"markdown prose w-full break-words dark:prose-invert dark"},"childNodes":[{"tagName":"P","nodeType":1,"nodeValue":null,"attributes":{},"childNodes":[{"nodeType":3,"nodeValue":"Sure, heres an example of a simple JavaScript program that outputs a message to the console:","attributes":{},"childNodes":[]}]},{"tagName":"PRE","nodeType":1,"nodeValue":null,"attributes":{},"childNodes":[{"tagName":"DIV","nodeType":1,"nodeValue":null,"attributes":{"class":"bg-black rounded-md mb-4"},"childNodes":[{"tagName":"DIV","nodeType":1,"nodeValue":null,"attributes":{"class":"flex items-center relative text-gray-200 bg-gray-800 px-4 py-2 text-xs font-sans justify-between rounded-t-md"},"childNodes":[{"tagName":"SPAN","nodeType":1,"nodeValue":null,"attributes":{},"childNodes":[{"nodeType":3,"nodeValue":"javascript","attributes":{},"childNodes":[]}]},{"tagName":"BUTTON","nodeType":1,"nodeValue":null,"attributes":{"class":"flex ml-auto gap-2"},"childNodes":[{"tagName":"svg","nodeType":1,"nodeValue":null,"attributes":{"stroke":"currentColor","fill":"none","stroke-width":"2","viewBox":"0 0 24 24","stroke-linecap":"round","stroke-linejoin":"round","class":"h-4 w-4","height":"1em","width":"1em","xmlns":"http://www.w3.org/2000/svg","data-darkreader-inline-stroke":"","style":"--darkreader-inline-stroke:currentColor;"},"childNodes":[{"tagName":"path","nodeType":1,"nodeValue":null,"attributes":{"d":"M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"},"childNodes":[]},{"tagName":"rect","nodeType":1,"nodeValue":null,"attributes":{"x":"8","y":"2","width":"8","height":"4","rx":"1","ry":"1"},"childNodes":[]}]},{"nodeType":3,"nodeValue":"Copy code","attributes":{},"childNodes":[]}]}]},{"tagName":"DIV","nodeType":1,"nodeValue":null,"attributes":{"class":"p-4 overflow-y-auto"},"childNodes":[{"tagName":"CODE","nodeType":1,"nodeValue":null,"attributes":{"class":"!whitespace-pre hljs language-javascript"},"childNodes":[{"tagName":"SPAN","nodeType":1,"nodeValue":null,"attributes":{"class":"hljs-variable language_"},"childNodes":[{"nodeType":3,"nodeValue":"console","attributes":{},"childNodes":[]}]},{"nodeType":3,"nodeValue":".","attributes":{},"childNodes":[]},{"tagName":"SPAN","nodeType":1,"nodeValue":null,"attributes":{"class":"hljs-title function_"},"childNodes":[{"nodeType":3,"nodeValue":"log","attributes":{},"childNodes":[]}]},{"nodeType":3,"nodeValue":"(","attributes":{},"childNodes":[]},{"tagName":"SPAN","nodeType":1,"nodeValue":null,"attributes":{"class":"hljs-string"},"childNodes":[{"nodeType":3,"nodeValue":"\"Hello, world!\"","attributes":{},"childNodes":[]}]},{"nodeType":3,"nodeValue":");\n","attributes":{},"childNodes":[]}]}]}]}]},{"tagName":"P","nodeType":1,"nodeValue":null,"attributes":{},"childNodes":[{"nodeType":3,"nodeValue":"This program uses the ","attributes":{},"childNodes":[]},{"tagName":"CODE","nodeType":1,"nodeValue":null,"attributes":{},"childNodes":[{"nodeType":3,"nodeValue":"console.log()","attributes":{},"childNodes":[]}]},{"nodeType":3,"nodeValue":" method to print the message \"Hello, world!\" to the console, which is a built-in feature of web browsers and many other environments where JavaScript is used.","attributes":{},"childNodes":[]}]}]}},"context":[],"actions":[]}');
		$domExtractor = new DOMExtractor($jsonData, $this->log);
		$output = $domExtractor->extract();

		// Check if the extracted code block contains the language
		$this->assertStringContainsString('```javascript', $output);

		// Check if the extracted code block contains the code
		$expectedCode = 'This program uses the `console.log()` method to print the message "Hello, world!" to the console, which is a built-in feature of web browsers and many other environments where JavaScript is used.';
		$this->assertStringContainsString($expectedCode, $output);
		$unexpectedString = 'javascriptconsole.log("Hello, world!");';
		$this->assertStringNotContainsString($unexpectedString, $output, 'If this string is in the output, we have failed at correctly extracting the code language from the DOM.');
	}
}
