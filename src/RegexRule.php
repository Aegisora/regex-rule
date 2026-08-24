<?php

namespace Aegisora\Rules;

use Aegisora\RuleContract\Exceptions\InvalidRuleContextException;
use Aegisora\RuleContract\Models\Context;
use Aegisora\RuleContract\Models\Result;
use Aegisora\RuleContract\Rule;
use InvalidArgumentException;
use RuntimeException;

class RegexRule extends Rule
{
    private string $pattern;

    public function __construct(string $pattern)
    {
        $this->pattern = $pattern;
    }

    public static function create(
        string $pattern
    ): self {
        return new self($pattern);
    }

    protected function executeValidate(Context $context): Result
    {
        $this->validatePattern($this->pattern);

        $value = $context->getValue();

        if (!is_string($value)) {
            throw new InvalidRuleContextException();
        }

        $matches = preg_match($this->pattern, $value);

        if ($matches === false) {
            throw new RuntimeException(
                sprintf('Failed to execute regex "%s" (PCRE error code: %d).', $this->pattern, preg_last_error())
            );
        }

        return $matches === 1 ? $this->getDefaultValidResult() : $this->getDefaultInvalidResult();
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validatePattern(string $pattern): void
    {
        if (@preg_match($pattern, '') === false) {
            throw new InvalidArgumentException(sprintf('Invalid regular expression pattern: "%s".', $pattern));
        }
    }
}
