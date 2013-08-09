<?php

namespace Easybook\Utils;

use Michelf\MarkdownExtra;

/**
 * Custom Markdown parser
 */
class Markdown extends MarkdownExtra
{
    public function toHtml($text)
    {
        // highlight the code blocks that follow this syntax:
        //
        // ~~~
        // // ...
        // ~~~
        //
        // ~~~ .php
        // // ...
        // ~~~
        $text = preg_replace_callback(
            '/
                (?:\n|\A)
                # 1: Opening marker
                (
                    ~{3,} # Marker: three tilde or more.
                )
                [ ]*
                (?:
                    \.?([-_:a-zA-Z0-9]+) # 2: standalone class name
                )?
                [ ]* \n # Whitespace and newline following marker.

                # 4: Content
                (
                    (?>
                        (?!\1 [ ]* \n)    # Not a closing marker.
                        .*\n+
                    )+
                )

                # Closing marker.
                \1 [ ]* \n
            /Umx',
            function($matches) {
                $code     = rtrim($matches[3]);
                $language = trim($matches[2]) ?: 'code';

                // prevent false Markdown header generation by encoding '#' characters
                $code = str_replace(
                    array('&#35;'),
                    array('#'),
                    $code
                );

                $language = 'js'   == $language ? 'javascript' : $language;
                $language = 'json' == $language ? 'javascript' : $language;
                $language = 'html' == $language ? 'html5'      : $language;

                $geshi = new \GeSHi();
                $geshi->enable_classes(); // this must be the first method (see Geshi doc)
                $geshi->set_encoding('UTF-8');
                $geshi->set_header_type(GESHI_HEADER_PRE);
                $geshi->set_overall_class('code');
                $geshi->enable_line_numbers(GESHI_NO_LINE_NUMBERS);
                $geshi->enable_keyword_links(false);

                $geshi->set_source($code);
                $geshi->set_language($language);
                $highlightedCode = $geshi->parse_code();

                // decode the '#' characters previously encoded to prevent false
                // Markdown header generation
                // also decode the unnecesarily encoded characters
                $highlightedCode = str_replace(
                    array('&#40;', '&#41;', '*',     '#'    ),
                    array('(',     ')',     '&#42;', '&#35;'),
                    $highlightedCode
                );

                return $highlightedCode;
            },
            $text
        );

        // after code highlighting, proceed with the regular Markdown transform
        $html = parent::defaultTransform($text);

        $html = str_replace(
            array('<p><pre class="', '</pre></p>'),
            array('<pre class="', '</pre>'),
            $html
        );

        return $html;
    }
}
