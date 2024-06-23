<?php

class Pair {
    public $Key;
    public $Value;
}

function compare($val1, $val2) {
    return strcmp($val1->Value, $val2->Value);
}

function ShiftIndexes($key) {
    $lenOfkey = strlen($key);
    $indexes = array();
    $sortedarray = array();
    for ($i = 0; $i < $lenOfkey; ++$i) {
        $pair = new Pair();
        $pair->Key = $i;
        $pair->Value = $key[$i];
        $sortedarray[] = $pair;
    }
    usort($sortedarray, 'compare');
    for ($i = 0; $i < $lenOfkey; ++$i) {
        $indexes[$sortedarray[$i]->Key] = $i;
    }
    return $indexes;
}

function Encrypt2($text, $key) {
    $lenoftext = strlen($text);
    $lenOfkey = strlen($key);
    $text = ($lenoftext % $lenOfkey == 0) ? $text : str_pad($text, $lenoftext - ($lenoftext % $lenOfkey) + $lenOfkey, "_", STR_PAD_RIGHT);
    $lenoftext = strlen($text);
    $numofcols = $lenOfkey;
    $numofrows = ceil($lenoftext / $numofcols);
    $rowmatrix1 = array_fill(0, $numofrows, array_fill(0, $numofcols, ''));
    $colmatrix2 = array_fill(0, $numofcols, array_fill(0, $numofrows, ''));
    $sortedcolmatrix2 = array_fill(0, $numofcols, array_fill(0, $numofrows, ''));
    $shiftIndexes = ShiftIndexes($key);

    for ($i = 0; $i < $lenoftext; ++$i) {
        $currentRow = (int)($i / $numofcols);
        $currentColumn = $i % $numofcols;
        $rowmatrix1[$currentRow][$currentColumn] = $text[$i];
    }

    for ($i = 0; $i < $numofrows; $i++) {
        for ($j = 0; $j < $numofcols; $j++) {
            $colmatrix2[$j][$i] = $rowmatrix1[$i][$j];
        }
    }

    for ($i = 0; $i < $numofcols; $i++) {
        for ($j = 0; $j < $numofrows; $j++) {
            $sortedcolmatrix2[$shiftIndexes[$i]][$j] = $colmatrix2[$i][$j];
        }
    }

    $ciphertext = "";
    for ($i = 0; $i < $lenoftext; $i++) {
        $currentRow = (int)($i / $numofrows);
        $currentColumn = $i % $numofrows;
        $ciphertext .= $sortedcolmatrix2[$currentRow][$currentColumn];
    }

    return $ciphertext;
}


function Decrypt2($text, $key) {
    $lenOfkey = strlen($key);
    $lenoftext = strlen($text);
    $numofcols = ceil($lenoftext / $lenOfkey);
    $numofrows = $lenOfkey;
    $rowmatrix1 = array_fill(0, $numofrows, array_fill(0, $numofcols, ''));
    $colmatrix2 = array_fill(0, $numofcols, array_fill(0, $numofrows, ''));
    $unsortedcolmatrix2 = array_fill(0, $numofcols, array_fill(0, $numofrows, ''));
    $shiftIndexes = ShiftIndexes($key);

    // Remplir la matrice de colonnes à partir du texte chiffré
    for ($i = 0; $i < $lenoftext; ++$i) {
        $currentRow = (int)($i / $numofcols);
        $currentColumn = $i % $numofcols;
        $rowmatrix1[$currentRow][$currentColumn] = $text[$i];
    }

    // Transposer la matrice
    for ($i = 0; $i < $numofrows; $i++) {
        for ($j = 0; $j < $numofcols; $j++) {
            $colmatrix2[$j][$i] = $rowmatrix1[$i][$j];
        }
    }

    // Remettre les colonnes dans l'ordre original
    for ($i = 0; $i < $numofcols; $i++) {
        for ($j = 0; $j < $numofrows; $j++) {
            // Utiliser l'index inversé pour décrypter
            if (isset($shiftIndexes[$j])) {
                $unsortedcolmatrix2[$i][$j] = $colmatrix2[$i][$shiftIndexes[$j]]; // Utiliser l'index inversé
            }
        }
    }

    // Construire le texte clair à partir de la matrice déchiffrée
    $plaintext = "";
    for ($i = 0; $i < $lenoftext; $i++) {
        $currentRow = (int)($i / $numofrows);
        $currentColumn = $i % $numofrows;
        $plaintext .= $unsortedcolmatrix2[$currentRow][$currentColumn];
    }
    $plaintext = rtrim($plaintext, "_");

    return $plaintext;
}


function cesarEncrypt($text, $shift) {
    $result = '';
    $shift = $shift % 26; // Normalize shift to a value within the alphabet range
    foreach(str_split($text) as $char) {
        if(ctype_alpha($char)) {
            $ascii = ord(ctype_upper($char) ? 'A' : 'a');
            $char = chr((ord($char) - $ascii + $shift) % 26 + $ascii);
        }
        $result .= $char;
    }
    return $result;
}

function cesarDecrypt($text, $shift) {
    $result = '';
    $shift = $shift % 26; // Normalize shift to a value within the alphabet range
    foreach(str_split($text) as $char) {
        if(ctype_alpha($char)) {
            $ascii = ord(ctype_upper($char) ? 'A' : 'a');
            $char = chr((ord($char) - $ascii - $shift + 26) % 26 + $ascii);
        }
        $result .= $char;
    }
    return $result;
}

function keywordSubstitutionCipher($text, $keyword, $decrypt=false) {
    $alphabet = range('a', 'z');
    $keyword = str_replace(' ', '', strtolower($keyword));
    $keywordLetters = str_split($keyword);
    $substitution = array_unique($keywordLetters);
    
    // Ajouter les lettres restantes de l'alphabet à la fin du tableau de substitution
    foreach ($alphabet as $letter) {
        if (!in_array($letter, $substitution)) {
            $substitution[] = $letter;
        }
    }
    
    // Créer le tableau de substitution pour le déchiffrement
    if ($decrypt) {
        $substitution = array_combine($substitution, $alphabet);
    } else {
        $substitution = array_combine($alphabet, $substitution);
    }
    
    $result = '';
    foreach (str_split(strtolower($text)) as $char) {
        if (ctype_alpha($char)) {
            // Si la substitution existe pour le caractère, sinon on garde le caractère original
            $result .= $substitution[$char];
        } else {
            $result .= $char;
        }
    }
    return $result;
}

function transpositionCipher($text, $key, $decrypt=false) {
    if ($decrypt) {
        return Decrypt2($text, $key);
    } else {
        return Encrypt2($text, $key);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text = $_POST['inputText'] ?? null;
    $key = $_POST['key'] ?? null;
    $method = $_POST['method'] ?? null;
    $action = $_POST['action'] ?? null;

    if ($method && $text !== null && $key !== null && $action !== null) {
        if ($method === 'cesar') {
            if ($action === 'encrypt') {
                echo cesarEncrypt($text, (int)$key);
            } elseif ($action === 'decrypt') {
                echo cesarDecrypt($text, (int)$key);
            }
        } elseif ($method === 'substitution') {
            echo keywordSubstitutionCipher($text, $key, $action === 'decrypt');
        } elseif ($method === 'transposition') {
            echo transpositionCipher($text, $key, $action === 'decrypt');
        }
    } else {
        echo "Tous les champs sont obligatoires.";
    }
}
?>
