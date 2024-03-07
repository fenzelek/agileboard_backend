<?php

if (! empty($greeting)) {
    echo $greeting, "\n\n";
} else {
    echo $level == 'error' ? trans('emails.default.whooops') : trans('emails.default.hi'), "\n\n";
}

echo "<br>";

if (! empty($introLines)) {
    echo implode("\n", $introLines), "\n\n";
}

if (isset($actionText)) {
    echo "{$actionText}: <a href='{$actionUrl}'>{$actionUrl}</a>", "\n\n";
}

if (! empty($outroLines)) {
    echo implode("\n", $outroLines), "\n\n";
}

echo "<br>";
echo trans('emails.default.dictionary_regards') . ',', "\n";
echo "<br>";
echo $regards, "\n";
