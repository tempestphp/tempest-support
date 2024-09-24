<?php

declare(strict_types=1);

namespace Tempest\Support;

use Countable;
use function ltrim;
use function preg_quote;
use function preg_replace;
use function rtrim;
use Stringable;
use function trim;

final readonly class StringHelper implements Stringable
{
    public function __construct(
        private string $string = '',
    ) {
    }

    public function __toString(): string
    {
        return $this->string;
    }

    public function equals(string|self $other): bool
    {
        $string = is_string($other) ? $other : $other->string;

        return $this->string === $string;
    }

    public function title(): self
    {
        return new self(mb_convert_case($this->string, MB_CASE_TITLE, 'UTF-8'));
    }

    public function lower(): self
    {
        return new self(mb_strtolower($this->string, 'UTF-8'));
    }

    public function upper(): self
    {
        return new self(mb_strtoupper($this->string, 'UTF-8'));
    }

    public function snake(string $delimiter = '_'): self
    {
        $string = $this->string;

        if (ctype_lower($string)) {
            return $this;
        }

        $string = preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $string);
        $string = preg_replace('![^' . preg_quote($delimiter) . '\pL\pN\s]+!u', $delimiter, mb_strtolower($string, 'UTF-8'));
        $string = preg_replace('/\s+/u', $delimiter, $string);
        $string = trim($string, $delimiter);

        return (new self($string))->deduplicate($delimiter);
    }

    public function kebab(): self
    {
        return $this->snake('-');
    }

    public function pascal(): self
    {
        $words = explode(' ', str_replace(['-', '_'], ' ', $this->string));

        // TODO: use `mb_ucfirst` when it has landed in PHP 8.4
        $studlyWords = array_map(static fn (string $word) => ucfirst($word), $words);

        return new self(implode('', $studlyWords));
    }

    public function camel(): self
    {
        return new self(lcfirst((string) $this->pascal()));
    }

    public function deduplicate(string|array $characters = ' '): self
    {
        $string = $this->string;

        foreach (arr($characters) as $character) {
            $string = preg_replace('/' . preg_quote($character, '/') . '+/u', $character, $string);
        }

        return new self($string);
    }

    public function pluralize(int|array|Countable $count = 2): self
    {
        return new self(LanguageHelper::pluralize($this->string, $count));
    }

    public function pluralizeLast(int|array|Countable $count = 2): self
    {
        $parts = preg_split('/(.)(?=[A-Z])/u', $this->string, -1, PREG_SPLIT_DELIM_CAPTURE);

        $lastWord = array_pop($parts);

        $string = implode('', $parts) . (new self($lastWord))->pluralize($count);

        return new self($string);
    }

    public function random(int $length = 16): self
    {
        $string = '';

        while (($len = strlen($string)) < $length) {
            $size = $length - $len;
            $bytesSize = (int) ceil($size / 3) * 3;
            $bytes = random_bytes($bytesSize);
            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), offset: 0, length: $size);
        }

        return new self($string);
    }

    public function finish(string $cap): self
    {
        return new self(preg_replace('/(?:' . preg_quote($cap, '/') . ')+$/u', replacement: '', subject: $this->string) . $cap);
    }

    public function after(string|int $search): self
    {
        if ($search === '') {
            return $this;
        }

        $string = array_reverse(explode((string) $search, $this->string, limit: 2))[0];

        return new self($string);
    }

    public function afterLast(string|int $search): self
    {
        if ($search === '') {
            return $this;
        }

        $position = strrpos($this->string, (string) $search);

        if ($position === false) {
            return $this;
        }

        $string = substr($this->string, $position + strlen((string) $search));

        return new self($string);
    }

    public function before(string|int $search): self
    {
        if ($search === '') {
            return $this;
        }

        $string = strstr($this->string, (string) $search, before_needle: true);

        if ($string === false) {
            return $this;
        }

        return new self($string);
    }

    public function beforeLast(string|int $search): self
    {
        if ($search === '') {
            return $this;
        }

        $pos = mb_strrpos($this->string, (string) $search);

        if ($pos === false) {
            return $this;
        }

        $string = mb_substr($this->string, start: 0, length: $pos);

        return new self($string);
    }

    public function between(int|string $from, int|string $to): self
    {
        if ($from === '' || $to === '') {
            return $this;
        }

        return $this->after($from)->beforeLast($to);
    }

    public function trim(string $characters = " \n\r\t\v\0"): self
    {
        return new self(trim($this->string, $characters));
    }

    public function ltrim(string $characters = " \n\r\t\v\0"): self
    {
        return new self(ltrim($this->string, $characters));
    }

    public function rtrim(string $characters = " \n\r\t\v\0"): self
    {
        return new self(rtrim($this->string, $characters));
    }

    public function length(): int
    {
        return mb_strlen($this->string);
    }

    public function classBasename(): self
    {
        return new self(basename(str_replace('\\', '/', $this->string)));
    }
}
