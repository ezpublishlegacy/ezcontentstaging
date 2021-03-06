<?php
/**
 * Simple PHPDoc parser
 *
 * @package pake
 * @author Alexey Zakhlestin
 */
class eZPHPDocParser
{
    private $lines;
    private $looking_for_short = true;
    private $short_lines_counter = 0;

    public $short_desc = '';
    public $long_desc = '';

    public function __construct($lines)
    {
        $this->lines = $lines;
        $this->filterDocblockLines();
        $this->parse();
    }

    private function filterDocblockLines()
    {
        $lines = array();

        foreach ($this->lines as $line) {
            if (false === $line = self::filterDocblockLine($line))
                continue;

            $lines[] = $line;
        }

        $this->lines = $lines;
    }

    private function parse()
    {
        foreach ($this->lines as $line) {
            if (substr($line, 0, 1) == '@')
                break; // tags started. stopping parsing

            if ($this->looking_for_short) {
                if (false === $this->parseShortString($line))
                    break;
            } else {
                $this->long_desc .= $this->parseLongDesc($line);
            }
        }
    }

    private static function filterDocblockLine($line)
    {
        $line = trim($line);

        if ($line == '/**' or $line == '*/')
            return false; // first or last line

        if (substr($line, 0, 1) != '*')
            return false; // not docblock, ignore.

        return trim(substr($line, 1));
    }

    private function parseShortString($line)
    {
        // Short description is not over, yet
        if (strlen($line) == 0) {
            $this->looking_for_short = false;
            return true;
        }

        if (++$this->short_lines_counter > 3) {
            // overriding, too long for the short description
            $this->short_lines_counter = 0;
            $this->short_desc = '';
            $this->long_desc = '';

            // restarting
            $this->parseShortString($this->lines[0]);
            $this->looking_for_short = false;
            array_shift($this->lines);
            $this->parse();
            return false;
        }

        $success = preg_match('/(.*\.)(\W.*)/', $line, $matches);
        if ($success !== false and $success > 0) {
            // found something
            $this->short_desc .= trim($matches[1]);
            $this->looking_for_short = false;
            $this->long_desc .= trim($matches[2]).' ';
            return true;
        }

        $this->short_desc .= $line.' ';
        return true;
    }

    private function parseLongDesc($line)
    {
        if (strlen($line) == 0)
            return "\n";

        return $line.' ';
    }
}
