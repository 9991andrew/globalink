<?php
/**
 * Contains functions that are used outside of classes in various places in MEGA World.
 * initLocale(locale, domain)
 *      Sets the user's locale for localization
 *
 * A(input)
 *      Aliased to AN(input), returns a noun with the correct English indefinite article.
 *      This is not used in practice because this presents problems with localization.
 *
 *  injectVariables(string, array)
 *      Accepts a string and an array of variables. Replaces escaped variables (like {{A}})
 *      with its value if that variable name/value pair is found in the array.
 *
 *  similarity(string1, string2)
 *      Compares two strings and returns a decimal score (from 0 to 1) of the similarity
 *      based on the Levenshtein distance difference versus the length.
 *
 * nlpSimilarity
 *      WS-NLP (Word & Sentence Natural Language Processing Similarity) scoring.
 *      Returns a percentage of similarity between any two strings using an API.
 *
 * svgClean
 *     Removes the xml and svg tags from an SVG image string. From Management.
 *     Used to ensure that avatar SVGs are composited correctly.
 */
class Helpers
{
    static public function initLocale($locale = null, $domain = "messages")
    {
        // For some reason locale was getting set as 0. This should ensure it's not something useless.
        if (strlen($locale) < 2) {
            $locale = Config::DEFAULT_LOCALE;
        }
        // Browsers usually seem to send hyphens instead of underscores
        // Switch variants of Language to the closest version we have. Eg en_US becomes "en_CA".
        if (preg_match('/^en.*/', $locale)) $locale = 'en_CA.utf8';
        if (preg_match('/^fr.*/', $locale)) $locale = 'fr_FR.utf8';
        if (preg_match('/^ar.*/', $locale)) $locale = 'ar_EG.utf8';
        // Traditional Chinese if HK or TW
        if (preg_match('/zh.HK.*/',$locale) || preg_match('/zh.TW/', $locale)) {
            $locale = 'zh_TW.utf8';
        // Otherwise assume simplified and set to China
        } else if (preg_match('/^(zh).*/', $locale)) {
            $locale = 'zh_CN.utf8';
        }

        $_SESSION['locale'] = $locale;
        //putenv('LANG=');
        //putenv('LANGUAGE=');
        //putenv("LC_MESSAGES=$locale");
        //setlocale(LC_MESSAGES, $locale);
        putenv('LC_ALL='.$locale);
        setlocale(LC_ALL, $locale);
        bindtextdomain($domain, "locale");
        bind_textdomain_codeset($domain, "UTF-8");
        textdomain($domain);
    }


    /**
     * Uses the correct indefinite article for English nouns for a given quantity.
     * Adapted from https://github.com/Kaivosukeltaja/php-indefinite-article
     *
     * Unfortunately I probably won't be able to use this because it isn't very
     * conducive to this application being localized into different languages :(
     */
    // Alias the function so we can use "AN" or "A"
    static public function AN($input, $count = 1)
    {
        return A($input, $count);
    }

    static public function A($input, $count = 1)
    {
        $matches = array();
        $matchCount = preg_match("/\A(\s*)(?:an?\s+)?(.+?)(\s*)\Z/i", $input, $matches);
        list($all, $pre, $word, $post) = $matches;
        if (!$word)
            return $input;
        $result = function ($wrd, $cnt): string {
            # THIS PATTERN MATCHES STRINGS OF CAPITALS STARTING WITH A "VOWEL-SOUND"
            # CONSONANT FOLLOWED BY ANOTHER CONSONANT, AND WHICH ARE NOT LIKELY
            # TO BE REAL WORDS (OH, ALL RIGHT THEN, IT'S JUST MAGIC!)
            $A_abbrev = "(?! FJO | [HLMNS]Y.  | RY[EO] | SQU
                    | ( F[LR]? | [HL] | MN? | N | RH? | S[CHKLMNPTVW]? | X(YL)?) [AEIOU])
                        [FHLMNRSX][A-Z]";

            # THIS PATTERN CODES THE BEGINNINGS OF ALL ENGLISH WORDS BEGINING WITH A
            # 'y' FOLLOWED BY A CONSONANT. ANY OTHER Y-CONSONANT PREFIX THEREFORE
            # IMPLIES AN ABBREVIATION.
            $A_y_cons = 'y(b[lor]|cl[ea]|fere|gg|p[ios]|rou|tt)';

            # EXCEPTIONS TO EXCEPTIONS
            $A_explicit_an = "euler|hour(?!i)|heir|honest|hono|herb";
            $A_ordinal_an = "[aefhilmnorsx]-?th";
            $A_ordinal_a = "[bcdgjkpqtuvwyz]-?th";

            if ($cnt != 1)
                return "$cnt $wrd";

            # HANDLE USER-DEFINED VARIANTS


            # HANDLE NUMBERS IN DIGIT FORM (1,2 …)
            #These need to be checked early due to the methods used in some cases below

            #any number starting with an '8' uses 'an'
            if (preg_match("/^[8](\d+)?/", $wrd)) return "an $wrd";

            #numbers starting with a '1' are trickier, only use 'an'
            #if there are 3, 6, 9, … digits after the 11 or 18

            #check if word starts with 11 or 18
            if (preg_match("/^[1][1](\d+)?/", $wrd) || (preg_match("/^[1][8](\d+)?/", $wrd))) {

                #first strip off any decimals and remove spaces or commas
                #then if the number of digits modulus 3 is 2 we have a match
                if (strlen(preg_replace(array("/\s/", "/,/", "/\.(\d+)?/"), '', $wrd)) % 3 == 2) return "an $wrd";
            }

            # HANDLE ORDINAL FORMS
            if (preg_match("/^(" . $A_ordinal_a . ")/i", $wrd)) return "a $wrd";
            if (preg_match("/^(" . $A_ordinal_an . ")/i", $wrd)) return "an $wrd";

            # HANDLE SPECIAL CASES
            if (preg_match("/^(" . $A_explicit_an . ")/i", $wrd)) return "an $wrd";
            if (preg_match("/^[aefhilmnorsx]$/i", $wrd)) return "an $wrd";
            if (preg_match("/^[bcdgjkpqtuvwyz]$/i", $wrd)) return "a $wrd";

            # HANDLE ABBREVIATIONS
            if (preg_match("/^(" . $A_abbrev . ")/x", $wrd)) return "an $wrd";
            if (preg_match("/^[aefhilmnorsx][.-]/i", $wrd)) return "an $wrd";
            if (preg_match("/^[a-z][.-]/i", $wrd)) return "a $wrd";

            # HANDLE CONSONANTS

            #KJBJM - the way this is written it will match any digit as well as non vowels
            #But is necessary for later matching of some special cases.  Need to move digit
            #recognition above this.
            #rule is: case insensitive match any string that starts with a letter not in [aeiouy]
            if (preg_match("/^[^aeiouy]/i", $wrd)) return "a $wrd";

            # HANDLE SPECIAL VOWEL-FORMS
            if (preg_match("/^e[uw]/i", $wrd)) return "a $wrd";
            if (preg_match("/^onc?e\b/i", $wrd)) return "a $wrd";
            if (preg_match("/^uni([^nmd]|mo)/i", $wrd)) return "a $wrd";
            if (preg_match("/^ut[th]/i", $wrd)) return "an $wrd";
            if (preg_match("/^u[bcfhjkqrst][aeiou]/i", $wrd)) return "a $wrd";

            # HANDLE SPECIAL CAPITALS
            if (preg_match("/^U[NK][AIEO]?/", $wrd)) return "a $wrd";

            # HANDLE VOWELS
            if (preg_match("/^[aeiou]/i", $wrd)) return "an $wrd";

            # HANDLE y... (BEFORE CERTAIN CONSONANTS IMPLIES (UNNATURALIZED) "i.." SOUND)
            if (preg_match("/^(" . $A_y_cons . ")/i", $wrd)) return "an $wrd";

            #DEFAULT CONDITION BELOW
            # OTHERWISE, GUESS "a"
            return "a $wrd";
        };
        return $pre . $result($word, $count) . $post;
    }

    /**
     * Swaps any placeholder tokens with the supplied list of variables if any matching ones are found.
     * Eg: "{{A}}" will become "100", ^##b##^ will become 5 if an array with A=>100, b=>5 is supplied.
     * These are case-insensitive to make it easier for people editing the quest data.
     * @param $str string (may be null)
     * @param $vars array
     * @return string (or null)
     */

    static public function injectVariables($str, array $vars)
    {
        // Here are the patterns we find and replace:
        // ^{{var}}^
        // {{var}}
        // ^##var##^
        // Loop through vars to look for known patterns
        foreach ($vars as $name => $var) {
            $pattern1 = '/\^{{' . $name . '}}\^/i';
            $pattern2 = '/{{' . $name . '}}/i';
            $pattern3 = '/\^##' . $name . '##\^/i';
            $str = preg_replace($pattern1, $var, $str);
            $str = preg_replace($pattern2, $var, $str);
            $str = preg_replace($pattern3, $var, $str);
        }

        return $str;
    }


    /**
     * A simple comparison between strings that returns a score based on the Levenshtein distance.
     * Spaces will be removed (or homogenized) and punctuation will be removed, then the
     * Levenshtein difference will be divided by the average string length to return a score.
     *
     * A score > 0.9 should be considered very similar, a score < 0.7 is quite different.
     *
     * @param string $s1 First string to compare
     * @param string $s1 Second string to compare
     * @param bool $preserveSpaces Do not remove spaces if true
     * @param bool $preservePunctuation Do not remove punctuation if true
     * @return float
     */
    static public function similarity(string $s1, string $s2, bool $preserveSpaces = false, bool $preservePunctuation = false)
    {
        // Trim spaces from beginning and end and convert to lower case.
        $s1 = strtolower(trim($s1));
        $s2 = strtolower(trim($s2));

        if ($preserveSpaces) {
            // Homogenize whitespace
            $s1 = preg_replace('/\s+/', ' ', $s1);
            $s2 = preg_replace('/\s+/', ' ', $s2);

        } else {
            // Remove all spaces (default)
            $s1 = preg_replace('/\s+/', '', $s1);
            $s2 = preg_replace('/\s+/', '', $s2);
        }

        if (!$preservePunctuation) {
            // List of punctuation characters we will ignore
            $punct = '/[\\.,\/#!$%\^&\*;:{}=\-_`~()\'\"]/';

            // Remove punctuation
            $s1 = preg_replace($punct, '', $s1);
            $s2 = preg_replace($punct, '', $s2);
        }

        // The average length of the two strings
        $avgLen = (strlen($s1) + strlen($s2)) / 2;

        // The number of characters by which the strings are different
        $levDist = levenshtein($s1, $s2);

        // Similarity score based on levenshtein distance over string length
        return ($avgLen - $levDist) / $avgLen;
    }


    /**
     * WS-NLP (Word & Sentence Natural Language Processing Similarity) scoring.
     * Returns a percentage of similarity between any two strings.
     *
     * I have found this service to be extremely unreliable. It only seems to work about 1 of 50 times I try.
     *
     * @param string $correctKey - the correct answer in the db
     * @param string $target - answer given by player
     * @param array $wsnlp - the WSNLP configuration for this map
     * @return float - percentage score from 0 to 1
     */
    static public function nlpSimilarity(string $correctKey, string $target, array $wsnlp)
    {
        // Prepare POST request with the ith key sentence, jth
        // target sentence, and the value. The JSON object will
        // get sent to a PHP bridge where the similarity between
        // the key sentence and target sentence will be calculated
        $json = array(
            'key' => $correctKey,
            'target' => $target,
            'value' => 1, // 1 to get a number in the range of 0 to 1
            'method' => $wsnlp['maxbpm'] ? 'bpm' : 'old', // use Bipartite Matching algorithm
            'ngram_service' => $wsnlp['ngrampos'] ? 'ngrampos' : '',
            'canonical' => $wsnlp['canonical'] ? 'canonical' : '',
            'language' => $wsnlp['lang'], // en (English), fr (French), hi (Hindi)
            'email' => 'megaworld@vipresearch.ca'
        );
        $json = json_encode($json);

        $context = array('http' =>
            array(
                'method' => 'POST',
                'timeout' => $wsnlp['timeout'],
                'header' => 'Content-Type: application/json',
                'content' => $json
            )
        );
        $context = stream_context_create($context);

        try {
            // use file_get_get_contents and json_decode to capture response
            $contents = file_get_contents('https://ws-nlp.vipresearch.ca/bridge/', false, $context);
            $contents = json_decode($contents);
            // The API will often just return a null string. Consider this a score of 0. (We could show an error).
            if (is_null($contents)) return 0;
            return $contents->similarity; //will be a number in the range of 0 to 1
        } catch (Exception $e) {
            // We really should return some kind of error here, but if the whole backend blows up we could just
            // give the student a pass so they can continue playing MEGA World.
            return 1;
        }
    }


    /**
     * Removes the xml and svg tags from an SVG image string
     * @param $svg string SVG Code
     * @param $typeId string Type of avatar part
     */
    static public function svgClean(string $svg, $typeId = null, $color1 = null, $color2 = null, $color3 = null, $color4 = null)
    {
        // Remove xml tag
        $svg = preg_replace('/<\?xml[^>]*>/i', '', $svg);
        // Remove open svg tags
        $svg = preg_replace('/<svg[^>]*>/i', '', $svg);
        // Remove close svg tags
        $svg = preg_replace('/<\/svg[^>]*>/i', '', $svg);

        // Add a specified fill to any tags with color1-4 classes. (This presumes they don't already have a fill).
        // If the color is not specified, make it transparent
        for ($i = 1; $i <= 4; $i++) {
            $color = ${'color' . $i} ?? 'transparent';
            if (trim(strlen($color)) == 0) $color = 'transparent';
            $svg = preg_replace('/(class="[^"]*color' . $i . '[^"]*")/', '\1 fill="' . $color . '"', $svg);
        }

        // If a typeId was given, wrap it in a group with the relevant class
        if (!is_null($typeId)) {
            $svg = '<g class="ait' . $typeId . '">' . $svg . '</g>';
        }
        return $svg;
    }
}
