# Aegisora Regex Rule

[![Latest Version](https://img.shields.io/packagist/v/aegisora/regex-rule?style=flat-square)](https://packagist.org/packages/aegisora/regex-rule)
[![Total Downloads](https://img.shields.io/packagist/dt/aegisora/regex-rule?style=flat-square)](https://packagist.org/packages/aegisora/regex-rule)
![Code Coverage Badge](./badge.svg)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
![PHPStan Badge](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg?style=flat)

Regex Rule provides a simple, rule-based regular expression validation implementation for the Aegisora ecosystem.

It is built on top of [`aegisora/rule-contract`](https://github.com/Aegisora/rule-contract) and follows its strict validation architecture, ensuring consistent and predictable behavior across applications.

This rule is useful for validating user input, form fields, usernames, slugs, email addresses, phone numbers, API request parameters, and any other string that must match a specific pattern.

---

## 📑 Table of Contents
- [Features](#-features)
- [Installation](#-installation)
- [Core Concept](#-core-concept)
- [Basic Usage](#-basic-usage)
- [Valid vs Invalid](#-valid-vs-invalid)
- [Validation Result](#-validation-result)
- [Guardian Usage](#-guardian-usage)
- [Real-World Examples](#-real-world-examples)
- [Factory Methods](#-factory-methods)
- [Architecture](#-architecture)
- [License](#-license)
- [Contributing](#-contributing)
- [Support](#-support)

---

## ✨ Features
- 🔹 Lightweight and dependency-free except `aegisora/rule-contract`
- 🔹 Validates a string against any PCRE regular expression
- 🔹 Supports the full pattern syntax and flags (`i`, `u`, `m`, `s`, ...)
- 🔹 Rejects non-string input as an invalid context
- 🔹 Surfaces broken patterns and runtime PCRE failures as execution errors instead of a silent `false`
- 🔹 Fully compatible with Aegisora validation pipeline
- 🔹 Strict `Context` → `Result` validation flow
- 🔹 No raw booleans — only structured results
- 🔹 Safe execution via base `Rule` abstraction
- 🔹 Expressive factory API
- 🔹 Ready to use out of the box

---

## 📦 Installation

```bash
composer require aegisora/regex-rule
```

---

## 🚀 Core Concept

This package implements a single validation rule:

- accepts a string value via `Context`
- checks whether the string matches the configured regular expression
- returns a standardized `Result`

Under the hood it wraps the common boilerplate:

```php
if (preg_match($pattern, $value) !== 1) {
    // value does not match the pattern
}
```

into a reusable rule that reports its outcome through a `Result` object instead of a raw boolean, and turns PCRE failures into explicit exceptions.

---

## 🏗️ Basic Usage

```php
use Aegisora\RuleContract\Models\Context;
use Aegisora\Rules\RegexRule;

$result = RegexRule::create('/^[a-z0-9_-]+$/')->validate(Context::create('user_name-1'));

if ($result->isValid()) {
    // value matches the pattern
} else {
    // value does not match the pattern
}
```

The rule can also be instantiated directly:

```php
$result = (new RegexRule('/^[a-z0-9_-]+$/'))->validate(Context::create('user_name-1'));
```

---

## ✅ Valid vs Invalid

The rule passes when the string matches the configured pattern and fails otherwise.

### Anchored patterns

```php
RegexRule::create('/^[a-z]+$/')->validate(Context::create('abc'));      // valid   — the whole string matches
RegexRule::create('/^[a-z]+$/')->validate(Context::create('abc123'));   // invalid — digits are not allowed

RegexRule::create('/^\d+$/')->validate(Context::create('12345'));       // valid   — only digits
RegexRule::create('/^\d+$/')->validate(Context::create(''));            // invalid — at least one digit is required
```

### Unanchored patterns

```php
RegexRule::create('/\d+/')->validate(Context::create('abc123'));        // valid   — a digit is found somewhere
RegexRule::create('/\d+/')->validate(Context::create('abcdef'));        // invalid — no digit found
```

### Flags

```php
RegexRule::create('/^abc$/i')->validate(Context::create('ABC'));        // valid   — case-insensitive match
RegexRule::create('/^abc$/')->validate(Context::create('ABC'));         // invalid — case matters without the i flag

RegexRule::create('/^[а-яё]+$/ui')->validate(Context::create('Привет')); // valid  — u flag enables UTF-8 mode
```

---

## 🧪 Validation Result

If the string matches the pattern, the rule returns a valid result.

`$result->isValid(); // true`

If the string does not match the pattern, the rule returns an invalid result.

```php
$result->isValid(); // false
$result->getFailedRuleCode(); // regex_rule
```

If the context value is not a string, the rule throws:

`Aegisora\RuleContract\Exceptions\InvalidRuleContextException`

If the pattern is invalid, or the match fails at runtime (e.g. the backtrack limit is exceeded or the subject is not valid UTF-8 under the `u` flag), the rule throws:

`Aegisora\RuleContract\Exceptions\RuleExecutionException`

---

## 🔗 Guardian Usage

This rule can be used together with `aegisora/guardian` to build fluent validation pipelines.

```php
use Aegisora\Guardian\Guardian;
use Aegisora\Rules\RegexRule;
use App\Exceptions\InvalidUsernameException;

$guardian = new Guardian();

$guardian
    ->that($username)
    ->must(RegexRule::create('/^[a-z0-9_-]{3,32}$/'), new InvalidUsernameException())
    ->validate();
```

If the value does not match the pattern, `Guardian` throws the provided domain exception.

---

## 🧭 Real-World Examples

Regex Rule is useful for enforcing format constraints before values are persisted or processed.

Examples

```text
User Registration:

require a username of lowercase letters, digits, underscores and hyphens
```
```text
Slugs:

ensure a URL slug contains only lowercase letters, digits and hyphens
```
```text
Identifiers:

validate that a code matches a fixed structured format
```
```text
API:

reject request parameters that do not match the expected shape
```

---

## 🧩 Factory Methods
`RegexRule::create($pattern);`
- creates a rule that passes when the value matches the PCRE `$pattern` (delimiters and flags included)

`new RegexRule($pattern);`
- equivalent to `RegexRule::create($pattern)`

`RegexRule::create($pattern)->validate($context);`
- `$context` — `Context` wrapping the string value to validate

---

## 🏛️ Architecture

This package relies on [`aegisora/rule-contract`](https://github.com/Aegisora/rule-contract).

Flow:
1. `validate()` is called
2. `Context` is passed in
3. The configured pattern is checked; a broken pattern raises `RuleExecutionException`
4. The string value is extracted from context (non-strings raise `InvalidRuleContextException`)
5. The value is matched against the pattern with `preg_match()`; a PCRE runtime failure raises `RuleExecutionException`
6. `Result` is returned — valid on match, invalid with the `regex_rule` code on no match

All logic is safely handled by Rule contract.

---

## ⚖️ License

This package is open-source and licensed under the MIT License. See the [LICENSE](LICENSE) for details.

---

## 🌱 Contributing

Contributions are welcome and greatly appreciated! See the [CONTRIBUTING](CONTRIBUTING.md) for details.

---

## 🌟 Support

If you find this project useful, please consider giving it a star on GitHub!

It helps the project grow and motivates further development.
