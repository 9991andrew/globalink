<?php

if (! function_exists('camelToSpaced')) {
    function camelToSpaced($camelWords)
    {
        $spaced = preg_replace('/(?<=\w)(?=[A-Z])/'," $1", $camelWords);
        return $spaced;
    }
}

if (! function_exists('camelToKebab')) {
    function camelToKebab($camelWords)
    {
        return preg_replace('/(?<=\w)(?=[A-Z])/',"-$1", $camelWords);
    }
}

/**
 * Uses the correct indefinite article for English nouns for a given quantity.
 * Adapted from https://github.com/Kaivosukeltaja/php-indefinite-article
 * Yes this is super overkill, but kind of neat
 */
if(! function_exists('A') || function_exists('AN')) {
    // Alias the function so we can use "AN" or "A"
    function AN($input, $count = 1) {return A($input, $count);}

    function A($input, $count = 1)
    {
        $matches = array();
        $matchCount = preg_match("/\A(\s*)(?:an?\s+)?(.+?)(\s*)\Z/i", $input, $matches);
        list($all, $pre, $word, $post) = $matches;
        if (!$word)
            return $input;
        $result = function ($wrd, $cnt):string {
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

            if($cnt != 1) // TODO: Check against $PL_count_one instead
                return "$cnt $wrd";

            # HANDLE USER-DEFINED VARIANTS
            // TODO

            # HANDLE NUMBERS IN DIGIT FORM (1,2 …)
            #These need to be checked early due to the methods used in some cases below

            #any number starting with an '8' uses 'an'
            if(preg_match("/^[8](\d+)?/", $wrd))					return "an $wrd";

            #numbers starting with a '1' are trickier, only use 'an'
            #if there are 3, 6, 9, … digits after the 11 or 18

            #check if word starts with 11 or 18
            if(preg_match("/^[1][1](\d+)?/", $wrd) || (preg_match("/^[1][8](\d+)?/", $wrd))) {

                #first strip off any decimals and remove spaces or commas
                #then if the number of digits modulus 3 is 2 we have a match
                if(strlen(preg_replace(array("/\s/", "/,/", "/\.(\d+)?/"), '', $wrd))%3 == 2) return "an $wrd";
            }

            # HANDLE ORDINAL FORMS
            if(preg_match("/^(".$A_ordinal_a.")/i", $wrd)) 		return "a $wrd";
            if(preg_match("/^(".$A_ordinal_an.")/i", $wrd))     	return "an $wrd";

            # HANDLE SPECIAL CASES
            if(preg_match("/^(".$A_explicit_an.")/i", $wrd))     	return "an $wrd";
            if(preg_match("/^[aefhilmnorsx]$/i", $wrd))     	    return "an $wrd";
            if(preg_match("/^[bcdgjkpqtuvwyz]$/i", $wrd))     	    return "a $wrd";

            # HANDLE ABBREVIATIONS
            if(preg_match("/^(".$A_abbrev.")/x", $wrd))     		return "an $wrd";
            if(preg_match("/^[aefhilmnorsx][.-]/i", $wrd))     	return "an $wrd";
            if(preg_match("/^[a-z][.-]/i", $wrd))     		        return "a $wrd";

            # HANDLE CONSONANTS

            #KJBJM - the way this is written it will match any digit as well as non vowels
            #But is necessary for later matching of some special cases.  Need to move digit
            #recognition above this.
            #rule is: case insensitive match any string that starts with a letter not in [aeiouy]
            if(preg_match("/^[^aeiouy]/i", $wrd))                  return "a $wrd";

            # HANDLE SPECIAL VOWEL-FORMS
            if(preg_match("/^e[uw]/i", $wrd))                  	return "a $wrd";
            if(preg_match("/^onc?e\b/i", $wrd))                  	return "a $wrd";
            if(preg_match("/^uni([^nmd]|mo)/i", $wrd))		        return "a $wrd";
            if(preg_match("/^ut[th]/i", $wrd))                  	return "an $wrd";
            if(preg_match("/^u[bcfhjkqrst][aeiou]/i", $wrd))   	return "a $wrd";

            # HANDLE SPECIAL CAPITALS
            if(preg_match("/^U[NK][AIEO]?/", $wrd))                return "a $wrd";

            # HANDLE VOWELS
            if(preg_match("/^[aeiou]/i", $wrd))			        return "an $wrd";

            # HANDLE y... (BEFORE CERTAIN CONSONANTS IMPLIES (UNNATURALIZED) "i.." SOUND)
            if(preg_match("/^(".$A_y_cons.")/i", $wrd))	        return "an $wrd";

            #DEFAULT CONDITION BELOW
            # OTHERWISE, GUESS "a"
            return "a $wrd";
        };
        return $pre . $result($word, $count) . $post;
    }
}

if (!function_exists('svgClean')) {
    /**
     * Removes the xml and svg tags from an SVG image string
     */
    function svgClean(string $svg, $typeId=null) {
        // Remove xml tag
        $svg = preg_replace('/<\?xml[^>]*>/i', '', $svg);
        // Remove open svg tags
        $svg = preg_replace('/<svg[^>]*>/i', '', $svg);
        // Remove close svg tags
        $svg = preg_replace('/<\/svg[^>]*>/i', '', $svg);

        // If a typeId was given, wrap it in a group with the relevant class
        if (!is_null($typeId)) {
            $svg = '<g class="ait'.$typeId.'">'.$svg.'</g>';
        }
        return trim($svg);
    }
}

if (!function_exists('htmlCheck')) {
    /**
     * Accepts HTML as a string and will return true if valid, properly balanced html and false if not.
     * Useful for telling a user that their HTML passes muster and won't break the game.
     * @param $html
     * @return bool
     */
    function htmlCheck( $html )
    {
        $stack = array();
        $autoclosed = array('br', 'hr', 'input', 'embed', 'img', 'meta', 'link', 'param', 'source', 'track', 'area', 'base', 'col', 'wbr');
        $l = strlen($html); $i = 0;
        $incomment = false; $intag = false; $instring = false;
        $closetag = false; $tag = '';
        while($i<$l)
        {
            while($i<$l && preg_match('#\\s#', $c=$html[$i])) $i++;
            if ( $i >= $l ) break;
            if ( $incomment && ('-->' === substr($html, $i, 3)) )
            {
                // close comment
                $incomment = false;
                $i += 3;
                continue;
            }
            $c = $html[$i++];
            if ( '<' === $c )
            {
                if ( $incomment ) continue;
                if ( $intag )  return false;
                if ( '!--' === substr($html, $i, 3) )
                {
                    // open comment
                    $incomment = true;
                    $i += 3;
                    continue;
                }

                // open tag
                $intag = true;
                if ( '/' === $html[$i] )
                {
                    $i++;
                    $closetag = true;
                }
                else
                {
                    $closetag = false;
                }
                $tag = '';
                while($i<$l && preg_match('#[a-z0-9\\-]#i', $c=$html[$i]) )
                {
                    $tag .= $c;
                    $i++;
                }
                if ( !strlen($tag) ) return false;
                $tag = strtolower($tag);
                if ( $i<$l && !preg_match('#[\\s/>]#', $html[$i]) ) return false;
                if ( $i<$l && $closetag && preg_match('#^\\s*/>#sim', substr($html, $i)) ) return false;
                if ( $closetag )
                {
                    if ( in_array($tag, $autoclosed) || (array_pop($stack) !== $tag) )
                        return false;
                }
                else if ( !in_array($tag, $autoclosed) )
                {
                    $stack[] = $tag;
                }
            }
            else if ( '>' ===$c )
            {
                if ( $incomment ) continue;

                // close tag
                if ( !$intag ) return false;
                $intag = false;
            }
        }
        return !$incomment && !$intag && empty($stack);
    }
}
