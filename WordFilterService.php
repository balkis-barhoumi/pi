<?php

namespace App\Service;

use Symfony\Component\String\UnicodeString;

class WordFilterService
{
    public function filterWords(string $text): string
    {
        // List of inappropriate words
        $inappropriateWords = [
            'badword1',
            'badword2',
            
        ];

        
        foreach ($inappropriateWords as $word) {
            $text = (new UnicodeString($text))->replace($word, '***')->toString();
        }

        return $text;
    }

}