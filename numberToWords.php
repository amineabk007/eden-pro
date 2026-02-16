<?php
function numberToWords($number) {
    $units = ["", "un", "deux", "trois", "quatre", "cinq", "six", "sept", "huit", "neuf"];
    $teens = ["", "onze", "douze", "treize", "quatorze", "quinze", "seize", "dix-sept", "dix-huit", "dix-neuf"];
    $tens = ["", "dix", "vingt", "trente", "quarante", "cinquante", "soixante", "soixante-dix", "quatre-vingt", "quatre-vingt-dix"];
    $thousands = ["", "mille", "million", "milliard"];
    
    if ($number == 0) return "zéro";
    
    $words = [];
    if ($number < 0) {
        $words[] = "moins";
        $number = abs($number);
    }

    $groups = array_reverse(str_split(str_pad($number, ceil(strlen($number) / 3) * 3, "0", STR_PAD_LEFT), 3));
    foreach ($groups as $groupIndex => $group) {
        $groupWords = [];
        $hundreds = intval($group[0]);
        $tensAndUnits = intval(substr($group, 1));
        
        if ($hundreds) {
            $groupWords[] = $hundreds == 1 ? "cent" : $units[$hundreds] . " cent";
            if ($tensAndUnits == 0 && $hundreds > 1) $groupWords[] = "s";
        }

        if ($tensAndUnits) {
            if ($tensAndUnits < 10) {
                $groupWords[] = $units[$tensAndUnits];
            } elseif ($tensAndUnits < 20) {
                $groupWords[] = $teens[$tensAndUnits - 10];
            } else {
                $ten = intval($tensAndUnits / 10);
                $unit = $tensAndUnits % 10;
                $groupWords[] = $tens[$ten];
                if ($unit) $groupWords[] = ($ten == 1 || $ten == 7 || $ten == 9 ? "-" : " ") . $units[$unit];
            }
        }

        if (!empty($groupWords)) {
            // Handle "un mille" case
            if ($groupIndex === 1 && implode(" ", $groupWords) === "un") {
                $groupWords = ["mille"];
            } elseif ($groupIndex > 0) {
                $groupWords[] = $thousands[$groupIndex];
            }
            $words[] = implode(" ", $groupWords);
        }
    }

    return implode(" ", array_reverse($words));
}
?>
