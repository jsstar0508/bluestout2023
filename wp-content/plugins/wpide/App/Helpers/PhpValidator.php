<?php
namespace WPIDE\App\Helpers;

use Exception;
use PhpParser\ParserFactory;

class PhpValidator
{

    /**
     * @throws Exception
     */
    public static function validate($content)
    {
        self::parse($content);
        //self::validateConstants($content);
    }

    public static function parse($content) {

        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        $parser->parse($content);
    }

    /**
     * @throws Exception
     */
    protected static function validateConstants($content)
    {

        $pattern = '/define\((.+?),.+?\)/';
        if (preg_match_all($pattern, $content, $matches)) {

            $matches = array_map(function ($match) {
                return trim(str_replace(['"', "'"], "", $match));
            }, $matches[1]);

            $duplicates = array();
            foreach (array_count_values($matches) as $val => $c) {
                if ($c > 1) {
                    $duplicates[] = $val;
                }
            }

            if (!empty($duplicates)) {
                $constant = array_shift($duplicates);
                throw new Exception('Constant ' . $constant . ' already defined!');
            }
        }
    }
}
