<?php
function getSentiment($text) {
    $text = strtolower($text);

    $positive_words = [
        "good", "fresh", "nice", "clean", "tasty", "helpful",
        "great", "awesome", "love", "perfect", "amazing"
    ];

    $negative_words = [
        "bad", "spoiled", "rotten", "smelly", "late",
        "poor", "worst", "dirty", "stale"
    ];

    foreach ($positive_words as $word) {
        if (strpos($text, $word) !== false) {
            return "positive";
        }
    }

    foreach ($negative_words as $word) {
        if (strpos($text, $word) !== false) {
            return "negative";
        }
    }

    return "neutral";
}
?>
