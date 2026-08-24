<?php

namespace Aegisora\Rules\Tests\Unit;

use Aegisora\RuleContract\Exceptions\InvalidRuleContextException;
use Aegisora\RuleContract\Exceptions\RuleExecutionException;
use Aegisora\RuleContract\Models\Context;
use Aegisora\RuleContract\Models\Result;
use Aegisora\RuleContract\RuleInterface;
use Aegisora\Rules\RegexRule;
use PHPUnit\Framework\TestCase;
use stdClass;

class RegexRuleTest extends TestCase
{
    /**
     * @dataProvider getFactoryProvidedData
     */
    public function testFactoryCreatesRule(RegexRule $rule): void
    {
        self::assertInstanceOf(RuleInterface::class, $rule);
    }

    public static function getFactoryProvidedData(): array
    {
        return [
            'letters only' => [
                'rule' => RegexRule::create('/^[a-z]+$/'),
            ],
            'digits only' => [
                'rule' => RegexRule::create('/^\d+$/'),
            ],
            'case insensitive flag' => [
                'rule' => RegexRule::create('/^abc$/i'),
            ],
            'unicode flag' => [
                'rule' => RegexRule::create('/^[а-яё]+$/ui'),
            ],
        ];
    }

    /**
     * @dataProvider getValidateProvidedData
     */
    public function testValidate(
        Context $context,
        string $pattern,
        array $expectedResult
    ): void {
        self::assertActualResultEqualsExpected(
            RegexRule::create($pattern)->validate($context),
            $expectedResult
        );
    }

    public static function getValidateProvidedData(): array
    {
        return [
            'anchored letters match' => [
                'context' => Context::create('abc'),
                'pattern' => '/^[a-z]+$/',
                'expectedResult' => self::validResult(),
            ],
            'anchored letters do not match' => [
                'context' => Context::create('abc123'),
                'pattern' => '/^[a-z]+$/',
                'expectedResult' => self::invalidResult(),
            ],
            'anchored digits match' => [
                'context' => Context::create('12345'),
                'pattern' => '/^\d+$/',
                'expectedResult' => self::validResult(),
            ],
            'empty string matches optional pattern' => [
                'context' => Context::create(''),
                'pattern' => '/^\d*$/',
                'expectedResult' => self::validResult(),
            ],
            'empty string does not match required pattern' => [
                'context' => Context::create(''),
                'pattern' => '/^\d+$/',
                'expectedResult' => self::invalidResult(),
            ],
            'unanchored pattern matches substring' => [
                'context' => Context::create('abc123'),
                'pattern' => '/\d+/',
                'expectedResult' => self::validResult(),
            ],
            'unanchored pattern without substring is invalid' => [
                'context' => Context::create('abcdef'),
                'pattern' => '/\d+/',
                'expectedResult' => self::invalidResult(),
            ],
            'case insensitive flag matches' => [
                'context' => Context::create('ABC'),
                'pattern' => '/^abc$/i',
                'expectedResult' => self::validResult(),
            ],
            'case sensitive does not match' => [
                'context' => Context::create('ABC'),
                'pattern' => '/^abc$/',
                'expectedResult' => self::invalidResult(),
            ],
            'unicode pattern matches cyrillic' => [
                'context' => Context::create('Привет'),
                'pattern' => '/^[а-яё]+$/ui',
                'expectedResult' => self::validResult(),
            ],
        ];
    }

    /**
     * @dataProvider getInvalidContextProvidedData
     */
    public function testThrowsInvalidRuleContextException(Context $context): void
    {
        $this->expectException(InvalidRuleContextException::class);

        RegexRule::create('/^[a-z]+$/')->validate($context);
    }

    public static function getInvalidContextProvidedData(): array
    {
        return [
            'context value - true' => [
                'context' => Context::create(true),
            ],
            'context value - false' => [
                'context' => Context::create(false),
            ],
            'context value - zero integer' => [
                'context' => Context::create(0),
            ],
            'context value - positive integer' => [
                'context' => Context::create(1),
            ],
            'context value - negative integer' => [
                'context' => Context::create(-1),
            ],
            'context value - zero float' => [
                'context' => Context::create(0.0),
            ],
            'context value - positive float' => [
                'context' => Context::create(0.01),
            ],
            'context value - negative float' => [
                'context' => Context::create(-0.01),
            ],
            'context value - null' => [
                'context' => Context::create(null),
            ],
            'context value - not empty array' => [
                'context' => Context::create([123,]),
            ],
            'context value - empty array' => [
                'context' => Context::create([]),
            ],
            'context value - object' => [
                'context' => Context::create(new stdClass()),
            ],
            'context value - callable' => [
                'context' => Context::create(
                    static function () {
                    }
                ),
            ],
            'context value - resource' => [
                'context' => Context::create(tmpfile()),
            ],
        ];
    }

    /**
     * @dataProvider getRuleExecutionExceptionProvidedData
     */
    public function testThrowsRuleExecutionException(
        Context $context,
        string $pattern
    ): void {
        $this->expectException(RuleExecutionException::class);

        RegexRule::create($pattern)->validate($context);
    }

    public static function getRuleExecutionExceptionProvidedData(): array
    {
        return [
            'invalid pattern - missing delimiters' => [
                'context' => Context::create('abc'),
                'pattern' => 'not-a-valid-regex',
            ],
            'invalid pattern - unbalanced group' => [
                'context' => Context::create('abc'),
                'pattern' => '/^(abc$/',
            ],
            'runtime failure - invalid utf-8 subject with u flag' => [
                'context' => Context::create("\x80\xFF"),
                'pattern' => '/^.$/u',
            ],
        ];
    }

    private static function validResult(): array
    {
        return [
            'isValid' => true,
            'failedRuleCode' => null,
        ];
    }

    private static function invalidResult(): array
    {
        return [
            'isValid' => false,
            'failedRuleCode' => 'regex_rule',
        ];
    }

    private static function assertActualResultEqualsExpected(
        Result $result,
        array $expectedResult
    ): void {
        self::assertEquals($expectedResult['isValid'], $result->isValid());
        self::assertEquals($expectedResult['failedRuleCode'], $result->getFailedRuleCode());
    }
}
